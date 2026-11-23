"""Case analysis and draft routes (PROJECT1_PLAN.md §5.1, §5.5–5.9; stories S3.5, S3.6, S3.7).

analyze: residual PII guard → multi-query retrieval (facts, complaint letter, playbook) → bundle (<sources>, <case>,
<case_document>) → size guard → Claude (CaseAnalysis) → validation → verification → one repair call.
draft: the same bundle with the analysis's own sources (same S refs, so the analysis citations stay meaningful) plus the
analysis → Claude (DraftResponse) → validation → verification (legal claims need an S source) → one repair call.
The pipeline in Laravel calls analyze, stores the result, then calls draft with the returned source_chunk_ids.
"""

from __future__ import annotations

from dataclasses import dataclass, replace
from typing import Any

from app.core.config import Settings
from app.core.errors import ServiceError
from app.llm.client import ClaudeClient, LlmCall
from app.llm.consult import para_json, sources_json
from app.llm.context import DocPage, DocumentEntry, Manifest, build_documents, build_sources, escape
from app.llm.prompts import CASE_ANALYSIS_PROMPT_VERSION, CASE_ANALYSIS_SYSTEM, CASE_DRAFT_PROMPT_VERSION, CASE_DRAFT_SYSTEM
from app.llm.runner import Outcome, StructuredRunner
from app.llm.schemas import CASE_ANALYSIS_SCHEMA, DRAFT_SCHEMA, SCHEMA_VERSION, CaseAnalysis, DraftResponse
from app.masking.masker import Masker, PlaceholderState
from app.retrieval.hybrid import HybridRetriever, RetrievalResult, RetrievedChunk
from app.retrieval.repository import ChunkRepository
from app.verify.citations import Claim, verify

COMPLAINT_EXCERPT = 1500


@dataclass(frozen=True)
class CaseFacts:
    case_type: str
    claim_type: str
    authority: str
    complaint_no: str
    received_on: str
    title: str
    procedure: str
    procedure_date: str
    patient_code: str
    facts: str  # masked

    def summary(self) -> str:
        return f"{self.title}\n{self.procedure}\n{self.facts}"


