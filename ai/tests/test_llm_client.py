from types import SimpleNamespace
from typing import Any

import anthropic
import httpx2
import pytest

from app.core.config import Prices
from app.llm.client import FALLBACK_BETA, ClaudeClient, LlmCall, LlmRequestError, LlmUnavailable
from tests.conftest import FakeAnthropic, message

CALL = LlmCall(system="rules", messages=[{"role": "user", "content": "q"}], schema={"type": "object"}, effort="low", max_tokens=8000)


def client(responder: Any, geo: str = "us", fallbacks: bool = True) -> tuple[ClaudeClient, FakeAnthropic]:
    fake = FakeAnthropic(responder)
    return ClaudeClient(fake, "claude-opus-5-5", geo, fallbacks, Prices()), fake


def test_request_shape() -> None:
    c, fake = client(lambda kw, n: message({"ok": True}))
    c.run(CALL)
    kw = fake.calls[0]
    assert kw["model"] == "claude-opus-5-5"
    assert kw["inference_geo"] == "us"
    assert kw["betas"] == [FALLBACK_BETA] and kw["fallbacks"] == "default"
    assert kw["output_config"] == {"effort": "low", "format": {"type": "json_schema", "schema": {"type": "object"}}}
    assert kw["system"][0]["cache_control"] == {"type": "ephemeral"}
    assert "thinking" not in kw  # always adaptive on this model; effort is the control


def test_fallbacks_can_be_turned_off() -> None:
    c, fake = client(lambda kw, n: message({"ok": True}), fallbacks=False)
    c.run(CALL)
    assert "fallbacks" not in fake.calls[0] and "betas" not in fake.calls[0]


def test_usage_cost_and_metadata() -> None:
    c, _ = client(lambda kw, n: message({"ok": True}, fallback=True, model="claude-opus-4-8"))
    r = c.run(CALL)
    assert r.text == '{"ok": true}'
    assert r.fallback_used and r.model_served == "claude-opus-4-8"
    # (1200 × 4 + 2000 × 0.20 + 300 × 20) / 1e6 × 1.1
    assert r.cost_usd == pytest.approx(0.01232)
    assert r.meta()["anthropic_request_id"] == "req_test"
    assert r.ttft_ms is not None


def test_refusal_returns_no_text_and_the_category() -> None:
    c, _ = client(lambda kw, n: message(None, stop_reason="refusal", category="general_harms"))
    r = c.run(CALL)
    assert r.text is None and r.refusal_category == "general_harms"


def test_max_tokens_is_retried_once_with_more_room() -> None:
    c, fake = client(lambda kw, n: message({"ok": True}, stop_reason="max_tokens" if n == 1 else "end_turn"))
    r = c.run(CALL)
    assert [k["max_tokens"] for k in fake.calls] == [8000, 12000]
    assert r.attempts == 2 and r.stop_reason == "end_turn"


def _status_error(cls: type[anthropic.APIStatusError], code: int) -> anthropic.APIStatusError:
    request = httpx2.Request("POST", "https://api.anthropic.com/v1/messages")
    return cls("err", response=httpx2.Response(code, request=request), body=None)


def test_overload_and_bad_requests_map_to_typed_errors() -> None:
    def overloaded(kw: Any, n: int) -> SimpleNamespace:
        raise _status_error(anthropic.RateLimitError, 429)

    def bad(kw: Any, n: int) -> SimpleNamespace:
        raise _status_error(anthropic.BadRequestError, 400)

    with pytest.raises(LlmUnavailable):
        client(overloaded)[0].run(CALL)
    with pytest.raises(LlmRequestError):
        client(bad)[0].run(CALL)
