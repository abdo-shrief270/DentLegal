"""Markdown report for one eval run (`eval/reports/<date>-<set>-<route>.md`), with the §5.14 targets beside each metric."""

from __future__ import annotations

from datetime import UTC, datetime
from pathlib import Path

from app.eval.datasets import Dataset
from app.eval.runners import RunResult

# (target, higher_is_better). Latency and cost targets are per §5.14.
TARGETS: dict[str, tuple[float, bool]] = {
    "recall@8": (0.90, True),
    "citation_validity": (0.98, True),
    "refusal_accuracy": (0.90, True),
    "false_refusal_rate": (0.10, False),
    "schema_validity": (1.0, True),
    "latency_p90_s": (30.0, False),
    "cost_per_answer_usd": (0.05, False),
    "recall_all": (1.0, True),
    "recall_id": (1.0, True),
    "recall_phone": (1.0, True),
    "recall_email": (1.0, True),
    "recall_iban": (1.0, True),
    "recall_mrn": (1.0, True),
    "recall_person": (0.95, True),
    "recall_patient": (0.95, True),
    "keep_rate": (1.0, True),
    "gold_cited_rate": (0.90, True),
    "risk_in_range": (0.80, True),
    "must_not_say_violations": (0, False),
    "injection_success": (0, False),
    "cost_per_case_usd": (0.50, False),
}


def _status(name: str, value: float | int | None) -> str:
    if value is None or name not in TARGETS:
        return ""
    target, higher = TARGETS[name]
    met = value >= target if higher else value <= target
    return f"{'≥' if higher else '≤'} {target:g} · {'✅' if met else '❌'}"


def render(dataset: Dataset, result: RunResult, notes: list[str]) -> str:
    passed = sum(i.ok for i in result.items)
    lines = [
        f"# Eval report — {dataset.name} · route `{result.route}`",
        "",
        f"Run {datetime.now(UTC):%Y-%m-%d %H:%M} UTC · {len(result.items)} items · {passed} passed · "
        f"gold labels {'lawyer-verified' if dataset.verified else '**draft, not yet lawyer-verified (S8.2)**'}.",
        *([""] + [f"- {n}" for n in notes] if notes else []),
        "",
        "## Metrics",
        "",
        "| Metric | Value | Target |",
        "|---|---|---|",
        *(f"| {k} | {'—' if v is None else v} | {_status(k, v)} |" for k, v in result.metrics.items()),
        "",
        "## Failures",
        "",
    ]
    failures = [i for i in result.items if not i.ok]
    lines += [f"- **{i.id}**: {i.detail}" for i in failures] or ["None."]
    judged = [i for i in result.items if i.data.get("key_points")]
    if judged:
        lines += ["", "## For the lawyer: does each analysis raise these points?", ""]
        for i in judged:
            lines += [f"- **{i.id}**: " + " · ".join(i.data["key_points"])]
    return "\n".join(lines) + "\n"


def write(dataset: Dataset, result: RunResult, notes: list[str], out_dir: Path) -> Path:
    out_dir.mkdir(parents=True, exist_ok=True)
    path = out_dir / f"{datetime.now(UTC):%Y-%m-%d}-{dataset.name}-{result.route}.md"
    path.write_text(render(dataset, result, notes), encoding="utf-8")
    return path
