"""Builds the long-lived pieces once per process: DB pool, models (loaded at start-up, §5.4), masker, Claude client."""

from __future__ import annotations

from dataclasses import dataclass
from typing import Any

import anthropic
from psycopg_pool import ConnectionPool

from app.core.config import Settings
from app.extract.ocr import Ocr, TesseractOcr
from app.llm.client import ClaudeClient
from app.masking.masker import Masker
from app.masking.ner import CamelNer, Ner, NoNer
from app.retrieval.models import BgeM3Embedder, BgeReranker, Embedder, HashingEmbedder, LexicalReranker, Reranker


@dataclass
class Container:
    settings: Settings
    pool: ConnectionPool[Any]
    embedder: Embedder
    reranker: Reranker
    masker: Masker
    claude: ClaudeClient
    ocr: Ocr | None  # None only when Tesseract is missing in a local/test environment

    def close(self) -> None:
        self.pool.close()


def build_container(settings: Settings, anthropic_client: Any | None = None) -> Container:
    settings.check_production_safety()
    embedder: Embedder = BgeM3Embedder() if settings.embedder == "bge-m3" else HashingEmbedder()
    reranker: Reranker = BgeReranker() if settings.reranker == "bge-reranker-v2-m3" else LexicalReranker()
    ner: Ner = CamelNer() if settings.ner == "camel" else NoNer()
    masker = Masker(ner, settings.masking_secret.get_secret_value().encode() or b"local-only-masking-key")
    pool: ConnectionPool[Any] = ConnectionPool(settings.database_url, min_size=1, max_size=8, open=True, kwargs={"autocommit": True})
    claude = ClaudeClient(
        client=anthropic_client or anthropic.Anthropic(max_retries=settings.anthropic_max_retries),
        model=settings.anthropic_model,
        inference_geo=settings.inference_geo,
        use_fallbacks=settings.use_fallbacks,
        prices=settings.prices,
    )
    ocr: Ocr | None
    try:
        ocr = TesseractOcr(settings.ocr_languages)
    except Exception:
        if not settings.is_test_like():
            raise
        ocr = None
    return Container(settings, pool, embedder, reranker, masker, claude, ocr)
