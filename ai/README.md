# DentLegal ai-svc

The Python service behind DentLegal's AI (PROJECT1_PLAN.md §2.3 D1, §5). Laravel owns the data, permissions and job orchestration and calls ai-svc over the private network with `X-Service-Token`. ai-svc does the work that needs Python models: Arabic normalisation, PII masking, embeddings, hybrid retrieval over the regulations, Claude calls with structured outputs, and citation verification. It stores no case data.

## Endpoints (internal only)

| Endpoint | Does | Status |
|---|---|---|
| `GET /v1/health` | DB check, which models are loaded | Built |
| `POST /v1/mask` | Full PII masking with per-case placeholder numbering (state holds keyed hashes, never values) | Built (NER pluggable, CAMeL in production) |
| `POST /v1/retrieve` | Hybrid retrieval: explicit article refs + pgvector + Arabic full text (+ trigram), RRF, parent articles, rerank, refusal gate | Built |
| `POST /v1/consult` | Consultant answer: retrieval → refusal gate → Claude (ConsultAnswer schema) → validation → citation verification → one repair call | Built |
| `POST /v1/case/analyze` | Case analysis: multi-query retrieval (facts, complaint letter, per-case-type playbook) → bundle with documents → size guard → Claude (CaseAnalysis) → verification of source and document citations → one repair call | Built |
| `POST /v1/case/draft` | Formal draft (أولًا الوقائع / ثانيًا الدفوع / ثالثًا الطلبات) from the same bundle, the analysis and the analysis's own sources; legal claims need a source | Built |
| `POST /v1/extract` | A case document (PDF, JPEG, PNG; ≤ 20 MB, ≤ 50 pages) → **masked** text per page, OCR confidence, «جودة منخفضة» flags. Raw text never leaves ai-svc; never sent to an LLM | Built (S3.3) |
| `POST /v1/kb/ingest` | A public source PDF → pages (text layer, OCR-repaired layer, OCR, or Claude transcription) → articles / regulation paragraphs / definitions / forms / sections → chunks + embeddings written for review | Built (S1.3, S1.5) |

Request and response shapes match `backend/app/Modules/Ai/` (the Laravel client).

## How a consultant answer is made

1. **Residual PII guard:** the question (already masked by Laravel) is masked again; anything a pattern detector recognises can't leave.
2. **Retrieval:** articles named in the question («المادة 27», «م27», «اللائحة 40/1», «المادة السابعة والعشرون») are fetched directly; dense (bge-m3, HNSW) and full-text (Arabic `tsvector`) rankings are fused with RRF; regulation paragraphs bring their parent article; the top 30 are reranked (bge-reranker-v2-m3) and kept if ≥ `τ_keep`.
3. **Refusal gate:** if the best score is below `τ_answer`, the answer is the standard refusal with a lawyer suggestion, and Claude is not called.
4. **Claude** (`claude-opus-5-5`): streamed; `output_config.format` with the ConsultAnswer JSON schema; effort `low`; system prompt cached; `inference_geo: "us"`; server-side refusal fallback (`fallbacks: "default"`); the serving model and any fallback are recorded. Refusals and `max_tokens` are handled per §5.12.
5. **Validation:** Pydantic adds the checks the schema can't express (quote 10–300 characters, 1–6 paragraphs). One retry, then `ai_invalid_output`.
6. **Verification:** each citation's ref must be in this run's manifest and its quote must appear in that source (exact after normalisation, else fuzzy ≥ 92); legal claims need a valid source; an article named in the text must be cited. Below 90% validity or any invented ref, one repair call is made with the problems listed.
7. **Response:** paragraphs with each citation mapped to a `chunk_id` and its verification, the sources with their scores, and run metadata for `ai_runs` (tokens, cost, latency, model served, fallback, request id).

## How a case analysis and draft are made

