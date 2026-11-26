"""Error tracking (§8.6): what reaches Sentry/GlitchTip is scrubbed; nothing is sent without a DSN."""

from __future__ import annotations

import json
from typing import Any

import sentry_sdk
from fastapi import FastAPI
from fastapi.testclient import TestClient
from pydantic import SecretStr
from sentry_sdk.envelope import Envelope
from sentry_sdk.transport import Transport

from app.core.config import Settings
from app.core.error_tracking import init_error_tracking, mask, scrub


class CaptureTransport(Transport):
    def __init__(self) -> None:
        super().__init__()
        self.events: list[dict[str, Any]] = []

    def capture_envelope(self, envelope: Envelope) -> None:
        for item in envelope.items:
            if item.type == "event" and item.payload.json is not None:
                self.events.append(item.payload.json)


def test_mask_removes_identifiers_and_quoted_text() -> None:
    text = "failed for 1098765432 / 0551234567 / a.b@example.com: " + "يدّعي المريض استمرار التنميل في الشفة السفلية بعد الزراعة"
    masked = mask(text)
    for leaked in ("1098765432", "0551234567", "a.b@example.com", "التنميل"):
        assert leaked not in masked
    assert len(mask("x" * 1000)) <= 301


def test_scrub_keeps_metadata_only() -> None:
    event = {
        "request": {
            "method": "POST",
            "url": "http://ai:8000/v1/consult?q=secret",
            "data": {"question": "سؤال"},
            "headers": {"Authorization": "Bearer t"},
        },
        "user": {"ip_address": "10.0.0.1"},
        "breadcrumbs": {"values": [{"message": "x"}]},
        "extra": {"body": "x"},
        "contexts": {"runtime": {"name": "CPython"}, "trace": {"x": 1}},
        "exception": {
            "values": [
                {"type": "ValueError", "value": "bad id 1098765432", "stacktrace": {"frames": [{"function": "f", "vars": {"text": "سر"}}]}}
            ]
        },
    }
    out = json.dumps(scrub(event), ensure_ascii=False)
    for leaked in ("secret", "سؤال", "Bearer", "10.0.0.1", "1098765432", "سر", "breadcrumbs", '"trace"'):
        assert leaked not in out
    assert '"method": "POST"' in out and "/v1/consult" in out and "ValueError" in out


def test_no_dsn_means_no_client() -> None:
    assert init_error_tracking(Settings(env="testing", error_tracking_dsn=SecretStr(""))) is False


def test_an_unhandled_route_error_is_sent_scrubbed() -> None:
    transport = CaptureTransport()
    sentry_sdk.init(
        dsn="https://key@errors.example.test/7",
        transport=transport,
        before_send=scrub,
        send_default_pii=False,
        include_local_variables=False,
        max_breadcrumbs=0,
        traces_sample_rate=0.0,
    )  # type: ignore[arg-type]
    try:
        app = FastAPI()

        @app.post("/v1/boom")
        def boom(payload: dict[str, str]) -> None:
            phone = "055" + "1234567"  # built at run time: the stack carries this source line, not the value
            raise RuntimeError(f"could not process {payload['question']} for {phone}")

        TestClient(app, raise_server_exceptions=False).post("/v1/boom?x=1", json={"question": "هل يجوز الإفصاح عن ملف المريض"})
        sentry_sdk.flush()
    finally:
        sentry_sdk.init(dsn=None)

    assert len(transport.events) == 1
    sent = json.dumps(transport.events[0], ensure_ascii=False)
    assert "RuntimeError" in sent and "/v1/boom" in sent
    event = transport.events[0]
    # The stack legitimately carries source lines of this test (the request call); the event fields must not.
    assert event["request"] == {"method": "POST", "url": "http://testserver/v1/boom"}
    value = event["exception"]["values"][-1]["value"]
    assert "0551234567" not in value and "الإفصاح" not in value and "[TEXT]" in value
    assert '"vars"' not in sent and "user" not in event and "breadcrumbs" not in event
