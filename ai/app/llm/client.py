"""Claude wrapper (PROJECT1_PLAN.md §5.6, §5.12, §5.13; story S2.3).

One request shape for every route: streamed (no HTTP timeouts on long outputs), structured output via
output_config.format, adaptive thinking with an explicit effort, cached system block, inference_geo pinned, and the
server-side refusal fallback ("default" routing) with the serving model recorded so a fallback is never silent.
"""

from __future__ import annotations

import time
from dataclasses import dataclass, field
from typing import Any

import anthropic

from app.core.config import Prices
from app.core.logging import get_logger

FALLBACK_BETA = "server-side-fallback-2026-07-01"
log = get_logger(__name__)


class LlmUnavailable(Exception):
    """Rate limits, overload, 5xx or network after the SDK's own retries. The caller's job may retry later."""


class LlmRequestError(Exception):
    """The request itself is wrong (400/401/403/404). Retrying won't help."""


@dataclass
class LlmCall:
    system: str
    messages: list[dict[str, Any]]
    schema: dict[str, Any]
    effort: str
    max_tokens: int


@dataclass
class LlmResult:
    text: str | None  # the JSON text, or None on refusal
    content: list[Any]  # full response content, to send back unchanged in a repair turn
    stop_reason: str | None
    refusal_category: str | None
    model_requested: str
    model_served: str
    fallback_used: bool
    usage: dict[str, int]
    cost_usd: float
    latency_ms: int
    ttft_ms: int | None
    request_id: str | None
    attempts: int = 1
    effort: str = ""
    inference_geo: str = ""

    def meta(self) -> dict[str, Any]:
        return {
            "model_requested": self.model_requested,
            "model_served": self.model_served,
            "fallback_used": self.fallback_used,
            "effort": self.effort,
            "inference_geo": self.inference_geo,
            "stop_reason": self.stop_reason,
            "refusal_category": self.refusal_category,
            **self.usage,
            "cost_usd": round(self.cost_usd, 6),
            "latency_ms": self.latency_ms,
            "ttft_ms": self.ttft_ms,
            "anthropic_request_id": self.request_id,
            "attempts": self.attempts,
        }


@dataclass
class ClaudeClient:
    client: Any  # anthropic.Anthropic, or a test double with the same beta.messages.stream surface
    model: str
    inference_geo: str
    use_fallbacks: bool
    prices: Prices = field(default_factory=Prices)

    def run(self, call: LlmCall) -> LlmResult:
        result = self._once(call, call.max_tokens)
        if result.stop_reason == "max_tokens":
            # One retry with 50% more room (§5.12); thinking counts towards max_tokens.
            retry = self._once(call, int(call.max_tokens * 1.5))
            retry.attempts = 2
            retry.cost_usd += result.cost_usd
            return retry
        return result

    def count_tokens(self, call: LlmCall) -> int:
        """Exact input size of a call (system + messages + output schema), for the bundle size guard."""
        try:
            counted = self.client.beta.messages.count_tokens(
                model=self.model,
                system=[{"type": "text", "text": call.system}],
                messages=call.messages,
                output_config={"format": {"type": "json_schema", "schema": call.schema}},
            )
        except (anthropic.RateLimitError, anthropic.InternalServerError, anthropic.APIConnectionError) as exc:
            raise LlmUnavailable(type(exc).__name__) from exc
        except anthropic.APIStatusError as exc:
            raise LlmRequestError(f"status {exc.status_code}") from exc
        return int(counted.input_tokens)

    def _once(self, call: LlmCall, max_tokens: int) -> LlmResult:
        kwargs: dict[str, Any] = {
            "model": self.model,
            "max_tokens": max_tokens,
            "inference_geo": self.inference_geo,
            "output_config": {"effort": call.effort, "format": {"type": "json_schema", "schema": call.schema}},
            "system": [{"type": "text", "text": call.system, "cache_control": {"type": "ephemeral"}}],
            "messages": call.messages,
        }
        if self.use_fallbacks:
            kwargs["betas"] = [FALLBACK_BETA]
            kwargs["fallbacks"] = "default"

        started = time.monotonic()
        ttft: float | None = None
        try:
            with self.client.beta.messages.stream(**kwargs) as stream:
                for event in stream:
                    if ttft is None and getattr(event, "type", None) == "content_block_delta":
                        ttft = time.monotonic()
                message = stream.get_final_message()
        except (anthropic.RateLimitError, anthropic.InternalServerError, anthropic.APIConnectionError) as exc:
            raise LlmUnavailable(type(exc).__name__) from exc
        except anthropic.APIStatusError as exc:
            if exc.status_code >= 500 or exc.status_code == 529:
                raise LlmUnavailable(f"status {exc.status_code}") from exc
            raise LlmRequestError(f"status {exc.status_code}") from exc

        latency_ms = int((time.monotonic() - started) * 1000)
        usage = self._usage(message)
        stop_reason = getattr(message, "stop_reason", None)
        refusal_category = None
        if stop_reason == "refusal":
            details = getattr(message, "stop_details", None)
            refusal_category = getattr(details, "category", None) or "unknown"
        text = None
        if stop_reason != "refusal":
            text = next((b.text for b in message.content if getattr(b, "type", None) == "text"), None)

        iterations = getattr(getattr(message, "usage", None), "iterations", None) or []
        fallback_used = any(getattr(it, "type", None) == "fallback_message" for it in iterations)
        result = LlmResult(
            text=text,
            content=list(message.content),
            stop_reason=stop_reason,
            refusal_category=refusal_category,
            model_requested=self.model,
            model_served=getattr(message, "model", self.model),
            fallback_used=fallback_used,
            usage=usage,
            cost_usd=self._cost(usage),
            latency_ms=latency_ms,
            ttft_ms=int((ttft - started) * 1000) if ttft else None,
            request_id=getattr(message, "_request_id", None),
            effort=call.effort,
            inference_geo=self.inference_geo,
        )
        # Metadata only: never the prompt, the sources or the answer.
        log.info(
            "llm_call",
            model_served=result.model_served,
            fallback_used=fallback_used,
            stop_reason=stop_reason,
            refusal_category=refusal_category,
            latency_ms=latency_ms,
            **usage,
        )
        return result

    @staticmethod
    def _usage(message: Any) -> dict[str, int]:
        u = getattr(message, "usage", None)
        return {
            "input_tokens": int(getattr(u, "input_tokens", 0) or 0),
            "output_tokens": int(getattr(u, "output_tokens", 0) or 0),
            "cache_read_tokens": int(getattr(u, "cache_read_input_tokens", 0) or 0),
            "cache_write_tokens": int(getattr(u, "cache_creation_input_tokens", 0) or 0),
        }

    def _cost(self, u: dict[str, int]) -> float:
        p = self.prices
        usd = (
            u["input_tokens"] * p.input
            + u["cache_write_tokens"] * p.cache_write_5m
            + u["cache_read_tokens"] * p.cache_read
            + u["output_tokens"] * p.output
        ) / 1_000_000
        return usd * (p.us_geo_multiplier if self.inference_geo == "us" else 1.0)
