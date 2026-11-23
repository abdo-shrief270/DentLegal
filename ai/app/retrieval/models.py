"""Embedding and reranking models (PROJECT1_PLAN.md §5.4).

Production: BAAI/bge-m3 (dense, 1024-d) and BAAI/bge-reranker-v2-m3, both through FlagEmbedding, loaded once at start-up.
Tests and local work without the weights: `HashingEmbedder` and `LexicalReranker`, deterministic and dependency-free.
They are not quality stand-ins; Settings only allows them outside staging and production.
"""

from __future__ import annotations

import hashlib
import itertools
import math
import re
from typing import Any, Protocol

import numpy as np

from app.text.normalize import search_text

DIM = 1024


class Embedder(Protocol):
    name: str

    def embed(self, texts: list[str]) -> list[list[float]]: ...


class Reranker(Protocol):
    name: str

    def score(self, query: str, passages: list[str]) -> list[float]:
        """Relevance in [0, 1] per passage (sigmoid-normalised)."""
        ...


class BgeM3Embedder:
    name = "bge-m3"

    def __init__(self) -> None:
        from FlagEmbedding import BGEM3FlagModel

        self._model: Any = BGEM3FlagModel("BAAI/bge-m3", use_fp16=False)

    def embed(self, texts: list[str]) -> list[list[float]]:
        out = self._model.encode(texts, batch_size=16, max_length=1024, return_dense=True, return_sparse=False, return_colbert_vecs=False)
        vectors = np.asarray(out["dense_vecs"], dtype=np.float32)
        return _l2_normalise(vectors).tolist()  # type: ignore[no-any-return]


class BgeReranker:
    name = "bge-reranker-v2-m3"

    def __init__(self) -> None:
        from FlagEmbedding import FlagReranker

        self._model: Any = FlagReranker("BAAI/bge-reranker-v2-m3", use_fp16=False)

    def score(self, query: str, passages: list[str]) -> list[float]:
        if not passages:
            return []
        scores = self._model.compute_score([[query, p] for p in passages], normalize=True)
        return [float(s) for s in (scores if isinstance(scores, list) else [scores])]


_TOKEN = re.compile(r"[\w؀-ۿ]+")


def _tokens(text: str) -> list[str]:
    return [t for t in _TOKEN.findall(search_text(text)) if len(t) > 1]


class HashingEmbedder:
    """Feature hashing of words and word bigrams into 1024 dimensions. Similar wording → similar vectors."""

    name = "hashing"

    def embed(self, texts: list[str]) -> list[list[float]]:
        vectors = np.zeros((len(texts), DIM), dtype=np.float32)
        for row, text in enumerate(texts):
            toks = _tokens(text)
            for feature in toks + [f"{a} {b}" for a, b in itertools.pairwise(toks)]:
                digest = hashlib.blake2b(feature.encode(), digest_size=8).digest()
                index = int.from_bytes(digest[:4], "little") % DIM
                sign = 1.0 if digest[4] & 1 else -1.0
                vectors[row, index] += sign
        return _l2_normalise(vectors).tolist()  # type: ignore[no-any-return]


class LexicalReranker:
    """Share of query words present in the passage, squashed to [0, 1]."""

    name = "lexical"

    def score(self, query: str, passages: list[str]) -> list[float]:
        q = set(_tokens(query))
        if not q:
            return [0.0 for _ in passages]
        scores = []
        for p in passages:
            overlap = len(q & set(_tokens(p))) / len(q)
            scores.append(1 / (1 + math.exp(-8 * (overlap - 0.4))))
        return scores


def _l2_normalise(vectors: np.ndarray[Any, Any]) -> np.ndarray[Any, Any]:
    norms = np.linalg.norm(vectors, axis=1, keepdims=True)
    norms[norms == 0] = 1.0
    normalised: np.ndarray[Any, Any] = vectors / norms
    return normalised


def vector_literal(vector: list[float]) -> str:
    """pgvector text form, e.g. '[0.1,0.2]'."""
    return "[" + ",".join(f"{v:.7f}" for v in vector) + "]"
