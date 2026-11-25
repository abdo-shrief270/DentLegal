"""Runs a dataset through one route and computes the §5.14 metrics. Each runner returns per-item results plus a
metrics dict; the report renders both. Routes: `retrieve` (no LLM: recall@k and the refusal gate), `consult` (the full
consultant with Claude), `case` (case analysis with Claude), `pii` (the masker)."""

from __future__ import annotations

import statistics
import time
from dataclasses import dataclass, field
from typing import Any

from app.eval.datasets import CaseItem, ConsultItem, PiiItem
from app.llm.case import CaseFacts, CaseService
from app.llm.consult import ConsultService
from app.llm.context import DocPage, DocumentEntry
from app.masking.masker import DentistIdentity, Masker
from app.retrieval.hybrid import HybridRetriever


@dataclass
class ItemResult:
    id: str
    ok: bool
    detail: str
    data: dict[str, Any] = field(default_factory=dict)


@dataclass
class RunResult:
    route: str
    metrics: dict[str, float | int | None]
    items: list[ItemResult]


def _pct(n: int, d: int) -> float | None:
    return round(n / d, 4) if d else None


def _p(values: list[float], q: float) -> float | None:
    if not values:
        return None
    if len(values) == 1:
        return round(values[0], 1)
    return round(statistics.quantiles(values, n=100, method="inclusive")[int(q * 100) - 1], 1)


def run_retrieve(items: list[ConsultItem], retriever: HybridRetriever, top_k: int) -> RunResult:
    """Retrieval only: is a gold article among the chunks the prompt would get, and does the gate refuse the right ones?"""
    out: list[ItemResult] = []
    hits = answerable = refused_ok = must_refuse = false_refusals = 0
    latencies: list[float] = []
    for item in items:
        started = time.perf_counter()
        r = retriever.retrieve(item.question, top_k)
        latencies.append((time.perf_counter() - started) * 1000)
        found = [
            str(g) for g in item.gold if any(g.matches(c.chunk.source_code, c.chunk.article_no, c.chunk.paragraph_ref) for c in r.chunks)
        ]
        labels = [f"{c.chunk.source_code} {c.chunk.label} ({c.rerank_score:.2f})" for c in r.chunks[:top_k]]
        if item.answerable:
            answerable += 1
            hit = bool(found)
            hits += hit
            false_refusals += not r.answerable
            ok = hit and r.answerable
            detail = "ok" if ok else ("gate refused" if not r.answerable else f"gold {', '.join(map(str, item.gold))} not in top {top_k}")
        else:
            must_refuse += 1
            refused_ok += not r.answerable
            ok = not r.answerable
            detail = "ok" if ok else f"should refuse; best score {r.best_score:.2f}"
        out.append(ItemResult(item.id, ok, detail, {"best_score": round(r.best_score, 3), "retrieved": labels}))
    return RunResult(
        "retrieve",
        {
            f"recall@{top_k}": _pct(hits, answerable),
            "refusal_accuracy": _pct(refused_ok, must_refuse),
            "false_refusal_rate": _pct(false_refusals, answerable),
            "latency_p50_ms": _p(latencies, 0.5),
            "latency_p90_ms": _p(latencies, 0.9),
        },
        out,
    )


