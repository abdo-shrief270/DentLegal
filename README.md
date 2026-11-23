# DentLegal — Project 1

Legal-protection platform for dentists in Saudi Arabia. This repository holds **Project 1**: the AI core (RAG over Saudi regulations) and the simplified release (Flutter dentist app, Vue web dashboard for lawyers and admins, Nuxt landing page).

- **Plan:** [`PROJECT1_PLAN.md`](PROJECT1_PLAN.md) (approved, 9 weeks).
- **Web dashboard (lawyer + admin):** [`web/`](web/README.md) — Nuxt + PrimeVue; calls the Laravel API (fake data mode for previews).
- **AI service (Python):** [`ai/`](ai/README.md) — OCR, masking, hybrid retrieval over the regulations, Claude consultant and case analysis/draft with verified citations, ingestion, eval harness.
- **API (Laravel):** [`backend/`](backend/README.md) — OTP and dashboard auth (2FA), cases, encrypted documents, the analysis pipeline, consultant, lawyer review, admin, ratings, stats, retention.
- **Dentist app (Flutter):** [`mobile/`](mobile/README.md) — real API layer, biometric lock, screenshot protection (fake data mode for previews).
- **Evaluation:** [`eval/`](eval/README.md) — synthetic datasets and reports (§5.14).
- **Change requests:** [`docs/change-requests/`](docs/change-requests/) — CR-01 activation keys (waiting for the client's approval).
- **Hosting:** Hostinger VPS (plan §8). UI previews with fake data deploy from `main` to the staging VPS: [`infra/preview/`](infra/preview/README.md).

## Local development (S0.1)

Needs Docker with Compose v2.

```bash
make up
```

Builds and starts PostgreSQL 17 + pgvector, Redis, the Laravel API (http://localhost:8000), Horizon, the scheduler, ai-svc (http://localhost:8001, stand-in models, no weights) and Mailpit (http://localhost:8025). It then migrates and seeds the synthetic demo data. Sign in from the app with `0551234417`; the OTP is in `backend/storage/logs/sms-*.log`. Set `ANTHROPIC_API_KEY` in your shell before `make up` to try real Claude calls with synthetic data. `make test` runs the backend and ai-svc tests against the stack, `make down` stops it, `make reset` also drops the database. Each service also runs without Docker; see its README.

## Reference material (approved by the client)

| Path | What it is |
|---|---|
| `site/prototype/` | Interactive HTML prototype: the approved UI for the dentist app, lawyer portal and admin. Open `site/prototype/index.html`. |
| `site/screenshots/` | Screenshots of every prototype screen (`m*` mobile, `w*` web) |
| `site/docs/prd.html` | Product requirements |
| `site/docs/user-flows.html` | User flows and screen map |
| `site/docs/architecture.html` | Technical direction |
| `site/assets/tokens.css` | Design tokens (colours, font) used by the prototype |
| `*.pdf` (repo root) | Regulatory sources to index: نظام مزاولة المهن الصحية ولائحته التنفيذية (current + previous edition), اللائحة التنظيمية لخدمات طب الأسنان, الوصف الوظيفي |
| `1.jpeg`, `2.jpeg` | The client's original concept images |

The prototype uses fictional data only. No real patient data belongs in this repository.

## Previewing the prototype

```bash
cd tools && npm install && npm run serve
```

Then open http://localhost:5173/prototype/. `npm run shots` regenerates `site/screenshots/` (needs Chrome; set `CHROME_PATH`).
