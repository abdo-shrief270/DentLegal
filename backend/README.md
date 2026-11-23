# DentLegal API (Laravel)

The system of record for Project 1 (PROJECT1_PLAN.md §2, §3, §4): REST API `/api/v1` for the dentist app and the web dashboard, data ownership, permissions, the case state machine, the knowledge-base lifecycle, and orchestration of the AI work done by **ai-svc** (the Python service: OCR, PII masking, embeddings, hybrid retrieval, Claude, citation verification; decision D1).

## Stack

| Concern | Choice |
|---|---|
| Runtime | PHP 8.4 in production (the lock file is resolved for 8.3+), Laravel 12 |
| Database | PostgreSQL 17 + `pgvector` + `pg_trgm`, Arabic full-text search |
| Queues, cache, sessions, rate limits | Redis, Laravel Horizon |
| Auth | Sanctum: 30-day tokens with the `dentist` ability for the app; cookie sessions + 2FA for the dashboard (S5.7, next) |
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
    Ai/                 typed port to ai-svc (retrieve, mask, health) with retries and fail-closed masking
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
| ai-svc client | `AiService` port + HTTP adapter: retrieve, mask, consult, analyzeCase, draftCase (service token, per-route timeouts, retries on 5xx/connection errors only) |

57 tests, 178 assertions: `php artisan test`.

Next: documents and uploads (S3.2), the analysis pipeline jobs (S3.8), consultations (S2.6), dashboard auth + 2FA (S5.7), lawyer and admin APIs, and ai-svc itself.

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
