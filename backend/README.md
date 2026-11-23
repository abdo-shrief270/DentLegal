# DentLegal API (Laravel)

The system of record for Project 1 (PROJECT1_PLAN.md §2, §3, §4): REST API `/api/v1` for the dentist app and the web dashboard, data ownership, permissions, the case state machine, the knowledge-base lifecycle, and orchestration of the AI work done by **ai-svc** (the Python service: OCR, PII masking, embeddings, hybrid retrieval, Claude, citation verification; decision D1).

## Stack

| Concern | Choice |
|---|---|
| Runtime | PHP 8.4 in production (the lock file is resolved for 8.3+), Laravel 12 |
| Database | PostgreSQL 17 + `pgvector` + `pg_trgm`, Arabic full-text search |
| Queues, cache, sessions, rate limits | Redis, Laravel Horizon |
| Auth | Sanctum: 30-day tokens with the `dentist` ability for the app; Sanctum SPA cookie sessions + mandatory TOTP two-factor for the dashboard |
| Roles | `spatie/laravel-permission`: `dentist`, `lawyer`, `admin` |
| API docs | `dedoc/scramble` at `/docs/api` (local and staging only) |
| Quality | PHPUnit 11 against real PostgreSQL, Larastan level 6, Pint |

## Structure

```
app/
  Support/              cross-cutting: API errors (one JSON envelope), Saudi phone numbers, PII pattern masker
  Http/Middleware/      X-App-Version check (426 for old app builds)
  Modules/
    Identity/           users, invitations, OTP sign-in, profile, consents, devices, SMS port
    Audit/              append-only audit log (database trigger blocks UPDATE/DELETE)
    Cases/              cases, DL-YYYY-NNNN codes, state machine, policy
    Knowledge/          sources, versions, chunks (pgvector), publish/archive lifecycle, «نص المادة» endpoint
    Ai/                 typed port to ai-svc (retrieve, mask, consult, case analyze/draft, extract, ingest) with retries
    Review/             lawyer review: queue, claim, versions with citation checks, internal notes, approval
    Consultations/      «المستشار القانوني الذكي»: threads, questions, queued answers
    Documents/          case uploads: type checked by bytes, encrypted at rest (per-file key wrapped by a master key)
    Pipeline/           queued case pipeline (extract → analyze → draft → ready), AI run records, analyses, drafts
```

Each module keeps `Models`, `Enums`, `Actions` (one class per business operation, used by controllers, jobs and tests), `Policies` and `Http` (controllers, form requests, resources). Controllers stay thin; every state change goes through an action that runs in a database transaction and writes the audit log.

## What is built (and tested)

