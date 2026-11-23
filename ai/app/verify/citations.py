"""Citation verification (PROJECT1_PLAN.md §5.9, story S2.4).

Per citation: the ref exists in this run's manifest (catches invented sources), and the quote really appears in that
source or document (exact after normalisation, else fuzzy ≥ 92). Document citations resolve to the page the quote is on.
Per paragraph: a legal claim needs a valid S citation (or, for analysis items, any valid citation), and an article the
text names must be among the cited chunks. The run gate (validity < 0.9 or any invented ref) drives one repair call.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import Literal

from rapidfuzz import fuzz

from app.llm.context import DocumentEntry, Manifest, SourceEntry
from app.llm.schemas import Cite
from app.text.normalize import search_text
from app.text.ordinals import find_article_refs

FUZZY_MIN = 92
MIN_QUOTE = 10
GATE = 0.9

Match = Literal["exact", "fuzzy", "none"]
Reason = Literal["ok", "ref_not_in_context", "quote_mismatch", "weak"]


@dataclass(frozen=True)
class Claim:
    """Anything that carries citations: an answer or draft paragraph, or an analysis item."""

    text: str
    legal_claim: bool
    citations: list[Cite]


@dataclass(frozen=True)
class CitationCheck:
    ref: str
    kind: Literal["knowledge", "case_document", "unknown"]
    chunk_id: int | None
    document_id: str | None
    document_page_id: str | None
    page_no: int | None
    label: str | None
    quote: str
    match: Match
    score: float
    reason: Reason

    @property
    def valid(self) -> bool:
        return self.reason == "ok"

    def to_json(self) -> dict[str, object]:
        out: dict[str, object] = {"ref": self.ref, "kind": self.kind, "label": self.label, "quote": self.quote, "verified": self.valid}
        if self.kind == "knowledge":
            out["chunk_id"] = self.chunk_id
        elif self.kind == "case_document":
            out.update(document_id=self.document_id, document_page_id=self.document_page_id, page=self.page_no)
        out["verification"] = {"match": self.match, "score": round(self.score, 1), "reason": self.reason}
        return out


@dataclass
class ParagraphCheck:
    citations: list[CitationCheck]
    unsupported: bool  # «غير مسند»
    article_mismatch: bool


@dataclass
class Verification:
    paragraphs: list[ParagraphCheck]
    citation_validity: float
    invented_refs: int
    flags: list[str] = field(default_factory=list)

    @property
    def needs_repair(self) -> bool:
        return self.citation_validity < GATE or self.invented_refs > 0

    def problems(self, labels: list[str] | None = None) -> list[str]:
        out = []
        for i, p in enumerate(self.paragraphs):
            where = labels[i] if labels else f"paragraph {i + 1}"
            for c in p.citations:
                if c.reason == "ref_not_in_context":
                    out.append(f"- {where}: {c.ref} is not one of the provided sources or documents")
                elif c.reason in ("quote_mismatch", "weak"):
                    out.append(f"- {where}: the quote for {c.ref} does not appear in that text")
            if p.unsupported:
                out.append(f"- {where}: claim without a valid citation")
            if p.article_mismatch:
                out.append(f"- {where}: names an article that none of its citations is from")
        return out


def check_quote(quote: str, text: str) -> tuple[Match, float]:
    q = search_text(quote)
    s = search_text(text)
    if q and q in s:
        return "exact", 100.0
    score = float(fuzz.partial_ratio(q, s)) if q else 0.0
    return ("fuzzy", score) if score >= FUZZY_MIN else ("none", score)


def _check(cite: Cite, manifest: Manifest) -> CitationCheck:
    entry = manifest.get(cite.ref)
    weak = len(search_text(cite.quote)) < MIN_QUOTE
    if isinstance(entry, SourceEntry):
        match, score = check_quote(cite.quote, entry.display_text)
        reason: Reason = "weak" if weak else ("ok" if match != "none" else "quote_mismatch")
        return CitationCheck(cite.ref, "knowledge", entry.chunk_id, None, None, None, entry.label, cite.quote, match, score, reason)
    if isinstance(entry, DocumentEntry):
        best: tuple[Match, float, int | None, str | None] = ("none", 0.0, None, None)
        for page in entry.pages:
            match, score = check_quote(cite.quote, page.text)
            if score > best[1]:
                best = (match, score, page.page_no, page.document_page_id)
        reason = "weak" if weak else ("ok" if best[0] != "none" else "quote_mismatch")
        return CitationCheck(
            cite.ref, "case_document", None, entry.document_id, best[3], best[2], entry.name, cite.quote, best[0], best[1], reason
        )
    return CitationCheck(cite.ref, "unknown", None, None, None, None, None, cite.quote, "none", 0.0, "ref_not_in_context")


def verify(claims: list[Claim], manifest: Manifest, any_citation_counts: bool = False) -> Verification:
    """`any_citation_counts`: analysis items may rest on documents alone; draft and answer claims need an S source."""
    checks: list[ParagraphCheck] = []
    total = valid = invented = 0
    for claim in claims:
        cits = [_check(c, manifest) for c in claim.citations]
        total += len(cits)
        valid += sum(c.valid for c in cits)
        invented += sum(c.reason == "ref_not_in_context" for c in cits)

        valid_sources = [e for c in cits if c.valid and isinstance(e := manifest.get(c.ref), SourceEntry)]
        supported = any(c.valid for c in cits) if any_citation_counts else bool(valid_sources)
        unsupported = claim.legal_claim and not supported

        named = find_article_refs(claim.text)
        cited = {e.article_no for e in valid_sources} | {e.paragraph_ref for e in valid_sources}
        article_mismatch = (
            bool(named)
            and bool(valid_sources)
            and not any((r.paragraph_ref or r.article_no) in cited or r.article_no in cited for r in named)
        )
        checks.append(ParagraphCheck(cits, unsupported, article_mismatch))

    validity = valid / total if total else (0.0 if any(c.legal_claim for c in claims) else 1.0)
    flags = sorted({name for p in checks for name, on in (("unsupported", p.unsupported), ("article_mismatch", p.article_mismatch)) if on})
    return Verification(checks, validity, invented, flags)
