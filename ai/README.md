# DentLegal ai-svc

The Python service behind DentLegal's AI (PROJECT1_PLAN.md §2.3 D1, §5). Laravel owns the data, permissions and job orchestration and calls ai-svc over the private network with `X-Service-Token`. ai-svc does the work that needs Python models: Arabic normalisation, PII masking, embeddings, hybrid retrieval over the regulations, Claude calls with structured outputs, and citation verification. It stores no case data.

## Endpoints (internal only)

| Endpoint | Does | Status |
|---|---|---|
| `GET /v1/health` | DB check, which models are loaded | Built |
| `POST /v1/mask` | Full PII masking with per-case placeholder numbering (state holds keyed hashes, never values) | Built (NER pluggable, CAMeL in production) |
| `POST /v1/retrieve` | Hybrid retrieval: explicit article refs + pgvector + Arabic full text (+ trigram), RRF, parent articles, rerank, refusal gate | Built |
| `POST /v1/consult` | Consultant answer: retrieval → refusal gate → Claude (ConsultAnswer schema) → validation → citation verification → one repair call | Built |
| `POST /v1/extract`, `/v1/case/analyze`, `/v1/case/draft`, `/v1/kb/ingest` | OCR, case analysis, draft, source ingestion | Next (S3.3, S3.6, S3.7, S1.3/S1.5) |

Request and response shapes match `backend/app/Modules/Ai/` (the Laravel client).

## How a consultant answer is made

1. **Residual PII guard:** the question (already masked by Laravel) is masked again; anything a pattern detector recognises can't leave.
2. **Retrieval:** articles named in the question («المادة 27», «م27», «اللائحة 40/1», «المادة السابعة والعشرون») are fetched directly; dense (bge-m3, HNSW) and full-text (Arabic `tsvector`) rankings are fused with RRF; regulation paragraphs bring their parent article; the top 30 are reranked (bge-reranker-v2-m3) and kept if ≥ `τ_keep`.
3. **Refusal gate:** if the best score is below `τ_answer`, the answer is the standard refusal with a lawyer suggestion, and Claude is not called.
4. **Claude** (`claude-opus-5-5`): streamed; `output_config.format` with the ConsultAnswer JSON schema; effort `low`; system prompt cached; `inference_geo: "us"`; server-side refusal fallback (`fallbacks: "default"`); the serving model and any fallback are recorded. Refusals and `max_tokens` are handled per §5.12.
5. **Validation:** Pydantic adds the checks the schema can't express (quote 10–300 characters, 1–6 paragraphs). One retry, then `ai_invalid_output`.
6. **Verification:** each citation's ref must be in this run's manifest and its quote must appear in that source (exact after normalisation, else fuzzy ≥ 92); legal claims need a valid source; an article named in the text must be cited. Below 90% validity or any invented ref, one repair call is made with the problems listed.
7. **Response:** paragraphs with each citation mapped to a `chunk_id` and its verification, the sources with their scores, and run metadata for `ai_runs` (tokens, cost, latency, model served, fallback, request id).

Logs are JSON and never contain questions, sources, prompts or answers (a redaction processor plus a test).

## Layout

```
app/
  core/        settings (+ production safety check), service-token auth, error envelope, redacting logs, container
  text/        display_text / search_text normalisation, Arabic ordinals, explicit article references
  masking/     PII masker (7 layers, fail-closed), NER port (CAMeL in production)
  retrieval/   embedder/reranker ports (BGE in production), SQL repository, hybrid retrieval + RRF + gate
  llm/         schemas v1, prompts (versioned), source blocks + manifest, Claude client, consult service
  verify/      citation verification
  routes/      /v1 endpoints
config/retrieval.yaml   retrieval thresholds (calibrated in S2.2 and S8.3)
tests/                  51 tests: unit + Postgres/pgvector + API with a fake Claude client
```

## Run locally

```bash
python3 -m venv .venv && .venv/bin/pip install -e ".[dev]"
```

```bash
cp .env.example .env
```

```bash
.venv/bin/uvicorn app.main:app --reload --port 8000
```

`.env.example` uses deterministic stand-ins for the models (`hashing`, `lexical`, NER `none`) so the service runs without the 5 GB of weights; the service refuses to start with them in staging or production. The real models come with `pip install -e ".[models]"` (FlagEmbedding, CAMeL Tools) or the Docker image, which bakes the weights in.

The knowledge tables are created by the Laravel migrations (`backend/`); ai-svc only reads active chunks (and will write chunks during ingestion).

## Checks (also in CI: `.github/workflows/ai.yml`)

```bash
.venv/bin/ruff check . && .venv/bin/ruff format --check .
```

```bash
.venv/bin/mypy app
```

```bash
.venv/bin/pytest
```

Tests need PostgreSQL with pgvector; they create their own `ai_test` schema in the database at `AI_TEST_DATABASE_URL` (default: the local `dentlegal_test`) and load synthetic regulation-style chunks. Claude is replaced by a fake client with the same `beta.messages.stream` surface, so tests cost nothing and run offline.

## Not verified yet

- **The real Claude request** has not been sent from this environment (no API key here). The combination of streaming + structured output + `fallbacks` + `inference_geo` is the spike in S2.3; run it once with the staging key before relying on it.
- **The Docker image** has not been built here (no Docker daemon). The BGE and CAMeL model downloads in it need checking on the first build.
- **Retrieval quality** with the real models is measured on the eval set (S2.2, S8.1); the thresholds in `config/retrieval.yaml` are the plan's start values.
