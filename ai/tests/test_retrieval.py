from typing import Any

import psycopg

from app.core.config import RetrievalParams
from app.retrieval.hybrid import HybridRetriever, rrf
from app.retrieval.models import HashingEmbedder, LexicalReranker
from app.retrieval.repository import ChunkRepository


def retriever(conn: psycopg.Connection[Any], **overrides: Any) -> HybridRetriever:
    return HybridRetriever(ChunkRepository(conn), HashingEmbedder(), LexicalReranker(), RetrievalParams(**overrides))


def test_rrf_rewards_agreement_between_rankings() -> None:
    scores = rrf([[1, 2, 3], [2, 3, 1]], k=60)
    assert max(scores, key=lambda d: scores[d]) == 2


def test_consent_question_finds_the_consent_article_and_regulation(conn: psycopg.Connection[Any]) -> None:
    result = retriever(conn).retrieve("هل يجب الحصول على موافقة المريض المكتوبة قبل الإجراء؟", 8)
    keys = [rc.chunk.chunk_key for rc in result.chunks]
    assert result.answerable
    assert keys[0] in ("HPPL:1440:A19", "HPPL:1440:R19-1")
    assert {"HPPL:1440:A19", "HPPL:1440:R19-1"} <= set(keys)


def test_explicitly_named_article_is_always_returned(conn: psycopg.Connection[Any]) -> None:
    result = retriever(conn).retrieve("ماذا تنص المادة 41؟", 8)
    hit = next(rc for rc in result.chunks if rc.chunk.chunk_key == "HPPL:1440:A41")
    assert hit.explicit
    assert result.answerable
    assert result.explicit_refs == ["41"]


def test_archived_versions_are_never_retrieved(conn: psycopg.Connection[Any]) -> None:
    result = retriever(conn, tau_keep=0.0).retrieve("التعويض عن الخطأ المهني المادة 27", 15)
    keys = {rc.chunk.chunk_key for rc in result.chunks}
    assert "HPPL:1440:A27" in keys
    assert "HPPL:1426:A27" not in keys


def test_out_of_scope_questions_are_not_answerable(conn: psycopg.Connection[Any]) -> None:
    result = retriever(conn).retrieve("ما هو سعر الذهب اليوم في الأسواق؟", 8)
    assert not result.answerable
    assert result.best_score < RetrievalParams().tau_answer


def test_source_filter(conn: psycopg.Connection[Any]) -> None:
    result = retriever(conn, tau_keep=0.0).retrieve("تعقيم الأدوات في عيادة الأسنان", 8, ["DENTAL_SVC_REG"])
    assert {rc.chunk.source_code for rc in result.chunks} == {"DENTAL_SVC_REG"}
