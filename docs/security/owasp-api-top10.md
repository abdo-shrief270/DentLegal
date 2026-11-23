# OWASP API Security Top 10 (2023) — DentLegal Project 1

Checklist for S9.3 (PROJECT1_PLAN.md §9). Each risk lists the controls in the code and the tests that pin them down.
Reviewed 23 Nov 2026 against `main`. Residual risks are listed at the end.

| # | Risk | Controls | Tests |
|---|---|---|---|
| API1 | Broken object-level authorisation | Every dentist resource goes through a policy that answers **404** for other users' cases, documents, consultations and messages (`CasePolicy`, ownership checks in controllers). Lawyers see a draft only once it is submitted for review; edits, notes, approval and original files only for the lawyer who claimed it. Ratings only on the dentist's own answers, analyses and visible draft versions. Signed file links are bound to the document and the viewer. | `CaseApiTest` (other dentists → 404), `DocumentApiTest`, `ConsultationApiTest::test_other_dentists_threads_are_not_found`, `ReviewApiTest::test_claiming_is_first_come_first_served`, `DocumentLinksAndPushTest::test_only_the_owner_or_the_claiming_lawyer_gets_a_link`, `FeedbackAndStatsTest::test_ratings_upsert_and_are_limited_to_own_items` |
| API2 | Broken authentication | Dentists: invite-only OTP, HMAC-hashed 6-digit codes, 5-minute expiry, 5 attempts then burnt, resend 60 s, 5/h per phone, 20/h per IP; 30-day Sanctum tokens, revoked on logout, deletion and deactivation, purged after 30 idle days. Dashboard: email + password (uniform error, constant-time for unknown emails), mandatory TOTP with replay protection and one-time recovery codes, 5-minute pending login, 5/min per email+IP and per user+IP, session regenerated on login, 2 h idle timeout. API tokens never open lawyer/admin routes. | `OtpAuthTest`, `DashboardAuthTest`, `TotpTest` (RFC 6238 vectors, replay), `AdminApiTest::test_deactivation_revokes_tokens_and_blocks_the_dashboard`, `RetentionTest` |
| API3 | Broken object-property-level authorisation | Models use `$fillable`; ids and server-owned fields are set with `forceFill` in actions only. Resources whitelist output: storage keys, wrapped keys, 2FA secrets, recovery codes, FCM tokens and idempotency keys are hidden; the analysis hides the model's raw form; internal review notes never reach the app; the dentist sees the AI draft until approval, never lawyer work in progress. | `DocumentApiTest`, `ReviewApiTest::test_the_dentist_does_not_see_lawyer_work_in_progress`, `AnalysisPipelineTest` (`assertJsonMissingPath('data.raw')`) |
| API4 | Unrestricted resource consumption | Every API and dashboard route has a throttle (a test fails if one is added without). Product limits: 20 questions/h and 100/day, 10 analyses/day, 60 uploads/h, one open question per thread, one pipeline run per case. Uploads ≤ 20 MB, ≤ 15 per case, type checked by bytes; knowledge PDFs ≤ 50 MB; request bodies validated with maximum lengths; list endpoints paginated or capped. Claude calls have `max_tokens` caps, one repair retry, and a token-counted size guard that refuses rather than truncates. | `SecurityHardeningTest::test_every_api_and_dashboard_route_is_rate_limited`, `ConsultationApiTest::test_consent_and_rate_limits`, `DocumentApiTest`, ai-svc `test_case_routes.py` (size guard) |
| API5 | Broken function-level authorisation | Route groups by role: dentist (`abilities:dentist` + `role:dentist`), lawyer and admin (`auth:sanctum` + `two_factor` + `role:*`). Horizon only for admins with two-factor checked in the session. API docs only in the local environment. | `ReviewApiTest::test_access_rules`, `AdminApiTest::test_only_admins`, `FeedbackAndStatsTest::test_dentists_cannot_read_admin_views`, `SecurityHardeningTest::test_horizon_is_for_admins_with_two_factor_in_this_session`, `test_debug_and_file_surfaces_are_closed` |
| API6 | Unrestricted access to sensitive business flows | Invite-only sign-up; consents required before AI processing and before lawyer access; idempotency keys on analysis start and questions; atomic claim (one lawyer); approval with unverified citations needs an explicit confirmation; invite resend cooldown. | `AnalysisPipelineTest::test_start_queues_the_chain_and_is_idempotent`, `ConsultationApiTest::test_idempotency_key_returns_the_same_answer_row`, `ReviewApiTest`, `AdminApiTest` |
| API7 | Server-side request forgery | No endpoint fetches a URL supplied by a user. Outbound calls go only to fixed hosts from configuration: ai-svc, Anthropic, FCM, the SMS and mail providers. | Code review (no user-controlled URLs) |
| API8 | Security misconfiguration | Security headers on every response (nosniff, DENY framing, no referrer, deny-all CSP for JSON, HSTS on https, no `X-Powered-By`); CORS only for the dashboard origin with credentials; strict CSP on the dashboard and site (hashed inline scripts) plus `frame-ancestors 'none'` from Caddy; no `/storage` route; `php artisan security:check` fails a deploy with debug on, http URLs, weak keys, insecure cookies, localhost stateful domains or debug logging; ai-svc refuses production start with stand-in models, NER off or weak secrets; JSON error envelope without stack traces. | `SecurityHardeningTest` (headers, CORS, closed surfaces, `security:check`), ai-svc `test_config_safety.py`, web `scripts/csp.mjs` + browser check (no CSP violations) |
| API9 | Improper inventory management | One versioned API (`/api/v1`), routes listed in `routes/*.php` and in Scramble docs (local only). ai-svc is internal only, behind a service token, with its OpenAPI/docs endpoints disabled. CI audits dependencies on every change (`composer audit`, `pip-audit`, `pnpm audit --prod`). | CI workflows `backend.yml`, `ai.yml`, `web.yml` |
| API10 | Unsafe consumption of APIs | ai-svc responses are validated against Pydantic schemas with one retry; every citation is verified against the source text before it reaches a user; documents and regulation text are passed to Claude as escaped data blocks, never as instructions; refusals and invalid output fail closed with a code. FCM responses are classified (dead tokens removed); retries only on network/5xx. | ai-svc `test_verify.py`, `test_case_routes.py`, `test_api.py`; `DocumentLinksAndPushTest::test_fcm_v1_gateway_signs_a_jwt_and_reads_dead_tokens` |

