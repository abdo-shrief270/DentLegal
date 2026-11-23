"""Internal API for Laravel (PROJECT1_PLAN.md §4.5). Private network only, X-Service-Token on every call except health."""

from __future__ import annotations

from typing import Annotated, Any

from fastapi import APIRouter, Depends, Request
from pydantic import BaseModel, Field

from app.core.container import Container
from app.core.errors import ServiceError
from app.core.security import require_service_token
from app.llm.client import LlmRequestError, LlmUnavailable
from app.llm.consult import ConsultService, HistoryTurn
from app.masking.masker import DentistIdentity, MaskingError, PlaceholderState
from app.retrieval.hybrid import HybridRetriever
from app.retrieval.repository import ChunkRepository

router = APIRouter(prefix="/v1")
Auth = Depends(require_service_token)


def container(request: Request) -> Container:
    c: Container = request.app.state.container
    return c


C = Annotated[Container, Depends(container)]


@router.get("/health")
def health(c: C) -> dict[str, Any]:
    db_ok = True
    try:
        with c.pool.connection() as conn:
            conn.execute("SELECT 1")
    except Exception:
        db_ok = False
    return {
        "status": "ok" if db_ok else "degraded",
        "db": db_ok,
        "models": {"embedder": c.embedder.name, "reranker": c.reranker.name, "ner": c.settings.ner, "llm": c.settings.anthropic_model},
    }


class DentistIn(BaseModel):
    name: str | None = Field(default=None, max_length=200)
    license_no: str | None = Field(default=None, max_length=60)


class MaskIn(BaseModel):
    texts: list[str] = Field(max_length=200)
    allowlist: list[str] = Field(default_factory=list, max_length=200)
    dentist: DentistIn | None = None
    placeholder_state: dict[str, Any] | None = None


@router.post("/mask", dependencies=[Auth])
def mask(body: MaskIn, c: C) -> dict[str, Any]:
    try:
        result = c.masker.mask(
            body.texts,
            body.allowlist,
            DentistIdentity(body.dentist.name, body.dentist.license_no) if body.dentist else None,
            PlaceholderState.from_dict(body.placeholder_state),
        )
    except MaskingError as exc:
        raise ServiceError(503, "masking_failed", str(exc), retryable=True) from exc
    return {
        "masked": result.texts,
        "entity_counts": result.entity_counts,
        "placeholder_state": result.state.to_dict(),
        "residual_pii": result.residual_pii,
    }


class RetrieveFilters(BaseModel):
    source_codes: list[str] | None = None


class RetrieveIn(BaseModel):
    query: str = Field(min_length=2, max_length=4000)
    top_k: int = Field(default=8, ge=1, le=30)
    filters: RetrieveFilters = Field(default_factory=RetrieveFilters)


@router.post("/retrieve", dependencies=[Auth])
def retrieve(body: RetrieveIn, c: C) -> dict[str, Any]:
    """Also used by the admin chunk preview. The query must already be masked."""
    with c.pool.connection() as conn:
        retriever = HybridRetriever(ChunkRepository(conn), c.embedder, c.reranker, c.settings.retrieval)
        result = retriever.retrieve(body.query, body.top_k, body.filters.source_codes)
    return {
        "answerable": result.answerable,
        "best_score": round(result.best_score, 4),
        "chunks": [
            {
                "chunk_id": rc.chunk.id,
                "label": rc.chunk.label,
                "law": rc.chunk.source_title,
                "text": rc.chunk.display_text,
                "rerank_score": round(rc.rerank_score, 4),
                "dense_rank": rc.dense_rank,
                "sparse_rank": rc.sparse_rank,
                "explicit": rc.explicit,
            }
            for rc in result.chunks
        ],
    }


class TurnIn(BaseModel):
    question: str = Field(max_length=2000)
    answer: str = Field(max_length=4000)


class ConsultIn(BaseModel):
    question: str = Field(min_length=3, max_length=2000)
    history: list[TurnIn] = Field(default_factory=list, max_length=10)


@router.post("/consult", dependencies=[Auth])
def consult(body: ConsultIn, c: C) -> dict[str, Any]:
    """Masked question (+ last turns) → answer with verified citations, sources and run metadata for ai_runs."""
    try:
        with c.pool.connection() as conn:
            retriever = HybridRetriever(ChunkRepository(conn), c.embedder, c.reranker, c.settings.retrieval)
            service = ConsultService(retriever, c.claude, c.masker, c.settings)
            return service.answer(body.question, [HistoryTurn(t.question, t.answer) for t in body.history[-3:]])
    except MaskingError as exc:
        raise ServiceError(503, "masking_failed", str(exc), retryable=True) from exc
    except LlmUnavailable as exc:
        raise ServiceError(503, "ai_unavailable", str(exc), retryable=True) from exc
    except LlmRequestError as exc:
        raise ServiceError(502, "ai_request_rejected", str(exc)) from exc
