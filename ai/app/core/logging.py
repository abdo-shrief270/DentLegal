"""JSON logs via structlog. Content fields are redacted: logs never hold questions, documents, prompts or answers (§5.13)."""

from __future__ import annotations

import logging
from collections.abc import MutableMapping
from typing import Any

import structlog

# Keys that may carry user or case text. Any key containing one of these words is replaced.
SENSITIVE_KEY_PARTS = ("text", "query", "question", "prompt", "content", "answer", "document", "facts", "quote", "token", "secret", "key")
REDACTED = "[redacted]"


def redact(_logger: Any, _method: str, event_dict: MutableMapping[str, Any]) -> MutableMapping[str, Any]:
    for key in list(event_dict.keys()):
        if key == "event":
            continue
        lowered = key.lower()
        if any(part in lowered for part in SENSITIVE_KEY_PARTS):
            event_dict[key] = REDACTED
    return event_dict


def configure_logging(level: str = "INFO") -> None:
    logging.basicConfig(format="%(message)s", level=level)
    structlog.configure(
        processors=[
            structlog.contextvars.merge_contextvars,
            structlog.processors.add_log_level,
            structlog.processors.TimeStamper(fmt="iso", utc=True),
            redact,
            structlog.processors.JSONRenderer(ensure_ascii=False),
        ],
        logger_factory=structlog.stdlib.LoggerFactory(),
        cache_logger_on_first_use=True,
    )


def get_logger(name: str) -> structlog.stdlib.BoundLogger:
    logger: structlog.stdlib.BoundLogger = structlog.get_logger(name)
    return logger
