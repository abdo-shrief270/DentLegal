"""Production safety of ai-svc's configuration (PROJECT1_PLAN.md S9.3)."""

from __future__ import annotations

import pytest

from app.core.config import Settings

PROD = {"env": "production", "embedder": "bge-m3", "reranker": "bge-reranker-v2-m3", "ner": "camel"}


def test_production_starts_with_real_models_and_strong_secrets() -> None:
    Settings(**PROD, service_token="s" * 40, masking_secret="m" * 40).check_production_safety()


@pytest.mark.parametrize(
    ("overrides", "message"),
    [
        ({"service_token": "change-me", "masking_secret": "m" * 40}, "AI_SERVICE_TOKEN"),
        ({"service_token": "s" * 40, "masking_secret": "short"}, "AI_MASKING_SECRET"),
        ({"service_token": "s" * 40, "masking_secret": "m" * 40, "ner": "none"}, "NER disabled"),
        ({"service_token": "s" * 40, "masking_secret": "m" * 40, "embedder": "hashing"}, "stand-in"),
    ],
)
def test_production_refuses_unsafe_settings(overrides: dict[str, str], message: str) -> None:
    with pytest.raises(RuntimeError, match=message):
        Settings(**{**PROD, **overrides}).check_production_safety()
