from app.llm.context import Manifest, SourceEntry
from app.llm.schemas import Cite
from app.verify.citations import Claim, check_quote, verify

A19 = "لا يجوز إجراء أي عمل طبي لمريض إلا برضاه أو بموافقة من يمثله، ويجب أن يكون الرضا مبنيًا على شرح واضح."
A21 = "يجب على الممارس الصحي أن يحافظ على الأسرار التي علم بها عن طريق مهنته."
MANIFEST: Manifest = {
    "S1": SourceEntry("S1", 412, "المادة 19", "نظام مزاولة المهن الصحية", 19, None, A19),
    "S2": SourceEntry("S2", 415, "المادة 21", "نظام مزاولة المهن الصحية", 21, None, A21),
}


def para(text: str, *cites: tuple[str, str], legal: bool = True) -> Claim:
    return Claim(text, legal, [Cite(ref=r, quote=q) for r, q in cites])


def test_exact_and_fuzzy_quotes() -> None:
    assert check_quote("لا يجوز اجراء أي عمل طبي لمريض إلا برضاه", A19)[0] == "exact"  # hamza folded
    assert check_quote("لا يجوز إجراء اي عمل طبي للمريض إلا برضاه", A19)[0] == "fuzzy"
    assert check_quote("يجوز للطبيب إفشاء السر دون قيد", A19)[0] == "none"


def test_valid_answer_passes_the_gate() -> None:
    v = verify([para("لا بد من رضا المريض.", ("S1", "لا يجوز إجراء أي عمل طبي لمريض إلا برضاه"))], MANIFEST)
    assert v.citation_validity == 1.0
    assert not v.needs_repair
    assert v.paragraphs[0].citations[0].chunk_id == 412


def test_invented_sources_and_wrong_quotes_need_repair() -> None:
    v = verify(
        [
            para("قاعدة أولى.", ("S9", "نص غير موجود في أي مصدر من المصادر")),
            para("قاعدة ثانية.", ("S2", "يجوز للطبيب إفشاء الأسرار في كل الأحوال")),
        ],
        MANIFEST,
    )
    assert v.invented_refs == 1
    assert v.citation_validity == 0.0
    assert v.needs_repair
    assert all(p.unsupported for p in v.paragraphs)
    assert len(v.problems()) == 4


def test_article_named_in_text_must_be_cited() -> None:
    v = verify([para("بحسب المادة 21 يجب حفظ السر.", ("S1", "لا يجوز إجراء أي عمل طبي لمريض إلا برضاه"))], MANIFEST)
    assert v.paragraphs[0].article_mismatch
    assert v.flags == ["article_mismatch"]


def test_non_legal_paragraphs_need_no_citation() -> None:
    v = verify([para("ننصحك بمراجعة محامٍ.", legal=False)], MANIFEST)
    assert not v.paragraphs[0].unsupported
    assert not v.needs_repair
