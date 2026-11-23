"""Knowledge-source ingestion (PROJECT1_PLAN.md §5.3, stories S1.3 and S1.5).

file → pages (text layer / repaired layer / OCR / Claude transcription) → structure → chunks → embeddings → one
transaction that replaces the version's chunks. Chunks are written with status `review`; Laravel owns the version
status and publishing (PublishSourceVersion makes them active and archives the previous version atomically).
"""

from __future__ import annotations

import io
from collections import Counter
from dataclasses import dataclass
from typing import Any

from PIL.Image import Image
from psycopg import Connection

from app.core.errors import ServiceError
from app.extract.ocr import Ocr
from app.extract.pages import Mode, PageText, Transcriber, extract_pdf
from app.ingest.chunker import Chunk, chunk
from app.ingest.parser import parse
from app.retrieval.models import Embedder, vector_literal

EDITABLE = ("uploaded", "processing", "review", "failed")
_VERSION = (
    "SELECT v.status, v.source_id, s.code FROM knowledge_source_versions v JOIN knowledge_sources s ON s.id = v.source_id WHERE v.id = %s"
)
_VERSION_FOR_UPDATE = _VERSION + " FOR UPDATE OF v"
EMBED_BATCH = 32
LOW_CONFIDENCE = 0.6


@dataclass(frozen=True)
class SourceMeta:
    version_id: str
    source_code: str
    source_title: str
    version_label: str


def ingest(
    conn: Connection[Any], meta: SourceMeta, pdf: bytes, mode: Mode, ocr: Ocr, embedder: Embedder, transcribe: Transcriber | None
) -> dict[str, Any]:
    _check_version(conn, meta, lock=False)  # fail fast before the slow part
    pages = extract_pdf(pdf, ocr, mode, transcribe)
    units = parse([(p.page_no, p.text) for p in pages], meta.source_title)
    chunks = chunk(units, meta.source_code, meta.version_label)
    if not chunks:
        raise ServiceError(422, "no_content", "no text could be extracted from the source")

    vectors: list[list[float]] = []
    for i in range(0, len(chunks), EMBED_BATCH):
        vectors += embedder.embed([c.embedding_text(meta.source_title) for c in chunks[i : i + EMBED_BATCH]])

    with conn.transaction():
        source_id = _check_version(conn, meta, lock=True)
        conn.execute("DELETE FROM knowledge_chunks WHERE version_id = %s", [meta.version_id])
        with conn.cursor() as cur:
            cur.executemany(
                "INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, article_no, paragraph_ref,"
                " page_from, page_to, display_text, search_text, token_count, heading_path, embedding)"
                " VALUES (%s, %s, 'review', %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s::vector)",
                [_row(meta.version_id, source_id, c, v) for c, v in zip(chunks, vectors, strict=True)],
            )
    return _report(pages, chunks)


def _check_version(conn: Connection[Any], meta: SourceMeta, lock: bool) -> str:
    row = conn.execute(_VERSION_FOR_UPDATE if lock else _VERSION, [meta.version_id]).fetchone()
    if row is None:
        raise ServiceError(404, "version_not_found", "unknown knowledge source version")
    status, source_id, code = row
    if code != meta.source_code:
        raise ServiceError(422, "source_mismatch", "the version belongs to another source")
    if status not in EDITABLE:
        raise ServiceError(409, "invalid_state", f"a version in status {status} can't be re-ingested")
    return str(source_id)


def _row(version_id: str, source_id: str, c: Chunk, vector: list[float]) -> list[object]:
    return [
        version_id,
        source_id,
        c.chunk_key,
        c.ordinal,
        c.chunk_type,
        c.article_no,
        c.paragraph_ref,
        c.page_from,
        c.page_to,
        c.display_text,
        c.search_text,
        c.token_count,
        c.heading_path,
        vector_literal(vector),
    ]


def _report(pages: list[PageText], chunks: list[Chunk]) -> dict[str, Any]:
    methods = Counter(p.method for p in pages)
    articles = sorted(
        {c.article_no for c in chunks if c.chunk_type == "article" and c.article_no and not c.chunk_key.split(":")[-1].startswith("P")}
    )
    gaps = [n for n in range(1, (articles[-1] if articles else 0) + 1) if n not in articles]
    dominant = methods.most_common(1)[0][0]
    return {
        "chunk_count": len(chunks),
        "chunks_by_type": dict(Counter(c.chunk_type for c in chunks)),
        "extraction_method": {"text_layer_repaired": "text_layer"}.get(dominant, dominant),
        "pages": [{"page_no": p.page_no, "method": p.method, "confidence": round(p.confidence, 3)} for p in pages],
        "warnings": {
            "missing_articles": gaps,
            "low_confidence_pages": [p.page_no for p in pages if p.confidence < LOW_CONFIDENCE],
        },
        "preview": [
            {
                "chunk_key": c.chunk_key,
                "chunk_type": c.chunk_type,
                "article_no": c.article_no,
                "paragraph_ref": c.paragraph_ref,
                "pages": [c.page_from, c.page_to],
                "text": c.display_text[:300],
            }
            for c in chunks[:5]
        ],
    }


def png_bytes(image: Image) -> bytes:
    buf = io.BytesIO()
    image.save(buf, format="PNG", optimize=True)
    return buf.getvalue()
