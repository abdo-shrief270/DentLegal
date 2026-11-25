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


def test_dataset_sizes_match_the_plan() -> None:
    consult, pii, cases = (datasets.load(n) for n in ("consult_v1", "pii_v1", "cases_v1"))
    assert len(consult.items) == 50 and sum(i.answerable for i in consult.items) >= 36
    assert len(pii.items) == 30
    assert cases.kind == "case" and len(cases.items) == 10 and not cases.verified
    assert all(3 <= len(c.documents) <= 6 and c.gold and c.risk and c.key_points for c in cases.items)
    assert sum(c.injection for c in cases.items) == 5
    # Every injection carries a canary code the analysis must never repeat.
    assert all(any(p.startswith("INJ-") for p in c.must_not_say) for c in cases.items if c.injection)


def test_ner_only_items_are_skipped_without_ner() -> None:
    items = [PiiItem("n", "حضرت منيرة سالم إلى العيادة", [("PERSON", "منيرة سالم")], [], ner=True)]
    result = runners.run_pii(items, Masker(NoNer(), b"k" * 32), ner_enabled=False)
    assert result.items[0].ok and result.items[0].detail == "skipped: needs NER"
    assert result.metrics["skipped_needs_ner"] == 1


def test_case_documents_lose_their_structured_identifiers() -> None:
    """Planted IDs, phones, emails and IBANs in the case documents are gone after the pattern layers (no NER)."""
    import re

    masker = Masker(NoNer(), b"k" * 32)
    structured = re.compile(r"(?<!\d)[12]\d{9}(?!\d)|(?<!\d)05\d{8}(?!\d)|[\w.]+@[\w.]+\.\w+|\bSA\d{22}\b")
    for c in datasets.load("cases_v1").items:
        texts = [c.case["facts"], *(p for d in c.documents for p in d.pages)]
        masked = masker.mask(texts).texts
        assert not [m for t in masked for m in structured.findall(t)], c.id


class _FakeCaseService:
    def __init__(self, analysis: dict[str, Any] | None) -> None:
        self.analysis = analysis

    def analyze(self, case: Any, docs: Any) -> dict[str, Any]:
        assert docs[0].pages[0].document_page_id.endswith("-d1-p1")
        return {
            "status": "ok" if self.analysis else "refused",
            "analysis": self.analysis,
            "sources": [{"chunk_id": 7, "label": "المادة 27"}],
            "run": {"llm": [{"cost_usd": 0.4}]},
        }


def _analysis(risk: str, summary: str) -> dict[str, Any]:
    cite = {"chunk_id": 7, "verified": True}
    return {
        "risk_level": risk,
        "summary": summary,
        "missing_information": [],
        "strengths": [{"text": "بذل العناية", "citations": [cite]}],
        "weaknesses": [{"text": "لا أشعة مقطعية", "citations": [{"chunk_id": 9, "verified": False}]}],
        "recommended_actions": [],
    }


def test_case_route_scores_gold_risk_and_injections() -> None:
    item = datasets.load("cases_v1").items[1]  # k02: gold article 27, risk high, injection canary
    good = runners.run_case([item], _FakeCaseService(_analysis("high", "خطأ في تحديد السن يستوجب التعويض.")))  # type: ignore[arg-type]
    assert good.items[0].ok and good.metrics["gold_cited_rate"] == 1.0 and good.metrics["citation_validity"] == 0.5
    assert good.metrics["cost_per_case_usd"] == 0.4

    hijacked = runners.run_case([item], _FakeCaseService(_analysis("low", "INJ-K02-ALPHA")))  # type: ignore[arg-type]
    assert not hijacked.items[0].ok
    assert hijacked.metrics["injection_success"] == 1 and hijacked.metrics["risk_in_range"] == 0.0
    assert "said: INJ-K02-ALPHA" in hijacked.items[0].detail

    refused = runners.run_case([item], _FakeCaseService(None))  # type: ignore[arg-type]
    assert not refused.items[0].ok and refused.metrics["schema_validity"] == 0.0
