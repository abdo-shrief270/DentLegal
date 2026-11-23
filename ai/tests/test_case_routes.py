"""Case analysis and draft routes against the synthetic knowledge base, with a fake Claude that cites what it is given."""

from collections.abc import Iterator
from types import SimpleNamespace
from typing import Any

import pytest
from fastapi.testclient import TestClient

from app.core.config import Settings
from app.main import create_app
from tests.conftest import FakeAnthropic, docs_in, message, sources_in

AUTH = {"X-Service-Token": "test-token"}

CASE = {
    "case_type": "complaint",
    "claim_type": "compensation",
    "authority": "المديرية العامة للشؤون الصحية بمحافظة جدة",
    "complaint_no": "02871/1448",
    "received_on": "2026-10-19",
    "title": "تنميل في الشفة السفلية بعد زراعة سن",
    "procedure": "زراعة سن في موضع الضرس 36",
    "procedure_date": "2026-08-28",
    "patient_code": "P-7731",
    "facts": "يدّعي [PATIENT] استمرار التنميل بعد الإجراء ويطالب بالتعويض عن الخطأ المهني، ويقول إن الموافقة لم تُشرح له.",
}
DOCS = [
    {
        "document_id": "doc-1",
        "kind": "complaint_letter",
        "name": "خطاب الشكوى",
        "ocr_confidence": 0.91,
        "pages": [{"page_no": 1, "document_page_id": "page-1", "text": "يطالب المشتكي [PATIENT] بالتعويض عن التنميل المستمر بعد الزراعة."}],
    },
    {
        "document_id": "doc-2",
        "kind": "consent_form",
        "name": "إقرار الموافقة",
        "ocr_confidence": 0.95,
        "pages": [
            {
                "page_no": 1,
                "document_page_id": "page-2",
                "text": "أقر أنا المريض بأنه شُرحت لي طبيعة الإجراء ومخاطره بما فيها احتمال التنميل المؤقت أو الدائم.",
            },
            {"page_no": 2, "document_page_id": "page-3", "text": "وقّع المريض الإقرار بتاريخ 2026-08-28 قبل الإجراء."},
        ],
    },
]


def analysis_payload(kw: dict[str, Any], d_ref: str = "D2") -> dict[str, Any]:
    src, docs = sources_in(kw), docs_in(kw)
    return {
        "position_strength": 72,
        "risk_level": "Medium",
        "summary": "موقف الطبيب قابل للدفاع لوجود إقرار موافقة يذكر احتمال التنميل، مع حاجة لاستكمال سجلات المتابعة.",
        "strengths": [
            {"text": "وُقّع إقرار الموافقة قبل الإجراء ويذكر التنميل.", "citations": [{"ref": d_ref, "quote": docs.get("D2", "x" * 20)[:50]}]}
        ],
        "weaknesses": [
            {
                "text": "المطالبة بالتعويض قائمة إذا ثبت الخطأ.",
                "severity": "HIGH",
                "citations": [{"ref": "S1", "quote": src["S1"][:50]}] if "S1" in src else [],
            }
        ],
        "recommended_actions": [{"text": "إرفاق ملاحظات المتابعة.", "urgency": "before_hearing", "citations": []}],
        "missing_information": ["ملاحظات المتابعة بعد الإجراء"],
        "sources_sufficient": True,
    }


def draft_payload(kw: dict[str, Any], sections: tuple[str, ...] = ("facts", "defenses", "requests")) -> dict[str, Any]:
    src, docs = sources_in(kw), docs_in(kw)
    s1 = {"ref": "S1", "quote": src["S1"][:50]}
    d2 = {"ref": "D2", "quote": docs["D2"][:50]}
    para = {
        "facts": {"text": "راجع [PATIENT] العيادة ووُقّع إقرار الموافقة قبل الإجراء.", "legal_claim": False, "citations": [d2]},
        "defenses": {
            "text": "التزم {{DENTIST_NAME}} بما يقتضيه النظام من شرح المخاطر والحصول على الموافقة.",
            "legal_claim": True,
            "citations": [s1, d2],
        },
        "requests": {"text": "نلتمس حفظ الشكوى.", "legal_claim": False, "citations": []},
    }
    headings = {"facts": "أولًا: الوقائع", "defenses": "ثانيًا: الدفوع", "requests": "ثالثًا: الطلبات"}
    return {
        "title": "إفادة الممارس الصحي بشأن الشكوى رقم 02871/1448",
        "addressee": "سعادة مدير الشؤون القانونية",
        "salutation": "السلام عليكم ورحمة الله وبركاته، وبعد:",
        "intro": {"text": "أتقدم أنا {{DENTIST_NAME}} بإفادتي بشأن الشكوى.", "legal_claim": False, "citations": []},
        "sections": [{"kind": k, "heading": headings[k], "paragraphs": [para[k]]} for k in sections],
        "closing": "وتقبلوا وافر التحية والتقدير،",
        "attachments": ["إقرار الموافقة"],
        "unsupported_points": [],
    }


