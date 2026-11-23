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
    Notifications/      push port (log, FCM HTTP v1) and the «رد معتمد جاهز» push
    Feedback/           ratings, app feedback, usage stats and the nightly roll-up
    Admin/              admin API: dentist and lawyer invites, account activation
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
| S7.1 Users & invites | `/api/v1/admin/dentists`, `/lawyers` (accounts plus pending invitations, masked phones, case / consultation counts, lawyers' review load and average review time), invite a dentist (SMS with the download link; first OTP login accepts it) or a lawyer (email with a one-time link to set a password and enrol two-factor; the licence and firm go to the lawyer profile), resend (60 s cooldown; a new link replaces the old one), activate / deactivate (revokes API tokens and sessions; disabled dashboard users are refused on the next request) |
| S1.5 Knowledge admin | Sources with versions, upload a PDF for an existing or a new source → `uploaded` → queued ingestion by ai-svc (`ingest` queue) → `review` with the ingestion report (or `failed` with the reason), chunk preview, publish (atomic switch), archive, re-index with another extraction method |
| Queues | Horizon supervisors for `default`, `ai` (Claude calls, up to 15 min) and `ingest` (up to 30 min) on a `redis-long` connection whose `retry_after` stays above the job timeouts |
| S6.1–S6.2 Ratings & feedback | `POST /ratings` (upsert: the last rating wins; only the dentist's own answers, analyses and the AI or approved draft versions), `POST /feedback` (category, screen, platform, app version). Free text is pattern-masked and stored encrypted |
| S7.3 Admin views | `/api/v1/admin/ratings` and `/feedback` with filters and `?format=csv` (UTF-8 with BOM for Excel, formula cells neutralised), `PATCH /feedback/{id}` status |
| S6.3 Usage stats | `/api/v1/admin/stats/overview?range=7d\|30d`: active and invited dentists, answers and refusal rate, cases, drafts generated and approved, useful % per type, submit → draft p50/p90, citation validity, AI cost, daily series. `stats:roll-up` runs nightly at 00:20 Riyadh (`--days=N` back-fills); the schedule needs `php artisan schedule:work` (or cron) on the server |
| Original documents | `GET /documents/{id}/view-url` (dentist, own case) and `GET /api/v1/lawyer/documents/{id}/view-url` (only the lawyer who claimed the case) return a 5-minute signed link that streams the decrypted file with `no-store` and a sandboxing CSP. Issuing and opening are audited |
| S5.5 Push | `DraftApproved` → queued «رد معتمد جاهز» to each of the dentist's devices: generic text with the case code, payload `{type: draft_approved, case_id}`; dead tokens are cleared. `PUSH_DRIVER=log` until the Firebase project exists, then `fcm` with the service-account JSON at `FCM_CREDENTIALS` (FCM HTTP v1; the OAuth token comes from an RS256 JWT, cached 50 min) |
| S9.2 Retention | `retention:purge` nightly at 01:10 Riyadh (`--dry-run` counts only): OTP codes after 24 h, tokens unused for 30 days, `ai_runs` after 12 months, audit rows after 2 years (the trigger allows exactly that and nothing newer), and accounts deleted more than `RETENTION_ACCOUNT_GRACE_DAYS` (7) ago: cases with their encrypted files, analyses, drafts, notes, consultations, ratings, feedback, consents, devices and profile are removed and the user row is emptied to a tombstone so audit references stay valid. The case-data end-of-pilot rule waits for D18 |

115 tests, 565 assertions: `php artisan test`.

Next: the approved draft as PDF (S4.5, Should; needs a PDF library) and the mobile app's real API layer, the knowledge-base ingestion job (S1.5), push notifications.

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