class CaseService:
    def __init__(self, retriever: HybridRetriever, repo: ChunkRepository, claude: ClaudeClient, masker: Masker, settings: Settings) -> None:
        self._retriever = retriever
        self._repo = repo
        self._claude = claude
        self._runner = StructuredRunner(claude)
        self._masker = masker
        self._s = settings

    # --- analyze ------------------------------------------------------------------------------------------------

    def analyze(self, case: CaseFacts, documents: list[DocumentEntry]) -> dict[str, Any]:
        case, documents, residual = self._guard(case, documents)
        complaint = next((d.display_text[:COMPLAINT_EXCERPT] for d in documents if d.kind == "complaint_letter"), "")
        retrieval = self._retriever.retrieve_many(
            [case.summary(), complaint, *self._s.playbooks.queries(case.case_type, case.claim_type)],
            rerank_query=case.summary(),
            top_k=self._s.retrieval.top_case,
        )
        source_block, sources = build_sources(retrieval.chunks)
        manifest: Manifest = {**sources, **{d.ref: d for d in documents}}
        note = "" if retrieval.answerable else "\nNote: retrieval found little relevant regulatory text for this case."
        messages = self._messages(source_block, case, documents, extra=note)
        call = LlmCall(CASE_ANALYSIS_SYSTEM, messages, CASE_ANALYSIS_SCHEMA, self._s.analysis_effort, self._s.case_max_tokens)
        self._size_guard(call)

        outcome = self._runner.run(
            system=call.system,
            messages=messages,
            schema=call.schema,
            model=CaseAnalysis,
            effort=call.effort,
            max_tokens=call.max_tokens,
            verify=lambda a: verify(_analysis_claims(a), manifest, any_citation_counts=True),
            labels=_analysis_labels,
        )
        run = self._run_meta("case_analysis", CASE_ANALYSIS_PROMPT_VERSION, retrieval, outcome, residual)
        base = {"sources": sources_json(retrieval, sources), "source_chunk_ids": [e.chunk_id for e in sources.values()], "run": run}
        if outcome.refused or outcome.parsed is None or outcome.verification is None:
            return {"status": "refused", "analysis": None, **base}

        a, v = outcome.parsed, outcome.verification
        checks = iter(v.paragraphs)
        analysis = {
            "position_strength": a.position_strength,
            "risk_level": a.risk_level,
            "summary": a.summary,
            "strengths": [_item_json(i.text, next(checks)) for i in a.strengths],
            "weaknesses": [{**_item_json(i.text, next(checks)), "severity": i.severity} for i in a.weaknesses],
            "recommended_actions": [{**_item_json(i.text, next(checks)), "urgency": i.urgency} for i in a.recommended_actions],
            "missing_information": a.missing_information,
            # The model's judgement, overruled when retrieval itself found too little (§5.5).
            "sources_sufficient": a.sources_sufficient and retrieval.answerable,
            "flags": v.flags,
            # Refs as the model wrote them, for the draft prompt (same sources, same refs).
            "raw": a.model_dump(),
        }
        return {"status": "needs_attention" if v.needs_repair else "ok", "analysis": analysis, **base}

    # --- draft --------------------------------------------------------------------------------------------------

    def draft(self, case: CaseFacts, documents: list[DocumentEntry], analysis: CaseAnalysis, source_chunk_ids: list[int]) -> dict[str, Any]:
        case, documents, residual = self._guard(case, documents)
        rows = self._repo.fetch(source_chunk_ids)
        if source_chunk_ids and not rows:
            raise ServiceError(409, "sources_missing", "none of the analysis sources exist any more; re-run the analysis")
        chunks = [RetrievedChunk(rows[cid], 0.0, None, None, False) for cid in source_chunk_ids if cid in rows]
        retrieval = RetrievalResult(chunks=chunks, best_score=0.0, answerable=bool(chunks))
        source_block, sources = build_sources(chunks)
        manifest: Manifest = {**sources, **{d.ref: d for d in documents}}
        messages = self._messages(source_block, case, documents, extra=f"\n<analysis>\n{_analysis_block(analysis)}\n</analysis>")
        call = LlmCall(CASE_DRAFT_SYSTEM, messages, DRAFT_SCHEMA, self._s.draft_effort, self._s.case_max_tokens)
        self._size_guard(call)

        outcome = self._runner.run(
            system=call.system,
            messages=messages,
            schema=call.schema,
            model=DraftResponse,
            effort=call.effort,
            max_tokens=call.max_tokens,
            verify=lambda d: verify([Claim(p.text, p.legal_claim, p.citations) for p in d.paragraphs()], manifest),
            labels=_draft_labels,
        )
        run = self._run_meta("case_draft", CASE_DRAFT_PROMPT_VERSION, retrieval, outcome, residual)
        base = {"sources": sources_json(retrieval, sources), "run": run}
        if outcome.refused or outcome.parsed is None or outcome.verification is None:
            return {"status": "refused", "draft": None, **base}

        d, v = outcome.parsed, outcome.verification
        checks = iter(v.paragraphs)
        intro_check = next(checks)
        draft = {
            "title": d.title,
            "addressee": d.addressee,
            "salutation": d.salutation,
            "intro": para_json(d.intro.text, d.intro.legal_claim, intro_check),
            "sections": [
                {"kind": s.kind, "heading": s.heading, "paragraphs": [para_json(p.text, p.legal_claim, next(checks)) for p in s.paragraphs]}
                for s in d.sections
            ],
            "closing": d.closing,
            "attachments": d.attachments,
            "unsupported_points": d.unsupported_points,
            "flags": v.flags,
        }
        return {"status": "needs_attention" if v.needs_repair else "ok", "draft": draft, **base}

    # --- shared -------------------------------------------------------------------------------------------------

    def _guard(self, case: CaseFacts, documents: list[DocumentEntry]) -> tuple[CaseFacts, list[DocumentEntry], int]:
        """Residual PII pass (layer 7) over everything that will be sent; numbering continues after existing tokens."""
        pages = [p for d in documents for p in d.pages]
        texts = [case.title, case.procedure, case.facts, *(p.text for p in pages)]
        result = self._masker.mask(texts, state=PlaceholderState.continuing(texts))
        title, procedure, facts, *page_texts = result.texts
        it = iter(page_texts)
        guarded = [replace(d, pages=tuple(DocPage(p.page_no, p.document_page_id, next(it)) for p in d.pages)) for d in documents]
        return replace(case, title=title, procedure=procedure, facts=facts), guarded, result.residual_pii

    @staticmethod
    def _messages(source_block: str, case: CaseFacts, documents: list[DocumentEntry], extra: str = "") -> list[dict[str, Any]]:
        case_block = "\n".join(
            f"<{k}>{escape(v)}</{k}>"
            for k, v in (
                ("case_type", case.case_type),
                ("claim_type", case.claim_type),
                ("authority", case.authority),
                ("complaint_no", case.complaint_no),
                ("received_on", case.received_on),
                ("title", case.title),
                ("procedure", case.procedure),
                ("procedure_date", case.procedure_date),
                ("patient_code", case.patient_code),
                ("facts", case.facts),
            )
        )
        return [
            {
                "role": "user",
                "content": [
                    {"type": "text", "text": f"<sources>\n{source_block}\n</sources>"},
                    {
                        "type": "text",
                        "text": f"<case>\n{case_block}\n</case>\n<case_documents>\n{build_documents(documents)}\n</case_documents>{extra}",
                    },
                ],
            }
        ]

    def _size_guard(self, call: LlmCall) -> None:
        """Refuse, never truncate, bundles over the limit. Counting is an API call, so small bundles skip it."""
        chars = len(call.system) + sum(len(part["text"]) for m in call.messages for part in m["content"])
        if chars < self._s.case_bundle_max_tokens:  # well under the limit at any Arabic chars-per-token ratio
            return
        tokens = self._claude.count_tokens(call)
        if tokens > self._s.case_bundle_max_tokens:
            raise ServiceError(413, "bundle_too_large", f"case bundle is {tokens} tokens (limit {self._s.case_bundle_max_tokens})")

    @staticmethod
    def _run_meta(run_type: str, prompt_version: str, retrieval: RetrievalResult, outcome: Outcome[Any], residual: int) -> dict[str, Any]:
        run: dict[str, Any] = {
            "run_type": run_type,
            "prompt_version": prompt_version,
            "schema_version": SCHEMA_VERSION,
            "retrieval": {**retrieval.params, "best_score": round(retrieval.best_score, 4), "explicit_refs": retrieval.explicit_refs},
            "residual_pii": residual,
            "llm": outcome.calls,
            "cost_usd": outcome.cost_usd,
            "repaired": outcome.repaired,
        }
        if outcome.refused:
            run["refusal_category"] = outcome.refusal_category
        if outcome.verification is not None:
            run["citation_validity"] = round(outcome.verification.citation_validity, 4)
        return run


