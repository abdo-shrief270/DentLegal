"""The consultant route (PROJECT1_PLAN.md §5.5–5.9, story S2.5).

masked question → residual PII guard → hybrid retrieval → refusal gate (no LLM call below τ_answer)
→ Claude (ConsultAnswer) → validation → citation verification → one repair call → answer with verified citations.
"""

from __future__ import annotations

from dataclasses import dataclass
from typing import Any

from app.core.config import Settings
from app.llm.client import ClaudeClient
from app.llm.context import SourceEntry, build_sources, escape
from app.llm.prompts import CONSULT_PROMPT_VERSION, CONSULT_SYSTEM, REFUSAL_TEXT
from app.llm.runner import StructuredRunner
from app.llm.schemas import CONSULT_SCHEMA, SCHEMA_VERSION, ConsultAnswer
from app.masking.masker import Masker, PlaceholderState
from app.retrieval.hybrid import HybridRetriever, RetrievalResult
from app.verify.citations import Claim, Verification, verify


@dataclass
class HistoryTurn:
    question: str  # masked
    answer: str  # masked plain text of the earlier answer


def sources_json(retrieval: RetrievalResult, manifest: dict[str, SourceEntry]) -> list[dict[str, Any]]:
    by_chunk = {e.chunk_id: e for e in manifest.values()}
    return [
        {
            "ref": by_chunk[rc.chunk.id].ref if rc.chunk.id in by_chunk else None,
            "chunk_id": rc.chunk.id,
            "label": rc.chunk.label,
            "law": rc.chunk.source_title,
            "rerank_score": round(rc.rerank_score, 4),
            "dense_rank": rc.dense_rank,
            "sparse_rank": rc.sparse_rank,
            "explicit": rc.explicit,
            "used_in_prompt": rc.chunk.id in by_chunk,
        }
        for rc in retrieval.chunks
    ]


def para_json(text: str, legal_claim: bool, check: Any) -> dict[str, Any]:
    return {
        "text": text,
        "legal_claim": legal_claim,
        "unsupported": check.unsupported,
        "article_mismatch": check.article_mismatch,
        "citations": [c.to_json() for c in check.citations],
    }


class ConsultService:
    def __init__(self, retriever: HybridRetriever, claude: ClaudeClient, masker: Masker, settings: Settings) -> None:
        self._retriever = retriever
        self._runner = StructuredRunner(claude)
        self._masker = masker
        self._s = settings

    def answer(self, question: str, history: list[HistoryTurn]) -> dict[str, Any]:
        # Laravel masks before calling; this is the residual guard (layer 7): nothing a detector knows can leave.
        texts = [question, *(t.question for t in history), *(t.answer for t in history)]
        guarded = self._masker.mask(texts, state=PlaceholderState.continuing(texts))
        question = guarded.texts[0]
        n = len(history)
        history = [HistoryTurn(guarded.texts[1 + i], guarded.texts[1 + n + i]) for i in range(n)]

        retrieval = self._retriever.retrieve(question, self._s.retrieval.top_consult)
        run: dict[str, Any] = {
            "run_type": "consult",
            "prompt_version": CONSULT_PROMPT_VERSION,
            "schema_version": SCHEMA_VERSION,
            "retrieval": {**retrieval.params, "best_score": round(retrieval.best_score, 4), "explicit_refs": retrieval.explicit_refs},
            "residual_pii": guarded.residual_pii,
            "llm": [],
            "cost_usd": 0.0,
        }
        if not retrieval.answerable or not retrieval.chunks:
            return self._refusal("refused_retrieval", retrieval, {}, run)

        source_block, manifest = build_sources(retrieval.chunks)
        outcome = self._runner.run(
            system=CONSULT_SYSTEM,
            messages=[{"role": "user", "content": self._user_content(source_block, question, history)}],
            schema=CONSULT_SCHEMA,
            model=ConsultAnswer,
            effort=self._s.consult_effort,
            max_tokens=self._s.consult_max_tokens,
            verify=lambda a: verify([Claim(p.text, p.legal_claim, p.citations) for p in a.answer], dict(manifest)),
        )
        run.update(llm=outcome.calls, cost_usd=outcome.cost_usd, repaired=outcome.repaired)
        if outcome.refused or outcome.parsed is None or outcome.verification is None:
            run["refusal_category"] = outcome.refusal_category
            return self._refusal("refused_model", retrieval, manifest, run)

        run["citation_validity"] = round(outcome.verification.citation_validity, 4)
        return {
            "status": "needs_attention" if outcome.verification.needs_repair else "answered",
            "answer": self._answer_json(outcome.parsed, outcome.verification),
            "sources": sources_json(retrieval, manifest),
            "run": run,
        }

    @staticmethod
    def _user_content(source_block: str, question: str, history: list[HistoryTurn]) -> list[dict[str, str]]:
        parts = [{"type": "text", "text": f"<sources>\n{source_block}\n</sources>"}]
        if history:
            turns = "\n".join(f"<turn><q>{escape(t.question)}</q><a>{escape(t.answer)}</a></turn>" for t in history[-3:])
            parts.append({"type": "text", "text": f"<history>\n{turns}\n</history>"})
        parts.append({"type": "text", "text": f"<question>{escape(question)}</question>"})
        return parts

    @staticmethod
    def _answer_json(answer: ConsultAnswer, v: Verification) -> dict[str, Any]:
        return {
            "answerable": answer.answerable,
            "paragraphs": [para_json(p.text, p.legal_claim, c) for p, c in zip(answer.answer, v.paragraphs, strict=True)],
            "suggest_lawyer": answer.suggest_lawyer or not answer.answerable,
            "follow_ups": answer.follow_ups,
            "flags": v.flags,
        }

    @staticmethod
    def _refusal(status: str, retrieval: RetrievalResult, manifest: dict[str, SourceEntry], run: dict[str, Any]) -> dict[str, Any]:
        return {
            "status": status,
            "answer": {
                "answerable": False,
                "paragraphs": [
                    {"text": REFUSAL_TEXT, "legal_claim": False, "unsupported": False, "article_mismatch": False, "citations": []}
                ],
                "suggest_lawyer": True,
                "follow_ups": [],
                "flags": [],
            },
            "sources": sources_json(retrieval, manifest),
            "run": run,
        }
