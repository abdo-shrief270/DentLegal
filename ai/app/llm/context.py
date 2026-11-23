"""Builds the source blocks and the run manifest (S1 → chunk id) that citation verification checks against."""

from __future__ import annotations

from dataclasses import dataclass

from app.retrieval.hybrid import RetrievedChunk


@dataclass(frozen=True)
class SourceEntry:
    ref: str  # S1
    chunk_id: int
    label: str  # المادة 19
    law: str
    article_no: int | None
    paragraph_ref: str | None
    display_text: str


def escape(text: str) -> str:
    """User and document text can't close or open tags (PROJECT1_PLAN.md §5.11)."""
    return text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


def build_sources(chunks: list[RetrievedChunk]) -> tuple[str, dict[str, SourceEntry]]:
    manifest: dict[str, SourceEntry] = {}
    blocks: list[str] = []
    for i, rc in enumerate(chunks, start=1):
        c = rc.chunk
        entry = SourceEntry(f"S{i}", c.id, c.label, c.source_title, c.article_no, c.paragraph_ref, c.display_text)
        manifest[entry.ref] = entry
        edition = f' edition="{escape(c.edition)}"' if c.edition else ""
        attrs = f'id="{entry.ref}" law="{escape(c.source_title)}" ref="{escape(c.label)}"{edition}'
        blocks.append(f"<source {attrs}>\n{escape(c.display_text)}\n</source>")
    return "\n".join(blocks), manifest
