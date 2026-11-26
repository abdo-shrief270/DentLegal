"""Error tracking (PROJECT1_PLAN.md §8.6, D14): unexpected exceptions go to Sentry or GlitchTip, scrubbed first.

What leaves the service: exception type, a masked and shortened message, the stack (files, lines, functions, source
lines of our code; never local variables), the route path, the environment and the release. `before_send` removes
request data, user, breadcrumbs and extra context, so no question, document text or prompt can reach the tracker.
"""

from __future__ import annotations

import re
from typing import Any

import sentry_sdk

from app.core.config import Settings
from app.masking.masker import PATTERNS

MESSAGE_MAX = 300
# Our own error messages are English; any Arabic in one is quoted content (a question, document text): replace it.
_ARABIC_RUN = re.compile(r"[\u0600-\u06FF]+(?:[\s،.]+[\u0600-\u06FF]+)*")


def mask(text: str) -> str:
    for kind, pattern in PATTERNS:
        text = pattern.sub(f"[{kind}]", text)
    text = _ARABIC_RUN.sub("[TEXT]", text)
    return text if len(text) <= MESSAGE_MAX else text[:MESSAGE_MAX] + "…"


def scrub(event: dict[str, Any], _hint: dict[str, Any] | None = None) -> dict[str, Any]:
    request = event.get("request") or {}
    event["request"] = {k: v for k, v in {"method": request.get("method"), "url": str(request.get("url", "")).split("?")[0]}.items() if v}
    for key in ("user", "breadcrumbs", "extra", "server_name"):
        event.pop(key, None)
    event["contexts"] = {k: v for k, v in (event.get("contexts") or {}).items() if k in ("runtime", "os")}
    for value in (event.get("exception") or {}).get("values", []):
        if isinstance(value.get("value"), str):
            value["value"] = mask(value["value"])
        for frame in (value.get("stacktrace") or {}).get("frames", []):
            frame.pop("vars", None)
    logentry = event.get("logentry") or {}
    for key in ("message", "formatted"):
        if isinstance(logentry.get(key), str):
            logentry[key] = mask(logentry[key])
        logentry.pop("params", None)
    if isinstance(event.get("message"), str):
        event["message"] = mask(event["message"])
    return event


def init_error_tracking(settings: Settings) -> bool:
    dsn = settings.error_tracking_dsn.get_secret_value()
    if not dsn:
        return False
    sentry_sdk.init(
        dsn=dsn,
        environment=settings.env,
        release=settings.release or None,
        send_default_pii=False,
        include_local_variables=False,
        max_breadcrumbs=0,
        traces_sample_rate=0.0,
        before_send=scrub,  # type: ignore[arg-type]
        server_name="ai-svc",
    )
    return True