def run_consult(items: list[ConsultItem], service: ConsultService) -> RunResult:
    """The full consultant: refusals, citation validity, whether a gold source is cited, schema validity, latency, cost."""
    out: list[ItemResult] = []
    valid_cites = total_cites = cited_gold = answerable = refused_ok = must_refuse = false_refusals = schema_ok = 0
    latencies: list[float] = []
    costs: list[float] = []
    for item in items:
        started = time.perf_counter()
        try:
            res = service.answer(item.question, [])
            schema_ok += 1
        except Exception as exc:  # an eval records failures instead of stopping
            out.append(ItemResult(item.id, False, f"error: {type(exc).__name__}: {exc}"))
            continue
        latencies.append(time.perf_counter() - started)
        costs.append(sum(float(c.get("cost_usd") or 0) for c in res["run"].get("llm", [])))
        refused = str(res["status"]).startswith("refused")
        citations = [c for p in res["answer"]["paragraphs"] for c in p["citations"]]
        valid = [c for c in citations if c.get("verified")]
        total_cites += len(citations)
        valid_cites += len(valid)
        by_chunk = {s["chunk_id"]: s for s in res["sources"]}
        cited = {(by_chunk[c["chunk_id"]]["label"]) for c in valid if c.get("chunk_id") in by_chunk}
        # Labels as the API shows them: «المادة 21» for an article, «اللائحة 19/1» for a regulation paragraph.
        gold_labels = {f"اللائحة {g.paragraph}" if g.paragraph else f"المادة {g.article}" for g in item.gold}
        gold_hit = bool(gold_labels & cited)
        if item.answerable:
            answerable += 1
            false_refusals += refused
            cited_gold += gold_hit
            ok = not refused and gold_hit
            detail = "ok" if ok else ("refused" if refused else f"gold {', '.join(map(str, item.gold))} not cited (cited: {sorted(cited)})")
        else:
            must_refuse += 1
            refused_ok += refused
            ok = refused
            detail = "ok" if ok else "answered a must-refuse question"
        out.append(ItemResult(item.id, ok, detail, {"status": res["status"], "citations": len(citations), "valid": len(valid)}))
    return RunResult(
        "consult",
        {
            "citation_validity": _pct(valid_cites, total_cites),
            "gold_cited_rate": _pct(cited_gold, answerable),
            "refusal_accuracy": _pct(refused_ok, must_refuse),
            "false_refusal_rate": _pct(false_refusals, answerable),
            "schema_validity": _pct(schema_ok, len(items)),
            "latency_p50_s": _p(latencies, 0.5),
            "latency_p90_s": _p(latencies, 0.9),
            "cost_per_answer_usd": round(statistics.mean(costs), 4) if costs else None,
        },
        out,
    )


def run_pii(items: list[PiiItem], masker: Masker, ner_enabled: bool = True) -> RunResult:
    """Every planted value must be gone after masking (recall per type); `keep` terms must survive (no over-masking).
    Items that only the NER layer can pass are skipped, not failed, when NER is off (CI)."""
    out: list[ItemResult] = []
    per_type: dict[str, list[int]] = {}
    kept = keep_total = skipped = 0
    for item in items:
        if item.ner and not ner_enabled:
            skipped += 1
            out.append(ItemResult(item.id, True, "skipped: needs NER"))
            continue
        dentist = DentistIdentity(name=item.dentist_name, license_no=None) if item.dentist_name else None
        masked = masker.mask([item.text], dentist=dentist).texts[0]
        leaks = [(t, v) for t, v in item.planted if v in masked]
        for t, v in item.planted:
            per_type.setdefault(t, [0, 0])
            per_type[t][1] += 1
            per_type[t][0] += v not in masked
        lost = [k for k in item.keep if k not in masked]
        keep_total += len(item.keep)
        kept += len(item.keep) - len(lost)
        ok = not leaks and not lost
        detail = "ok" if ok else "; ".join([*(f"leaked {t}: {v}" for t, v in leaks), *(f"over-masked: {k}" for k in lost)])
        out.append(ItemResult(item.id, ok, detail, {"masked": masked}))
    metrics: dict[str, float | int | None] = {f"recall_{t.lower()}": _pct(n, d) for t, (n, d) in sorted(per_type.items())}
    found = sum(n for n, _ in per_type.values())
    planted = sum(d for _, d in per_type.values())
    metrics["recall_all"] = _pct(found, planted)
    metrics["keep_rate"] = _pct(kept, keep_total)
    metrics["skipped_needs_ner"] = skipped
    return RunResult("pii", metrics, out)


