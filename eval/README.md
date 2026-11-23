# Evaluation (PROJECT1_PLAN.md §5.14)

Synthetic datasets only. Gold labels are drafts until the lawyer verifies them (S8.2); each set says `verified: false`
until then, and the report shows it.

| Set | Kind | Now | Planned |
|---|---|---|---|
| `datasets/consult_v1.yaml` | consultant questions with gold articles and must-refuse items | 16 (12 answerable, 4 out of scope) | 50 |
| `datasets/pii_v1.yaml` | texts with planted identifiers and terms that must survive | 10 | 30 |
| `cases_v1` | synthetic cases with documents | — | 10 (S8.2) |

Run from `ai/` with the same environment as the service (`.env`):

```bash
.venv/bin/python -m app.eval run --set consult_v1 --route retrieve
```

```bash
.venv/bin/python -m app.eval run --set pii_v1 --route pii
```

```bash
.venv/bin/python -m app.eval run --set consult_v1 --route consult --effort low
```

`retrieve` needs no Claude key (recall@8 and the refusal gate); `consult` calls Claude with the staging key. Reports go
to `reports/<date>-<set>-<route>.md` with each metric beside its §5.14 target; the exit code is 1 if any item fails.
Retrieval numbers are only meaningful with the real models (`EMBEDDER=bge-m3`, `RERANKER=bge-reranker-v2-m3`) and the
indexed sources (S1.6).