## Dependency audit (23 Nov 2026)

| Ecosystem | Result |
|---|---|
| PHP (`composer audit`) | No advisories |
| Python (`pip-audit`) | No known vulnerabilities |
| Web (`pnpm audit --prod`) | One moderate (GHSA-g3hv-75wg-m249, minimatch, build-time only via Nitro's archiver), fixed with a pnpm override; now clean |
| Flutter | No advisory database for pub; packages pinned and current |

## Secrets review (23 Nov 2026)

No `.env` file has ever been committed. No keys, tokens or private keys appear in tracked files or in the git history (pattern scan for Anthropic, AWS, GitHub, Slack and Google keys and PEM private keys). Example env files hold empty values or `change-me` placeholders, which `security:check` and ai-svc's production check refuse. The test-only document key in `phpunit.xml` is not used anywhere else.

## Deployment requirements (checked by `security:check` or noted here)

- `TRUSTED_PROXIES` set to Caddy's network so rate limits and HSTS see the real client and scheme; the API must be reachable only through Caddy. With cached config, set it as a real environment variable (it is read in `bootstrap/app.php`).
- ai-svc and Postgres on the private Docker network only; no published ports in production.
- `OTP_REVIEW_PHONE` unset after the App Store review.

## Residual risks (accepted for P1, revisit in P2)

- OTP request reveals whether a phone is invited (`invitation_required`). Accepted: invite-only pilot, rate-limited per phone and IP.
- Styles allow `'unsafe-inline'` on the dashboard (PrimeVue injects component styles); scripts are hash-locked.
- No penetration test, certificate pinning, or root/jailbreak detection in P1 (plan §2.4, P2).
- Not yet run against the deployed staging stack: header and TLS checks on the real domain, and Lighthouse (S9.7).
