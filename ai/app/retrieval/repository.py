"""SQL over knowledge_chunks (schema owned by the Laravel migrations). Only active chunks are ever retrieved.

Queries are assembled from constant fragments only (`_source_filter` returns a fixed string); every value is a bound
parameter, which is why the S608 warnings below are suppressed.
"""

from __future__ import annotations

from dataclasses import dataclass

from psycopg import Connection
from psycopg.rows import dict_row

from app.retrieval.models import vector_literal
from app.text.ordinals import ArticleRef


@dataclass(frozen=True)
class ChunkRow:
    id: int
    source_code: str
    source_title: str
    edition: str | None
    chunk_key: str
    chunk_type: str
    article_no: int | None
    paragraph_ref: str | None
    heading_path: list[str]
    display_text: str
    search_text: str

    @property
    def label(self) -> str:
        if self.chunk_type == "regulation" and self.paragraph_ref:
            return f"اللائحة {self.paragraph_ref}"
        if self.article_no is not None:
            return f"المادة {self.article_no}"
        return self.chunk_key


def _source_filter(codes: list[str] | None) -> tuple[str, list[object]]:
    if not codes:
        return "", []
    return " AND c.source_id IN (SELECT id FROM knowledge_sources WHERE code = ANY(%s))", [codes]


class ChunkRepository:
    def __init__(self, conn: Connection) -> None:
        self._conn = conn

    def dense(self, embedding: list[float], k: int, ef_search: int, source_codes: list[str] | None = None) -> list[int]:
        where, params = _source_filter(source_codes)
        vec = vector_literal(embedding)
        with self._conn.transaction(), self._conn.cursor() as cur:
            cur.execute(f"SET LOCAL hnsw.ef_search = {int(ef_search)}")
            cur.execute(
                f"SELECT c.id FROM knowledge_chunks c WHERE c.status = 'active'{where} ORDER BY c.embedding <=> %s::vector LIMIT %s",  # noqa: S608
                [*params, vec, k],
            )
            return [r[0] for r in cur.fetchall()]

    def sparse(self, query_search_text: str, k: int, source_codes: list[str] | None = None) -> list[int]:
        where, params = _source_filter(source_codes)
        with self._conn.cursor() as cur:
            cur.execute(
                "SELECT c.id FROM knowledge_chunks c, websearch_to_tsquery('arabic', %s) q"  # noqa: S608
                f" WHERE c.status = 'active' AND c.tsv @@ q{where} ORDER BY ts_rank_cd(c.tsv, q) DESC, c.id LIMIT %s",
                [query_search_text, *params, k],
            )
            hits = [r[0] for r in cur.fetchall()]
            if hits:
                return hits
            # Arabic stemming can be strict: fall back to matching any of the words.
            words = " or ".join(w for w in query_search_text.split() if len(w) > 2)
            if not words:
                return []
            cur.execute(
                "SELECT c.id FROM knowledge_chunks c, websearch_to_tsquery('arabic', %s) q"  # noqa: S608
                f" WHERE c.status = 'active' AND c.tsv @@ q{where} ORDER BY ts_rank_cd(c.tsv, q) DESC, c.id LIMIT %s",
                [words, *params, k],
            )
            return [r[0] for r in cur.fetchall()]

    def trigram(self, query_search_text: str, k: int, threshold: float, source_codes: list[str] | None = None) -> list[int]:
        where, params = _source_filter(source_codes)
        with self._conn.cursor() as cur:
            cur.execute(
                "SELECT c.id FROM knowledge_chunks c"  # noqa: S608
                f" WHERE c.status = 'active' AND word_similarity(%s, c.search_text) >= %s{where}"
                " ORDER BY word_similarity(%s, c.search_text) DESC, c.id LIMIT %s",
                [query_search_text, threshold, *params, query_search_text, k],
            )
            return [r[0] for r in cur.fetchall()]

    def by_article(self, refs: list[ArticleRef], source_codes: list[str] | None = None) -> list[int]:
        if not refs:
            return []
        where, params = _source_filter(source_codes)
        articles = [r.article_no for r in refs if r.paragraph_ref is None]
        paragraphs = [r.paragraph_ref for r in refs if r.paragraph_ref is not None]
        with self._conn.cursor() as cur:
            cur.execute(
                "SELECT c.id FROM knowledge_chunks c WHERE c.status = 'active'"  # noqa: S608
                " AND ((c.chunk_type = 'article' AND c.article_no = ANY(%s)) OR c.paragraph_ref = ANY(%s))"
                f"{where} ORDER BY c.article_no, c.ordinal",
                [articles, paragraphs, *params],
            )
            return [r[0] for r in cur.fetchall()]

    def parent_articles(self, chunk_ids: list[int]) -> dict[int, int]:
        """regulation chunk id → id of the article chunk it belongs to (same source and article number)."""
        if not chunk_ids:
            return {}
        with self._conn.cursor() as cur:
            cur.execute(
                "SELECT r.id, a.id FROM knowledge_chunks r JOIN knowledge_chunks a"
                " ON a.source_id = r.source_id AND a.version_id = r.version_id AND a.article_no = r.article_no AND a.chunk_type = 'article'"
                " WHERE r.id = ANY(%s) AND r.chunk_type = 'regulation' AND a.status = 'active'",
                [chunk_ids],
            )
            return {r[0]: r[1] for r in cur.fetchall()}

    def fetch(self, chunk_ids: list[int]) -> dict[int, ChunkRow]:
        if not chunk_ids:
            return {}
        with self._conn.cursor(row_factory=dict_row) as cur:
            cur.execute(
                "SELECT c.id, s.code AS source_code, s.title_ar AS source_title, v.edition, c.chunk_key, c.chunk_type,"
                " c.article_no, c.paragraph_ref, c.heading_path, c.display_text, c.search_text"
                " FROM knowledge_chunks c JOIN knowledge_source_versions v ON v.id = c.version_id"
                " JOIN knowledge_sources s ON s.id = c.source_id WHERE c.id = ANY(%s)",
                [chunk_ids],
            )
            return {row["id"]: ChunkRow(**row) for row in cur.fetchall()}