1. **Residual PII guard** over the facts and every document page; new placeholders continue the numbering already in the text (`[PHONE_2]` after `[PHONE_1]`).
2. **Retrieval** with several queries fused into one ranking: the case summary, the first 1,500 characters of the complaint letter, and the playbook queries for the case type and claim type (`config/playbooks.yaml`, to be reviewed with the lawyer in W4). Reranked against the case summary; up to 15 sources.
3. **Bundle:** `<sources>`, `<case>` fields and `<case_document id="D1" kind=… pages=… ocr_confidence=…>` with page markers. Everything is escaped.
4. **Size guard:** large bundles are token-counted with the API and refused above 150k tokens (`bundle_too_large`), never truncated.
5. **Claude** with effort `high`, up to 32k output tokens, same request shape as the consultant.
6. **Verification:** document citations resolve to the page the quote is on (`document_page_id`). Analysis points may rest on a document alone; draft paragraphs with `legal_claim` need a source. One repair call when the gate fails.
7. **Analysis** returns `source_chunk_ids`; the **draft** call passes them back so the draft sees the same sources under the same S refs as the analysis it builds on. `sources_sufficient` is false when retrieval found too little, whatever the model said.

## Extraction and ingestion

How each page is read (text layer, OCR-guided repair of the «لا» ligature, OCR for encoding-corrupted pages, Claude transcription on request) and the measured results on the four supplied sources are in [`docs/ingestion/S1.1-extraction-bakeoff.md`](../docs/ingestion/S1.1-extraction-bakeoff.md). The current health-practice law parses into 165 chunks with all 44 articles and 100 regulation paragraphs.

Ingestion writes chunks with status `review` and replaces the version's earlier chunks in one transaction; Laravel owns the version status and publishing. Active and archived versions can't be re-ingested.

Logs are JSON and never contain questions, sources, prompts or answers (a redaction processor plus a test).

## Evaluation (S8.1)

`python -m app.eval run --set <set> --route retrieve|consult|pii` runs a dataset from `../eval/datasets/` and writes a markdown report to `../eval/reports/` with each metric beside its §5.14 target (recall@8, refusal accuracy, false refusals, citation validity, gold-cited rate, schema validity, latency, cost per answer, PII recall per type, over-masking). See [`../eval/README.md`](../eval/README.md). CI runs the `pii_v1` set on every change.

The first runs (23 Nov, stand-in models, the current law indexed locally): `pii_v1` found a real gap, mobile numbers written in groups (`+966 50 111 2233`, `055-123-4567`) leaked, now fixed in ai-svc and in Laravel's pattern masker; `consult_v1` retrieval recall@8 0.83, refusals 4/4, one false refusal, with the misses listed in the report. Production numbers need the BGE models and the lawyer-verified sets (S8.2).

## Layout

```
app/
  core/        settings (+ production safety check), service-token auth, error envelope, redacting logs, container
  text/        display_text / search_text normalisation, Arabic ordinals, explicit article references
  masking/     PII masker (7 layers, fail-closed), NER port (CAMeL in production)
  retrieval/   embedder/reranker ports (BGE in production), SQL repository, hybrid retrieval + RRF + gate
  llm/         schemas v1, prompts (versioned), source/document blocks + manifest, Claude client, shared runner, consult and case services
  verify/      citation verification
  eval/        eval harness: datasets, runners (retrieve / consult / pii), markdown reports, CLI
  extract/     PDF text layers, quality checks, OCR (Tesseract), repairs, case-document extraction with masking
  ingest/      structure parser (articles, regulations, definitions, forms, sections), chunker, ingestion service
  routes/      /v1 endpoints
config/retrieval.yaml   retrieval thresholds (calibrated in S2.2 and S8.3)
config/playbooks.yaml   fixed retrieval queries per case type and claim type
tests/                  88 tests: unit, Postgres/pgvector, real Tesseract on pages of the supplied PDFs, API with a fake Claude client
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

Tests need PostgreSQL with pgvector and Tesseract with Arabic (`apt-get install tesseract-ocr tesseract-ocr-ara`; the OCR tests skip without it); they create their own `ai_test` schema in the database at `AI_TEST_DATABASE_URL` (default: the local `dentlegal_test`) and load synthetic regulation-style chunks. Claude is replaced by a fake client with the same `beta.messages.stream` surface, so tests cost nothing and run offline.

## Not verified yet

- **The real Claude request** has not been sent from this environment (no API key here). The combination of streaming + structured output + `fallbacks` + `inference_geo` is the spike in S2.3; run it once with the staging key before relying on it.
- **The Docker image** has not been built here (no Docker daemon). The BGE and CAMeL model downloads in it need checking on the first build.
- **Real ingestion into the database** of the full sources was run as extraction + parsing (numbers in the bake-off report); the embedding step was tested with the stand-in embedder only.
- **Retrieval quality** with the real models is measured on the eval set (S2.2, S8.1); the thresholds in `config/retrieval.yaml` are the plan's start values.
