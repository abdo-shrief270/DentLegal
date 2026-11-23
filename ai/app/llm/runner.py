"""Shared structured-output loop for every route (PROJECT1_PLAN.md §5.8, §5.9, §5.12).

call → Pydantic validation (one retry on failure) → citation verification → one repair call when the gate fails,
keeping whichever attempt verifies better. Refusals stop the loop; the caller decides what the user sees.
"""

from __future__ import annotations

from collections.abc import Callable
from dataclasses import dataclass, field
from typing import Any

from pydantic import BaseModel, ValidationError

from app.core.errors import ServiceError
from app.llm.client import ClaudeClient, LlmCall, LlmResult
from app.llm.prompts import REPAIR_INSTRUCTION
from app.verify.citations import Verification


@dataclass
class Outcome[T: BaseModel]:
    parsed: T | None
    verification: Verification | None
    calls: list[dict[str, Any]] = field(default_factory=list)
    refusal_category: str | None = None
    repaired: bool = False

    @property
    def refused(self) -> bool:
        return self.refusal_category is not None

    @property
    def cost_usd(self) -> float:
        return round(sum(float(c["cost_usd"]) for c in self.calls), 6)


class StructuredRunner:
    def __init__(self, claude: ClaudeClient) -> None:
        self._claude = claude

    def run[T: BaseModel](
        self,
        *,
        system: str,
        messages: list[dict[str, Any]],
        schema: dict[str, Any],
        model: type[T],
        effort: str,
        max_tokens: int,
        verify: Callable[[T], Verification],
        labels: Callable[[T], list[str]] | None = None,
    ) -> Outcome[T]:
        outcome: Outcome[T] = Outcome(None, None)
        result, parsed = self._call_and_parse(LlmCall(system, messages, schema, effort, max_tokens), model, outcome)
        if parsed is None:
            if result.stop_reason == "refusal":
                outcome.refusal_category = result.refusal_category or "unknown"
                return outcome
            raise ServiceError(502, "ai_invalid_output", "the model output did not match the schema after one retry")

        verification = verify(parsed)
        outcome.parsed, outcome.verification = parsed, verification
        if not verification.needs_repair:
            return outcome

        problems = verification.problems(labels(parsed) if labels else None)
        repair_messages = [
            *messages,
            {"role": "assistant", "content": result.content},  # sent back unchanged (thinking blocks included)
            {"role": "user", "content": REPAIR_INSTRUCTION.format(problems="\n".join(problems))},
        ]
        _, repaired = self._call_and_parse(LlmCall(system, repair_messages, schema, effort, max_tokens), model, outcome)
        outcome.repaired = True
        if repaired is not None:
            repaired_verification = verify(repaired)
            if repaired_verification.citation_validity >= verification.citation_validity:
                outcome.parsed, outcome.verification = repaired, repaired_verification
        return outcome

    def _call_and_parse[T: BaseModel](self, call: LlmCall, model: type[T], outcome: Outcome[T]) -> tuple[LlmResult, T | None]:
        last: LlmResult | None = None
        for _ in range(2):  # one retry on schema/validation failure
            last = self._claude.run(call)
            outcome.calls.append(last.meta())
            if last.text is None:
                return last, None
            try:
                return last, model.model_validate_json(last.text)
            except ValidationError:
                continue
        if last is None:  # pragma: no cover - the loop always runs
            raise ServiceError(500, "internal", "no model call was made")
        return last, None