def _case_inputs(item: CaseItem) -> tuple[CaseFacts, list[DocumentEntry]]:
    docs = [
        DocumentEntry(
            ref=f"D{n}",
            document_id=f"{item.id}-d{n}",
            kind=d.kind,
            name=d.name,
            pages=tuple(DocPage(p, f"{item.id}-d{n}-p{p}", text) for p, text in enumerate(d.pages, start=1)),
        )
        for n, d in enumerate(item.documents, start=1)
    ]
    return CaseFacts(**item.case), docs  # the service masks everything before the call (layer 7)


def _analysis_text(a: dict[str, Any]) -> str:
    parts = [a["summary"], *a["missing_information"]]
    parts += [i["text"] for k in ("strengths", "weaknesses", "recommended_actions") for i in a[k]]
    return "\n".join(parts)


def run_case(items: list[CaseItem], service: CaseService) -> RunResult:
    """Case analysis: schema validity, citation validity, whether a gold article is cited, risk level within the gold
    range, must-not-say phrases (injection canaries among them), latency and cost. The key points are listed in the
    report for the lawyer to judge."""
    out: list[ItemResult] = []
    valid_cites = total_cites = gold_hit_n = risk_ok = schema_ok = violations = injected = 0
    latencies: list[float] = []
    costs: list[float] = []
    for item in items:
        case, docs = _case_inputs(item)
        started = time.perf_counter()
        try:
            res = service.analyze(case, docs)
        except Exception as exc:  # an eval records failures instead of stopping
            out.append(ItemResult(item.id, False, f"error: {type(exc).__name__}: {exc}"))
            continue
        latencies.append(time.perf_counter() - started)
        costs.append(sum(float(c.get("cost_usd") or 0) for c in res["run"].get("llm", [])))
        a = res.get("analysis")
        if a is None:
            out.append(ItemResult(item.id, False, f"no analysis ({res['status']})"))
            continue
        schema_ok += 1
        citations = [c for k in ("strengths", "weaknesses", "recommended_actions") for i in a[k] for c in i["citations"]]
        valid = [c for c in citations if c.get("verified")]
        total_cites += len(citations)
        valid_cites += len(valid)
        by_chunk = {s["chunk_id"]: s for s in res["sources"]}
        cited = {by_chunk[c["chunk_id"]]["label"] for c in valid if c.get("chunk_id") in by_chunk}
        gold_labels = {f"اللائحة {g.paragraph}" if g.paragraph else f"المادة {g.article}" for g in item.gold}
        hit = bool(gold_labels & cited)
        gold_hit_n += hit
        in_range = a["risk_level"] in item.risk
        risk_ok += in_range
        said = _analysis_text(a)
        bad = [p for p in item.must_not_say if p in said]
        violations += len(bad)
        injected += bool(bad) and item.injection
        problems = [
            *([] if hit else [f"gold {', '.join(map(str, item.gold))} not cited (cited: {sorted(cited)})"]),
            *([] if in_range else [f"risk {a['risk_level']}, acceptable {'/'.join(item.risk)}"]),
            *(f"said: {p}" for p in bad),
        ]
        out.append(
            ItemResult(
                item.id,
                not problems,
                "; ".join(problems) or "ok",
                {"status": res["status"], "citations": len(citations), "valid": len(valid), "key_points": item.key_points},
            )
        )
    return RunResult(
        "case",
        {
            "schema_validity": _pct(schema_ok, len(items)),
            "citation_validity": _pct(valid_cites, total_cites),
            "gold_cited_rate": _pct(gold_hit_n, len(items)),
            "risk_in_range": _pct(risk_ok, len(items)),
            "must_not_say_violations": violations,
            "injection_success": injected,
            "latency_p50_s": _p(latencies, 0.5),
            "latency_p90_s": _p(latencies, 0.9),
            "cost_per_case_usd": round(statistics.mean(costs), 4) if costs else None,
        },
        out,
    )
