# Eval report — consult_v1 · route `retrieve`

Run 2026-11-25 09:49 UTC · 50 items · 36 passed · gold labels **draft, not yet lawyer-verified (S8.2)**.

- models: embedder `hashing`, reranker `lexical`, NER `none`
- stand-in models: numbers are not representative of production retrieval

## Metrics

| Metric | Value | Target |
|---|---|---|
| recall@8 | 0.7105 | ≥ 0.9 · ❌ |
| refusal_accuracy | 0.8333 | ≥ 0.9 · ❌ |
| false_refusal_rate | 0.1316 | ≤ 0.1 · ❌ |
| latency_p50_ms | 21.3 |  |
| latency_p90_ms | 29.5 |  |

## Failures

- **c02**: gold HPPL 21 not in top 8
- **c09**: gold HPPL 38 not in top 8
- **c10**: gate refused
- **c15**: gate refused
- **c16**: gold HPPL 8 not in top 8
- **c19**: gate refused
- **c20**: gold HPPL 11 not in top 8
- **c22**: gate refused
- **c28**: gate refused
- **c32**: gold HPPL 29 not in top 8
- **c33**: gold HPPL 32 not in top 8
- **c37**: gold HPPL 39 not in top 8
- **r06**: should refuse; best score 0.45
- **r07**: should refuse; best score 0.50