def _analysis_claims(a: CaseAnalysis) -> list[Claim]:
    # Strengths and weaknesses must rest on a source or a document; actions may stand on their own.
    return [
        *(Claim(i.text, True, i.citations) for i in a.strengths),
        *(Claim(i.text, True, i.citations) for i in a.weaknesses),
        *(Claim(i.text, False, i.citations) for i in a.recommended_actions),
    ]


def _analysis_labels(a: CaseAnalysis) -> list[str]:
    return [
        *(f"strength {i}" for i in range(1, len(a.strengths) + 1)),
        *(f"weakness {i}" for i in range(1, len(a.weaknesses) + 1)),
        *(f"recommended action {i}" for i in range(1, len(a.recommended_actions) + 1)),
    ]


def _draft_labels(d: DraftResponse) -> list[str]:
    labels = ["intro"]
    for s in d.sections:
        labels += [f"{s.kind} paragraph {i}" for i in range(1, len(s.paragraphs) + 1)]
    return labels


def _item_json(text: str, check: Any) -> dict[str, Any]:
    return {"text": text, "unsupported": check.unsupported, "citations": [c.to_json() for c in check.citations]}


def _analysis_block(a: CaseAnalysis) -> str:
    def refs(cites: list[Any]) -> str:
        return f" [{', '.join(c.ref for c in cites)}]" if cites else ""

    lines = [f"position_strength: {a.position_strength}", f"risk_level: {a.risk_level}", f"summary: {escape(a.summary)}", "strengths:"]
    lines += [f"- {escape(i.text)}{refs(i.citations)}" for i in a.strengths]
    lines += ["weaknesses:"] + [f"- ({i.severity}) {escape(i.text)}{refs(i.citations)}" for i in a.weaknesses]
    lines += ["recommended_actions:"] + [f"- ({i.urgency}) {escape(i.text)}{refs(i.citations)}" for i in a.recommended_actions]
    lines += ["missing_information:"] + [f"- {escape(m)}" for m in a.missing_information]
    return "\n".join(lines)