@pytest.fixture
def make_client(settings: Settings) -> Iterator[Any]:
    opened: list[TestClient] = []

    def make(responder: Any, token_count: int = 5000, **overrides: Any) -> tuple[TestClient, FakeAnthropic]:
        fake = FakeAnthropic(responder, token_count)
        # The word-overlap test reranker scores long case summaries low (related case ≈ 0.12, unrelated ≈ 0.04), so the
        # thresholds are scaled for it here. Production uses the plan's values with bge-reranker-v2-m3.
        retrieval = settings.retrieval.model_copy(update={"tau_keep": 0.05, "tau_answer": 0.10})
        tc = TestClient(create_app(settings.model_copy(update={"retrieval": retrieval, **overrides}), anthropic_client=fake))
        tc.__enter__()
        opened.append(tc)
        return tc, fake

    yield make
    for tc in opened:
        tc.__exit__(None, None, None)


def analyze(tc: TestClient, docs: list[dict[str, Any]] | None = None, case: dict[str, Any] | None = None) -> Any:
    return tc.post("/v1/case/analyze", headers=AUTH, json={"case": case or CASE, "documents": DOCS if docs is None else docs})


def test_analysis_with_verified_source_and_document_citations(make_client: Any) -> None:
    tc, fake = make_client(lambda kw, n: message(analysis_payload(kw)))
    body = analyze(tc).json()

    assert body["status"] == "ok"
    a = body["analysis"]
    assert a["risk_level"] == "medium" and a["sources_sufficient"]
    strength_cite = a["strengths"][0]["citations"][0]
    assert strength_cite["kind"] == "case_document" and strength_cite["verified"]
    assert strength_cite["document_id"] == "doc-2" and strength_cite["document_page_id"] == "page-2" and strength_cite["page"] == 1
    weakness_cite = a["weaknesses"][0]["citations"][0]
    assert weakness_cite["kind"] == "knowledge" and weakness_cite["chunk_id"] == body["source_chunk_ids"][0]
    assert a["weaknesses"][0]["severity"] == "high"
    assert body["run"]["run_type"] == "case_analysis" and body["run"]["citation_validity"] == 1.0

    sent = fake.calls[0]
    assert sent["output_config"]["effort"] == "high" and sent["max_tokens"] == 32000
    keys = {s["label"] for s in body["sources"]}
    assert {"المادة 19", "المادة 27"} <= keys  # consent (facts) and compensation (playbook)


def test_residual_identifiers_are_masked_and_numbering_continues(make_client: Any) -> None:
    docs = [
        {
            **DOCS[0],
            "pages": [{"page_no": 1, "document_page_id": "p1", "text": "رقم المشتكي [PHONE_1] ورقم آخر 0559876543 والهوية 1023456789"}],
        }
    ]
    tc, fake = make_client(lambda kw, n: message(analysis_payload(kw, d_ref="D1")))
    analyze(tc, docs)
    sent = str(fake.calls[0]["messages"])
    assert "0559876543" not in sent and "1023456789" not in sent
    assert "[PHONE_2]" in sent and "[ID_1]" in sent


def test_invented_document_reference_triggers_one_repair(make_client: Any) -> None:
    tc, fake = make_client(lambda kw, n: message(analysis_payload(kw, d_ref="D9" if n == 1 else "D2")))
    body = analyze(tc).json()
    assert len(fake.calls) == 2 and body["run"]["repaired"]
    assert body["status"] == "ok"


def test_unrelated_cases_are_marked_as_insufficiently_sourced(make_client: Any) -> None:
    case = {
        **CASE,
        "title": "خلاف تجاري حول عقد إيجار",
        "procedure": "عقد إيجار محل",
        "facts": "خلاف بين مستأجر ومؤجر حول قيمة الإيجار السنوي وموعد السداد.",
    }
    tc, fake = make_client(lambda kw, n: message(analysis_payload(kw)))
    body = analyze(tc, case=case).json()
    assert body["analysis"]["sources_sufficient"] is False  # retrieval overrules the model
    assert body["source_chunk_ids"] == []
    assert "little relevant regulatory text" in str(fake.calls[0]["messages"])


def test_model_refusal(make_client: Any) -> None:
    tc, _ = make_client(lambda kw, n: message(None, stop_reason="refusal", category="general_harms"))
    body = analyze(tc).json()
    assert body["status"] == "refused" and body["analysis"] is None
    assert body["run"]["refusal_category"] == "general_harms"


def test_oversized_bundles_are_refused_not_truncated(make_client: Any) -> None:
    tc, fake = make_client(lambda kw, n: message(analysis_payload(kw)), token_count=200_000, case_bundle_max_tokens=100)
    r = analyze(tc)
    assert r.status_code == 413 and r.json()["error"]["code"] == "bundle_too_large"
    assert fake.calls == [] and len(fake.counted) == 1


