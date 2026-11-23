"""Builds the prompt blocks and the run manifest that citation verification checks against.

S refs → knowledge chunks, D refs → case documents (and, after verification, the page the quote is on).
All user and document text is escaped so it can't close or open tags (PROJECT1_PLAN.md §5.11).
"""

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


@dataclass(frozen=True)
class DocPage:
    page_no: int
    document_page_id: str
    text: str  # masked


@dataclass(frozen=True)
class DocumentEntry:
    ref: str  # D1
    document_id: str
    kind: str
    name: str
    pages: tuple[DocPage, ...]
    ocr_confidence: float | None = None

    @property
    def display_text(self) -> str:
        return "\n".join(p.text for p in self.pages)


Manifest = dict[str, SourceEntry | DocumentEntry]


def escape(text: str) -> str:
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


def build_documents(documents: list[DocumentEntry]) -> str:
    blocks = []
    for d in documents:
        pages = f"{d.pages[0].page_no}-{d.pages[-1].page_no}" if d.pages else "0"
        conf = f' ocr_confidence="{d.ocr_confidence:.2f}"' if d.ocr_confidence is not None else ""
        body = "\n".join(f"[صفحة {p.page_no}]\n{escape(p.text)}" for p in d.pages)
        blocks.append(
            f'<case_document id="{d.ref}" kind="{escape(d.kind)}" name="{escape(d.name)}" pages="{pages}"{conf}>\n{body}\n</case_document>'
        )
    return "\n".join(blocks)