| Story | Status |
|---|---|
| S0.6 Laravel skeleton | Modules, Sanctum, Horizon, permissions, Scramble, error envelope, rate limits, strict models |
| S5.1 OTP auth API | Invitation-only, HMAC-hashed 6-digit codes, 5-minute expiry, 5 attempts then burnt, resend 60 s, 5/h per phone, 20/h per IP, review number with a fixed code (config-guarded), SMS port with a log driver |
| S5.2 (part) | First login accepts the invitation and creates the dentist; profile completion; versioned, append-only consents |
| S5.6 (part) | Roles seeded, ownership policy (other users' cases return 404), audit service + append-only table |
| S3.1 Case API | Create, list (cursor pagination, status filter), show; 6-state machine with 409 on invalid transitions; atomic yearly codes; typed facts pattern-masked before storage and encrypted at rest |
| S1.4 KB schema | `knowledge_*` tables, HNSW / GIN / trigram partial indexes on active chunks, generated Arabic `tsvector`, one active version per source, active chunks must be embedded |
| S1.5 (Laravel side) | Publish archives the previous version and switches chunks in one transaction |
| ai-svc client | `AiService` port + HTTP adapter: retrieve, mask, consult, analyzeCase, draftCase, extractDocument, ingestSource (service token, per-route timeouts, retries on 5xx/connection errors only) |
| S3.2 Documents | Upload (PDF, JPEG, PNG by magic bytes; ≤ 20 MB; ≤ 15 per case; only while the case is a draft) and delete. Files are encrypted with XChaCha20-Poly1305 under a per-file key wrapped by `DOCUMENT_MASTER_KEY`, with the document id bound in, so a file can't be swapped onto another row |
| S3.8 Pipeline | `POST /cases/{id}/analysis` (consent `ai_processing`, ≥ 1 document, 10/day, `Idempotency-Key`, one run per case at a time) queues a chain on the `ai` queue: extract + mask (only masked text is stored) → analysis → draft → `ai_ready`. Overload and network errors retry with backoff; refusals and invalid output fail the run and the case with a code. A retry skips documents already extracted. Every Claude call is an `ai_runs` row with its sources |
| Results | `GET …/analysis/status` (steps, progress), `GET …/analysis`, `GET …/draft` (placeholders rendered: dentist name, licence, «المريض (الرمز P-…)»), `POST …/submit-review` (consent `lawyer_access`; case → `lawyer_review`) |
| S2.6 Consult API | Suggested questions (config), threads list (cursor), thread with messages, ask (new thread or follow-up) → 202 with the pending answer, poll `GET /consultation-messages/{id}`. Questions are pattern-masked before storage, then NER-masked by ai-svc with the thread's placeholder numbering before Claude sees them; the last 3 answered turns go as history. Consent `ai_processing`, 20/hour and 100/day per dentist, one open question per thread (409 `answer_pending`), `Idempotency-Key`. Answers are `answered` / `refused` (with the reason, no Claude call when retrieval is weak) / `failed` (with a code); only verified, cited sources are returned. Answers are stored encrypted |
| S5.7 Dashboard auth | Cookie session (`GET /sanctum/csrf-cookie`, then `POST /login`), `POST /two-factor-challenge` (TOTP or a one-time recovery code; the pending login expires after 5 min), `GET /user`, `POST /logout`. Invite links: `GET /invitations/{token}`, `POST /invitations/{token}/accept` sets the password and starts enrolment (QR URI + 8 recovery codes shown once), `POST /user/confirmed-two-factor-authentication` turns it on. The `two_factor` middleware closes every lawyer and admin route until a code was checked in this session, and refuses API tokens. Throttled 5/min per email + IP and per user + IP; 2 h idle timeout (`SESSION_LIFETIME`). TOTP is RFC 6238 in `Identity/TwoFactor/Totp.php` (tested against the RFC vectors), so no Fortify: its current version pulls in passkey/WebAuthn packages we don't use |
| S4.2–S4.4 Lawyer review | `/api/v1/lawyer/reviews` (tabs `unclaimed` / `mine` / `approved`, counts), detail (case, masked documents as D refs, the AI run's sources as S refs with scores, all versions, quality checks, internal notes), atomic claim (409 `already_claimed`), save an edited version (new immutable version; each citation re-checked against its source or document page; legal claims without a valid source flagged), internal notes (never shown to the dentist), approve (locks the version, case → `approved`, `DraftApproved` event for the push notification; unverified citations need `confirm_warnings`). Only the claiming lawyer edits; the dentist keeps seeing the AI draft until approval, then the approved version |

97 tests, 405 assertions: `php artisan test`.

Next: admin API, the knowledge-base ingestion job (S1.5), push notifications.

## Run locally

Requirements: PHP 8.3+ with `pdo_pgsql`, `redis`, `intl`, `sodium`; PostgreSQL 16/17 with pgvector; Redis.

```bash
cp .env.example .env && composer install && php artisan key:generate
```

Create the database and enable the extensions once (needs a superuser; the app role doesn't):

```bash
sudo -u postgres psql -c "CREATE ROLE dentlegal LOGIN PASSWORD 'dentlegal'" -c "CREATE DATABASE dentlegal OWNER dentlegal" -c "CREATE DATABASE dentlegal_test OWNER dentlegal"
```

```bash
for db in dentlegal dentlegal_test; do sudo -u postgres psql -d $db -c "CREATE EXTENSION IF NOT EXISTS vector; CREATE EXTENSION IF NOT EXISTS pg_trgm;"; done
```

```bash
php artisan migrate --seed && php artisan db:seed --class=DemoSeeder
```

```bash
php artisan serve
```

The demo seeder (never in production) creates the same synthetic dentist as the app's fake mode, `+966551234417`, an open invitation for `+966551111111`, and three cases. With `SMS_DRIVER=log` the OTP is written to `storage/logs/sms-*.log`.

Quick check:

```bash
curl -s -X POST localhost:8000/api/v1/auth/otp/request -H 'Content-Type: application/json' -H 'Accept: application/json' -H 'X-App-Version: 1.0.0' -d '{"phone":"0551234417"}'
```

## Quality gates (also in CI: `.github/workflows/backend.yml`)

```bash
vendor/bin/pint --test
```

```bash
vendor/bin/phpstan analyse
```

```bash
php artisan test
```

## Conventions

- **Errors:** always `{ "error": { "code", "message", "details" } }`; `message` is Arabic and safe to show. Validation errors also include Laravel's `errors` map.
- **Ids:** UUID v7 for user-facing entities, bigserial for logs and chunks.
- **Privacy:** no raw patient identifiers are stored. Typed facts are pattern-masked on write; documents are masked by ai-svc before storage; logs and audit metadata never hold case text, codes or tokens. Only synthetic data in seeds, factories and tests.
- **Time:** UTC everywhere; the clients format Hijri and Gregorian dates.