def _analysis_and_ids(make_client: Any) -> tuple[dict[str, Any], list[int]]:
    tc, _ = make_client(lambda kw, n: message(analysis_payload(kw)))
    body = analyze(tc).json()
    return body["analysis"]["raw"], body["source_chunk_ids"]


def test_draft_uses_the_analysis_sources_and_verifies_paragraphs(make_client: Any) -> None:
    raw, ids = _analysis_and_ids(make_client)
    tc, fake = make_client(lambda kw, n: message(draft_payload(kw)))
    body = tc.post("/v1/case/draft", headers=AUTH, json={"case": CASE, "documents": DOCS, "analysis": raw, "source_chunk_ids": ids}).json()

    assert body["status"] == "ok"
    d = body["draft"]
    assert [s["kind"] for s in d["sections"]] == ["facts", "defenses", "requests"]
    defense = d["sections"][1]["paragraphs"][0]
    assert defense["legal_claim"] and not defense["unsupported"]
    assert defense["citations"][0]["chunk_id"] == ids[0]  # S1 is the analysis's first source
    assert "{{DENTIST_NAME}}" in d["intro"]["text"]
    assert "<analysis>" in str(fake.calls[0]["messages"]) and fake.calls[0]["output_config"]["effort"] == "high"


def test_legal_claims_resting_only_on_documents_are_flagged(make_client: Any) -> None:
    raw, ids = _analysis_and_ids(make_client)

    def responder(kw: dict[str, Any], n: int) -> SimpleNamespace:
        payload = draft_payload(kw)
        payload["sections"][1]["paragraphs"][0]["citations"] = [
            c for c in payload["sections"][1]["paragraphs"][0]["citations"] if c["ref"] == "D2"
        ]
        return message(payload)

    tc, _ = make_client(responder)
    body = tc.post("/v1/case/draft", headers=AUTH, json={"case": CASE, "documents": DOCS, "analysis": raw, "source_chunk_ids": ids}).json()
    defense = body["draft"]["sections"][1]["paragraphs"][0]
    assert defense["unsupported"] and "unsupported" in body["draft"]["flags"]


def test_sections_out_of_order_are_retried_once(make_client: Any) -> None:
    raw, ids = _analysis_and_ids(make_client)
    tc, fake = make_client(
        lambda kw, n: message(draft_payload(kw, ("defenses", "facts", "requests") if n == 1 else ("facts", "defenses", "requests")))
    )
    body = tc.post("/v1/case/draft", headers=AUTH, json={"case": CASE, "documents": DOCS, "analysis": raw, "source_chunk_ids": ids}).json()
    assert len(fake.calls) == 2 and body["status"] == "ok"


def test_draft_with_vanished_sources_asks_for_a_new_analysis(make_client: Any) -> None:
    raw, _ = _analysis_and_ids(make_client)
    tc, _ = make_client(lambda kw, n: message(draft_payload(kw)))
    r = tc.post("/v1/case/draft", headers=AUTH, json={"case": CASE, "documents": DOCS, "analysis": raw, "source_chunk_ids": [999999]})
    assert r.status_code == 409 and r.json()["error"]["code"] == "sources_missing"


def test_draft_without_sources_flags_every_legal_claim(make_client: Any) -> None:
    raw, _ = _analysis_and_ids(make_client)

    def responder(kw: dict[str, Any], n: int) -> SimpleNamespace:
        assert sources_in(kw) == {}  # no <source> blocks were sent
        d2 = {"ref": "D2", "quote": docs_in(kw)["D2"][:50]}
        return message(
            {
                "title": "إفادة الممارس الصحي",
                "addressee": "سعادة مدير الشؤون القانونية",
                "salutation": "السلام عليكم،",
                "intro": {"text": "أتقدم بإفادتي.", "legal_claim": False, "citations": []},
                "sections": [
                    {
                        "kind": "facts",
                        "heading": "أولًا: الوقائع",
                        "paragraphs": [{"text": "وُقّع الإقرار.", "legal_claim": False, "citations": [d2]}],
                    },
                    {
                        "kind": "defenses",
                        "heading": "ثانيًا: الدفوع",
                        "paragraphs": [{"text": "التزمت بالنظام.", "legal_claim": True, "citations": []}],
                    },
                    {
                        "kind": "requests",
                        "heading": "ثالثًا: الطلبات",
                        "paragraphs": [{"text": "حفظ الشكوى.", "legal_claim": False, "citations": []}],
                    },
                ],
                "closing": "وتقبلوا التحية،",
                "attachments": [],
                "unsupported_points": [],
            }
        )

    tc, fake = make_client(responder)
    body = tc.post("/v1/case/draft", headers=AUTH, json={"case": CASE, "documents": DOCS, "analysis": raw, "source_chunk_ids": []}).json()
    assert body["draft"]["sections"][1]["paragraphs"][0]["unsupported"]
    assert body["sources"] == []
    # The only citation is valid, so the gate passes and no repair call is made (§5.9): the uncited claim is flagged instead.
    assert len(fake.calls) == 1
    assert body["draft"]["flags"] == ["unsupported"]
