"""The eval harness (PROJECT1_PLAN.md §5.14, S8.1): datasets load, metrics are computed right, reports render."""

from __future__ import annotations

from pathlib import Path
from typing import Any

import psycopg

from app.core.config import RetrievalParams
from app.eval import datasets, report, runners
from app.eval.datasets import ConsultItem, Gold, PiiItem
from app.masking.masker import Masker
from app.masking.ner import NoNer
from app.retrieval.hybrid import HybridRetriever
from app.retrieval.models import HashingEmbedder, LexicalReranker
from app.retrieval.repository import ChunkRepository


def test_the_repo_datasets_load_and_are_marked_as_drafts() -> None:
    consult = datasets.load("consult_v1")
    pii = datasets.load("pii_v1")
    assert consult.kind == "consult" and pii.kind == "pii"
    assert not consult.verified and not pii.verified  # until the lawyer verifies them (S8.2)
    assert len({i.id for i in consult.items}) == len(consult.items)
    assert all(i.gold for i in consult.items if i.answerable)
    assert all(not i.gold for i in consult.items if not i.answerable)


def test_gold_matches_articles_and_paragraphs() -> None:
    assert Gold("HPPL", article=21).matches("HPPL", 21, None)
    assert Gold("HPPL", article=19).matches("HPPL", 19, "19/1")  # a paragraph under the article counts
    assert not Gold("HPPL", paragraph="19/1").matches("HPPL", 19, None)
    assert not Gold("HPPL", article=21).matches("DENTAL_SVC_REG", 21, None)


def test_retrieve_route_scores_recall_and_the_refusal_gate(conn: psycopg.Connection[Any]) -> None:
    retriever = HybridRetriever(
        ChunkRepository(conn), HashingEmbedder(), LexicalReranker(), RetrievalParams(tau_keep=0.05, tau_answer=0.10)
    )
    items = [
        ConsultItem("a", "هل يجب الحصول على موافقة المريض المكتوبة قبل الإجراء؟", True, [Gold("HPPL", article=19)]),
        ConsultItem("b", "ماذا تنص المادة 41؟", True, [Gold("HPPL", article=41)]),
        ConsultItem("c", "ما سعر صرف الدولار اليوم؟", False),
    ]

    result = runners.run_retrieve(items, retriever, 8)

    assert result.metrics["recall@8"] == 1.0
    assert result.metrics["refusal_accuracy"] == 1.0
    assert [i.ok for i in result.items] == [True, True, True]


def test_pii_route_counts_leaks_and_over_masking() -> None:
    items = [
        PiiItem(
            "x", "هوية المريض 1098765432 وجواله 0551234567 وفق المادة 21.", [("ID", "1098765432"), ("PHONE", "0551234567")], ["المادة 21"]
        ),
        PiiItem("y", "الاسم المستعار نورة بلا تسمية", [("PERSON", "نورة")], []),  # NoNer can't find an unlabelled name
    ]

    result = runners.run_pii(items, Masker(NoNer(), b"test-secret"))

    assert result.metrics["recall_id"] == 1.0
    assert result.metrics["recall_person"] == 0.0
    assert result.metrics["keep_rate"] == 1.0
    assert result.items[1].detail == "leaked PERSON: نورة"


def test_report_shows_targets_and_failures(tmp_path: Path) -> None:
    ds = datasets.Dataset("pii_demo", "pii", False, [])
    res = runners.RunResult("pii", {"recall_all": 0.9, "keep_rate": 1.0}, [runners.ItemResult("p1", False, "leaked PHONE: 05…")])

    path = report.write(ds, res, ["stand-in models"], tmp_path)
    text = path.read_text(encoding="utf-8")

    assert "| recall_all | 0.9 | ≥ 1 · ❌ |" in text
    assert "| keep_rate | 1.0 | ≥ 1 · ✅ |" in text
    assert "- **p1**: leaked PHONE: 05…" in text
    assert "not yet lawyer-verified" in text
