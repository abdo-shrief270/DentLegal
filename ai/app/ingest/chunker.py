"""Units → chunks of about 250-450 tokens (PROJECT1_PLAN.md §5.3 step 5).

Long units are split at line boundaries; each continuation repeats the last 20 words of the previous piece as context
(never across units, so never across articles). The text embedded is prefixed with the heading path; display_text stays
clean for quoting.
"""

from __future__ import annotations

import itertools
from dataclasses import dataclass

from app.ingest.parser import Unit
from app.text.normalize import search_text

TOKENS_PER_WORD = 1.8  # Arabic, bge-m3 / Claude tokenisers, measured roughly on the supplied sources
MAX_WORDS = 250  # ≈ 450 tokens
TARGET_WORDS = 190  # ≈ 340 tokens
OVERLAP_WORDS = 20


@dataclass(frozen=True)
class Chunk:
    chunk_key: str
    ordinal: int
    chunk_type: str
    article_no: int | None
    paragraph_ref: str | None
    heading_path: list[str]
    page_from: int
    page_to: int
    display_text: str
    search_text: str
    token_count: int

    def embedding_text(self, source_title: str) -> str:
        return " › ".join([source_title, *self.heading_path]) + "\n" + self.display_text


def _split(unit: Unit) -> list[str]:
    lines = [line for line in unit.lines if line.strip()]
    pieces: list[list[str]] = [[]]
    for line in lines:
        words_now = sum(len(x.split()) for x in pieces[-1])
        if pieces[-1] and words_now + len(line.split()) > MAX_WORDS and words_now >= TARGET_WORDS // 2:
            pieces.append([])
        pieces[-1].append(line)
    texts = ["\n".join(p) for p in pieces if p]
    out = texts[:1]
    for prev, cur in itertools.pairwise(texts):
        context = " ".join(prev.split()[-OVERLAP_WORDS:])
        out.append(f"… {context}\n{cur}")
    return out


def chunk(units: list[Unit], source_code: str, version_label: str) -> list[Chunk]:
    chunks: list[Chunk] = []
    used: set[str] = set()
    section_no = 0
    for unit in units:
        if unit.kind == "section":
            section_no += 1
        base = {
            "article": f"A{unit.article_no}",
            "regulation": f"R{(unit.paragraph_ref or '').replace('/', '-')}",
            "definition": f"A1-D{len([k for k in used if '-D' in k]) + 1}",
            "section": f"S{section_no}",
            "form": f"F{len([k for k in used if ':F' in k or '-F' in k]) + 1}",
        }[unit.kind]
        if unit.part > 1:
            base = f"P{unit.part}-{base}"
        pieces = _split(unit)
        for i, text in enumerate(pieces, start=1):
            key = f"{source_code}:{version_label}:{base}" + (f"-p{i}" if len(pieces) > 1 else "")
            while key in used:  # e.g. the same article heading repeated in a source
                key += "+"
            used.add(key)
            chunk_type = "definition" if unit.kind == "definition" else unit.kind
            chunks.append(
                Chunk(
                    chunk_key=key,
                    ordinal=len(chunks) + 1,
                    chunk_type=chunk_type,
                    article_no=unit.article_no,
                    paragraph_ref=unit.paragraph_ref,
                    heading_path=[*unit.heading_path, *([unit.term] if unit.term else [])],
                    page_from=unit.page_from,
                    page_to=unit.page_to,
                    display_text=text,
                    search_text=search_text(text),
                    token_count=min(32000, int(len(text.split()) * TOKENS_PER_WORD)),
                )
            )
    return chunks
