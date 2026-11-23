"""The consultant route (PROJECT1_PLAN.md §5.5–5.9, story S2.5).

masked question → residual PII guard → hybrid retrieval → refusal gate (no LLM call below τ_answer)
→ Claude (structured ConsultAnswer) → Pydantic validation (one retry) → citation verification (one repair call)
→ answer with every citation mapped to a chunk id and its verification.
"""

from __future__ import annotations

from dataclasses import dataclass
from typing import Any

from pydantic import ValidationError

from app.core.config import Settings
from app.core.errors import ServiceError
from app.llm.client import ClaudeClient, LlmCall, LlmResult
from app.llm.context import SourceEntry, build_sources, escape
from app.llm.prompts import CONSULT_PROMPT_VERSION, CONSULT_SYSTEM, REFUSAL_TEXT, REPAIR_INSTRUCTION
from app.llm.schemas import CONSULT_SCHEMA, SCHEMA_VERSION, ConsultAnswer, Para
from app.masking.masker import Masker
from app.retrieval.hybrid import HybridRetriever, RetrievalResult
from app.verify.citations import Verification, verify


@dataclass
class HistoryTurn:
    question: str  # masked
    answer: str  # masked plain text of the earlier answer


class ConsultService:
    def __init__(self, retriever: HybridRetriever, claude: ClaudeClient, masker: Masker, settings: Settings) -> None:
        self._retriever = retriever
        self._claude = claude
        self._masker = masker
        self._s = settings

    def answer(self, question: str, history: list[HistoryTurn]) -> dict[str, Any]:
        # Laravel masks before calling; this is the residual guard (layer 7) so nothing a detector knows can leave.
        guarded = self._masker.mask([question, *(t.question for t in history), *(t.answer for t in history)])
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
        }
        if not retrieval.answerable or not retrieval.chunks:
            return self._refusal("refused_retrieval", retrieval, {}, run)

        source_block, manifest = build_sources(retrieval.chunks)
        messages: list[dict[str, Any]] = [{"role": "user", "content": self._user_content(source_block, question, history)}]
        result, parsed = self._call_and_parse(messages)
        run["llm"] = [result.meta()]
        if parsed is None:
            if result.stop_reason == "refusal":
                return self._refusal("refused_model", retrieval, manifest, run)
            raise ServiceError(502, "ai_invalid_output", "the model output did not match the schema after one retry")

        verification = verify(parsed.answer, manifest)
        if verification.needs_repair:
            messages += [
                {"role": "assistant", "content": result.content},
                {"role": "user", "content": REPAIR_INSTRUCTION.format(problems="\n".join(verification.problems()))},
            ]
            repaired_result, repaired = self._call_and_parse(messages)
            run["llm"].append(repaired_result.meta())
            run["repaired"] = True
            if repaired is not None:
                repaired_verification = verify(repaired.answer, manifest)
                if repaired_verification.citation_validity >= verification.citation_validity:
                    parsed, verification = repaired, repaired_verification

        status = "needs_attention" if verification.needs_repair else "answered"
        run["citation_validity"] = round(verification.citation_validity, 4)
        run["cost_usd"] = round(sum(call["cost_usd"] for call in run["llm"]), 6)
        return {
            "status": status,
            "answer": self._answer_json(parsed, verification),
            "sources": self._sources_json(retrieval, manifest),
            "run": run,
        }

    # --- helpers ---

    @staticmethod
    def _user_content(source_block: str, question: str, history: list[HistoryTurn]) -> list[dict[str, str]]:
        parts = [{"type": "text", "text": f"<sources>\n{source_block}\n</sources>"}]
        if history:
            turns = "\n".join(f"<turn><q>{escape(t.question)}</q><a>{escape(t.answer)}</a></turn>" for t in history[-3:])
            parts.append({"type": "text", "text": f"<history>\n{turns}\n</history>"})
        parts.append({"type": "text", "text": f"<question>{escape(question)}</question>"})
        return parts

    def _call_and_parse(self, messages: list[dict[str, Any]]) -> tuple[LlmResult, ConsultAnswer | None]:
        call = LlmCall(CONSULT_SYSTEM, messages, CONSULT_SCHEMA, self._s.consult_effort, self._s.consult_max_tokens)
        last: LlmResult | None = None
        for _ in range(2):  # a schema/validation failure is retried once (§5.12)
            last = self._claude.run(call)
            if last.text is None:
                return last, None
            try:
                return last, ConsultAnswer.model_validate_json(last.text)
            except ValidationError:
                continue
        if last is None:  # pragma: no cover - the loop always runs
            raise ServiceError(500, "internal", "no model call was made")
        return last, None

    @staticmethod
    def _answer_json(answer: ConsultAnswer, v: Verification) -> dict[str, Any]:
        paragraphs = []
        for para, check in zip(answer.answer, v.paragraphs, strict=True):
            paragraphs.append(
                {
                    "text": para.text,
                    "legal_claim": para.legal_claim,
                    "unsupported": check.unsupported,
                    "article_mismatch": check.article_mismatch,
                    "citations": [
                        {
                            "ref": c.ref,
                            "chunk_id": c.chunk_id,
                            "label": c.label,
                            "quote": c.quote,
                            "verified": c.valid,
                            "verification": {"match": c.match, "score": round(c.score, 1), "reason": c.reason},
                        }
                        for c in check.citations
                    ],
                }
            )
        return {
            "answerable": answer.answerable,
            "paragraphs": paragraphs,
            "suggest_lawyer": answer.suggest_lawyer or not answer.answerable,
            "follow_ups": answer.follow_ups,
            "flags": v.flags,
        }

    @staticmethod
    def _sources_json(retrieval: RetrievalResult, manifest: dict[str, SourceEntry]) -> list[dict[str, Any]]:
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

    def _refusal(self, status: str, retrieval: RetrievalResult, manifest: dict[str, SourceEntry], run: dict[str, Any]) -> dict[str, Any]:
        answer = ConsultAnswer(
            answerable=False, answer=[Para(text=REFUSAL_TEXT, legal_claim=False, citations=[])], suggest_lawyer=True, follow_ups=[]
        )
        run.setdefault("llm", [])
        run["cost_usd"] = round(sum(call["cost_usd"] for call in run["llm"]), 6)
        return {
            "status": status,
            "answer": self._answer_json(answer, verify(answer.answer, manifest)),
            "sources": self._sources_json(retrieval, manifest),
            "run": run,
        }
