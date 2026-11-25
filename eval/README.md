# Evaluation (PROJECT1_PLAN.md §5.14)

Synthetic datasets only. Gold labels are drafts until the lawyer verifies them (S8.2); each set says `verified: false`
until then, and the report shows it.

| Set | Kind | Size |
|---|---|---|
| `datasets/consult_v1.yaml` | consultant questions with gold articles and must-refuse items | 50 (38 answerable, 12 out of scope) |
| `datasets/cases_v1.yaml` | synthetic cases with 3–6 documents each, planted PII, 5 injection attempts, 2 handwritten notes; gold: key articles, acceptable risk range, must-mention points, must-not-say phrases (injection canaries) | 10 |
| `datasets/pii_v1.yaml` | texts with planted identifiers and terms that must survive; 3 hold unlabelled names that only NER can catch (`ner: true`, skipped when `AI_NER=none`, as in CI) | 30 |

Gold articles were read from the official text of the current law and executive regulation. All three sets stay
`verified: false` until the lawyer checks them (S8.2): the gold articles, the risk ranges and the must-mention points.

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

```bash
.venv/bin/python -m app.eval run --set cases_v1 --route case
```

`retrieve` needs no Claude key (recall@8 and the refusal gate); `consult` and `case` call Claude with the staging key.
`case` scores schema validity, citation validity, gold article cited, risk in range, must-not-say violations and
injection success automatically, and lists each case's must-mention points in the report for the lawyer to judge. Reports go
to `reports/<date>-<set>-<route>.md` with each metric beside its §5.14 target; the exit code is 1 if any item fails.
Retrieval numbers are only meaningful with the real models (`EMBEDDER=bge-m3`, `RERANKER=bge-reranker-v2-m3`) and the
indexed sources (S1.6).
