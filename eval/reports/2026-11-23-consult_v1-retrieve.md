# Eval report — consult_v1 · route `retrieve`

Run 2026-11-23 20:00 UTC · 16 items · 13 passed · gold labels **draft, not yet lawyer-verified (S8.2)**.

- models: embedder `hashing`, reranker `lexical`, NER `none`
- stand-in models: numbers are not representative of production retrieval

## Metrics

| Metric | Value | Target |
|---|---|---|
| recall@8 | 0.8333 | ≥ 0.9 · ❌ |
| refusal_accuracy | 1.0 | ≥ 0.9 · ✅ |
| false_refusal_rate | 0.0833 | ≤ 0.1 · ✅ |
| latency_p50_ms | 17.6 |  |
| latency_p90_ms | 21.1 |  |

## Failures

- **c02**: gold HPPL 21 not in top 8
- **c09**: gold HPPL 38 not in top 8
- **c10**: gate refused
