"""Citation verification (PROJECT1_PLAN.md §5.9, story S2.4).

Checks, per citation: the ref exists in this run's manifest (catches invented sources), and the quote really appears
in that source (exact after normalisation, else fuzzy ≥ 92). Per paragraph: a legal claim needs at least one valid
S citation, and an article the text names must be among the cited chunks. The run gate drives one repair call.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from typing import Literal

from rapidfuzz import fuzz

from app.llm.context import SourceEntry
from app.llm.schemas import Para
from app.text.normalize import search_text
from app.text.ordinals import find_article_refs

FUZZY_MIN = 92
MIN_QUOTE = 10
GATE = 0.9

Match = Literal["exact", "fuzzy", "none"]
Reason = Literal["ok", "ref_not_in_context", "quote_mismatch", "weak"]


@dataclass(frozen=True)
class CitationCheck:
    ref: str
    chunk_id: int | None
    label: str | None
    quote: str
    match: Match
    score: float
    reason: Reason

    @property
    def valid(self) -> bool:
        return self.reason == "ok"


@dataclass
class ParagraphCheck:
    citations: list[CitationCheck]
    unsupported: bool  # legal claim without a valid source citation → «غير مسند»
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

    def problems(self) -> list[str]:
        out = []
        for p_index, p in enumerate(self.paragraphs, start=1):
            for c in p.citations:
                if c.reason == "ref_not_in_context":
                    out.append(f"- paragraph {p_index}: {c.ref} is not one of the provided sources")
                elif c.reason in ("quote_mismatch", "weak"):
                    out.append(f"- paragraph {p_index}: the quote for {c.ref} does not appear in that source")
            if p.unsupported:
                out.append(f"- paragraph {p_index}: legal claim without a valid source citation")
            if p.article_mismatch:
                out.append(f"- paragraph {p_index}: names an article that none of its citations is from")
        return out


def check_quote(quote: str, source_text: str) -> tuple[Match, float]:
    q = search_text(quote)
    s = search_text(source_text)
    if q and q in s:
        return "exact", 100.0
    score = float(fuzz.partial_ratio(q, s)) if q else 0.0
    return ("fuzzy", score) if score >= FUZZY_MIN else ("none", score)


def verify(paragraphs: list[Para], manifest: dict[str, SourceEntry]) -> Verification:
    checks: list[ParagraphCheck] = []
    total = valid = invented = 0
    for para in paragraphs:
        cits: list[CitationCheck] = []
        for cite in para.citations:
            total += 1
            entry = manifest.get(cite.ref)
            if entry is None:
                invented += 1
                cits.append(CitationCheck(cite.ref, None, None, cite.quote, "none", 0.0, "ref_not_in_context"))
                continue
            match, score = check_quote(cite.quote, entry.display_text)
            reason: Reason = "weak" if len(search_text(cite.quote)) < MIN_QUOTE else ("ok" if match != "none" else "quote_mismatch")
            if reason == "ok":
                valid += 1
            cits.append(CitationCheck(cite.ref, entry.chunk_id, entry.label, cite.quote, match, score, reason))

        valid_sources = [manifest[c.ref] for c in cits if c.valid and c.ref.startswith("S")]
        unsupported = para.legal_claim and not valid_sources
        named = find_article_refs(para.text)
        cited_articles = {e.article_no for e in valid_sources} | {e.paragraph_ref for e in valid_sources}
        article_mismatch = (
            bool(named)
            and bool(valid_sources)
            and not any((r.paragraph_ref or r.article_no) in cited_articles or r.article_no in cited_articles for r in named)
        )
        checks.append(ParagraphCheck(cits, unsupported, article_mismatch))

    validity = valid / total if total else (1.0 if not any(p.legal_claim for p in paragraphs) else 0.0)
    flags = sorted({f for p in checks for f, on in (("unsupported", p.unsupported), ("article_mismatch", p.article_mismatch)) if on})
    return Verification(checks, validity, invented, flags)
