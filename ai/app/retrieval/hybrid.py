"""Hybrid retrieval with a refusal gate (PROJECT1_PLAN.md §5.5).

explicit article refs + dense (pgvector) + full text (Arabic tsvector) [+ trigram when full text is thin]
→ Reciprocal Rank Fusion → parent articles pulled in → cross-encoder rerank → keep ≥ τ_keep → answerable if best ≥ τ_answer.
The caller passes masked text; this module never sees identifiers.
"""

from __future__ import annotations

from dataclasses import dataclass, field

from app.core.config import RetrievalParams
from app.retrieval.models import Embedder, Reranker
from app.retrieval.repository import ChunkRepository, ChunkRow
from app.text.normalize import search_text
from app.text.ordinals import find_article_refs


@dataclass(frozen=True)
class RetrievedChunk:
    chunk: ChunkRow
    rerank_score: float
    dense_rank: int | None
    sparse_rank: int | None
    explicit: bool


@dataclass
class RetrievalResult:
    chunks: list[RetrievedChunk]
    best_score: float
    answerable: bool
    explicit_refs: list[str] = field(default_factory=list)
    params: dict[str, float | int] = field(default_factory=dict)


def rrf(rankings: list[list[int]], k: int) -> dict[int, float]:
    """Reciprocal Rank Fusion: score(d) = Σ 1 / (k + rank_i(d)), ranks starting at 1."""
    scores: dict[int, float] = {}
    for ranking in rankings:
        for rank, doc in enumerate(ranking, start=1):
            scores[doc] = scores.get(doc, 0.0) + 1.0 / (k + rank)
    return scores


class HybridRetriever:
    def __init__(self, repo: ChunkRepository, embedder: Embedder, reranker: Reranker, params: RetrievalParams) -> None:
        self._repo = repo
        self._embedder = embedder
        self._reranker = reranker
        self._p = params

    def retrieve(self, masked_query: str, top_k: int, source_codes: list[str] | None = None) -> RetrievalResult:
        p = self._p
        q_search = search_text(masked_query)
        refs = find_article_refs(masked_query)

        explicit = self._repo.by_article(refs, source_codes)
        dense = self._repo.dense(self._embedder.embed([masked_query])[0], p.k_dense, p.hnsw_ef_search, source_codes)
        sparse = self._repo.sparse(q_search, p.k_sparse, source_codes)
        rankings = [dense, sparse]
        if len(sparse) < p.trigram_min_hits:
            rankings.append(self._repo.trigram(q_search, p.k_sparse, p.trigram_threshold, source_codes))

        fused = rrf(rankings, p.rrf_k)
        for chunk_id in explicit:
            fused[chunk_id] = fused.get(chunk_id, 0.0) + p.explicit_ref_boost
        # A strong regulation paragraph brings its article along (its text gives the rule the paragraph details).
        top_ids = [cid for cid, _ in sorted(fused.items(), key=lambda kv: kv[1], reverse=True)[: p.k_rerank]]
        parents = self._repo.parent_articles(top_ids)
        for regulation_id, article_id in parents.items():
            fused.setdefault(article_id, fused[regulation_id] * 0.9)

        candidates = [cid for cid, _ in sorted(fused.items(), key=lambda kv: (-kv[1], kv[0]))[: p.k_rerank]]
        rows = self._repo.fetch(candidates)
        candidates = [cid for cid in candidates if cid in rows]
        passages = [" › ".join([rows[c].source_title, *rows[c].heading_path]) + "\n" + rows[c].display_text for c in candidates]
        scores = self._reranker.score(masked_query, passages)

        dense_rank = {cid: i for i, cid in enumerate(dense, start=1)}
        sparse_rank = {cid: i for i, cid in enumerate(sparse, start=1)}
        explicit_set = set(explicit)
        ranked = sorted(zip(candidates, scores, strict=True), key=lambda cs: (-cs[1], cs[0]))
        score_of = dict(ranked)
        kept_ids = [cid for cid, score in ranked if score >= p.tau_keep or cid in explicit_set]  # a named article is always shown
        # A kept regulation paragraph keeps its parent article right after it (the article states the rule it details).
        with_parents: list[int] = []
        for cid in kept_ids:
            if cid not in with_parents:
                with_parents.append(cid)
            parent = parents.get(cid)
            if parent is not None and parent in rows and parent not in with_parents:
                with_parents.append(parent)
        kept = [
            RetrievedChunk(rows[cid], score_of[cid], dense_rank.get(cid), sparse_rank.get(cid), cid in explicit_set) for cid in with_parents
        ][:top_k]

        best = ranked[0][1] if ranked else 0.0
        return RetrievalResult(
            chunks=kept,
            best_score=best,
            answerable=best >= p.tau_answer or bool(explicit_set & {c.chunk.id for c in kept}),
            explicit_refs=[r.paragraph_ref or str(r.article_no) for r in refs],
            params={
                "k_dense": p.k_dense,
                "k_sparse": p.k_sparse,
                "k_rerank": p.k_rerank,
                "rrf_k": p.rrf_k,
                "tau_keep": p.tau_keep,
                "tau_answer": p.tau_answer,
            },
        )
