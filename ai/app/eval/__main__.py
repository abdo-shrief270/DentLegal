"""CLI (PROJECT1_PLAN.md §5.14):

    python -m app.eval run --set consult_v1 --route retrieve
    python -m app.eval run --set consult_v1 --route consult --effort low      # calls Claude: staging key, synthetic data
    python -m app.eval run --set pii_v1 --route pii

Settings come from the environment like the service (`.env`). The report goes to `eval/reports/`; the exit code is 1
when any item fails, so a CI job can gate on it.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

from app.core.config import Settings
from app.core.container import build_container
from app.eval import datasets, report, runners
from app.llm.consult import ConsultService
from app.masking.masker import Masker
from app.masking.ner import CamelNer, Ner, NoNer
from app.retrieval.hybrid import HybridRetriever
from app.retrieval.repository import ChunkRepository


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(prog="python -m app.eval")
    sub = parser.add_subparsers(dest="command", required=True)
    run = sub.add_parser("run", help="run one dataset through one route")
    run.add_argument("--set", required=True, help="dataset name in eval/datasets (e.g. consult_v1)")
    run.add_argument("--route", required=True, choices=["retrieve", "consult", "pii"])
    run.add_argument("--effort", choices=["low", "medium", "high"], help="override the consultant's effort")
    run.add_argument("--limit", type=int, help="first N items only")
    run.add_argument("--out", type=Path, default=datasets.REPO_ROOT / "eval" / "reports")
    args = parser.parse_args(argv)

    dataset = datasets.load(args.set)
    items = dataset.items[: args.limit] if args.limit else dataset.items
    if (args.route == "pii") != (dataset.kind == "pii"):
        parser.error(f"route {args.route} does not fit a {dataset.kind} dataset")

    settings = Settings()
    if args.effort:
        settings.consult_effort = args.effort
    if args.route == "pii":
        # The masker alone: no database, no models beyond NER (CI runs this route on every change).
        ner: Ner = CamelNer() if settings.ner == "camel" else NoNer()
        masker = Masker(ner, settings.masking_secret.get_secret_value().encode() or b"eval-only-masking-key")
        result = runners.run_pii(items, masker)
        path = report.write(dataset, result, [f"NER `{settings.ner}`"], args.out)
        return _finish(path, result)

    container = build_container(settings)
    notes = [f"models: embedder `{settings.embedder}`, reranker `{settings.reranker}`, NER `{settings.ner}`"]
    if settings.embedder == "hashing" or settings.reranker == "lexical":
        notes.append("stand-in models: numbers are not representative of production retrieval")
    try:
        with container.pool.connection() as conn:
            retriever = HybridRetriever(ChunkRepository(conn), container.embedder, container.reranker, settings.retrieval)
            if args.route == "retrieve":
                result = runners.run_retrieve(items, retriever, settings.retrieval.top_consult)
            else:
                notes.append(f"model `{settings.anthropic_model}`, effort `{settings.consult_effort}`")
                result = runners.run_consult(items, ConsultService(retriever, container.claude, container.masker, settings))
    finally:
        container.close()

    return _finish(report.write(dataset, result, notes, args.out), result)


def _finish(path: Path, result: runners.RunResult) -> int:
    print(path)
    for name, value in result.metrics.items():
        print(f"{name:24} {value}")
    return 0 if all(i.ok for i in result.items) else 1


if __name__ == "__main__":
    sys.exit(main())
