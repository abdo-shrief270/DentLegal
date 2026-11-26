# DentLegal production runbook

For whoever operates DentLegal after handover. Steps marked **(after S0.4)** use the production Compose file and
deploy workflow, which are set up together with the VPS.

## 1. Contacts and accounts

| Role | Who | Reach |
|---|---|---|
| Technical owner | Abdelrahman | _(phone / email at handover)_ |
| Client contact (decisions, user notices) | _(client)_ | _(at handover)_ |
| Reviewing lawyer | _(client)_ | _(at handover)_ |

| Account | Used for | Holder |
|---|---|---|
| Hostinger (hPanel) | VPS, snapshots, firewall | client, with Abdelrahman as a user |
| Domain / DNS | `<domain>`, `api.`, `portal.` | client |
| GitHub repository | code, CI, deploy | client (owner), Abdelrahman |
| Anthropic Console | AI keys per workspace, spend limits | client |
| SMS provider (D13) | OTP messages, sender ID | client |
| Email provider (D17) | dashboard invitations | client |
| Off-site backup bucket (D29) | encrypted nightly backups | client |
| Firebase | push notifications | client |
| Error tracking and uptime (D14) | alerts | client, alerts to both |
| Apple / Google developer | app releases | client |

Secrets live in the password manager shared by the client and Abdelrahman, never in git, chat or email. The document
encryption key and the backup age key are also kept offline: **without the document key, stored documents can't be
read**.

## 2. Production layout

One Hostinger KVM 8 VPS, Ubuntu 24.04, Docker Compose under `/srv/dentlegal` (PROJECT1_PLAN.md §8.3):

| Container | Role |
|---|---|
| `caddy` | TLS (Let's Encrypt), the only service with open ports (80/443); serves the dashboard and landing page builds |
| `app` | Laravel API |
| `horizon` | queues: `default`, `ai` (consultations, analysis, drafts), `ingest` (knowledge sources) |
| `scheduler` | `schedule:work`: stats roll-up 00:20, retention purge 01:10 (Riyadh) |
| `ai-svc` | masking, retrieval, Claude calls, OCR; models in memory (about 5 GB) |
| `postgres` | PostgreSQL 17 with pgvector |
| `redis` | queues and cache |

Documents are stored encrypted per file under `/srv/dentlegal/documents`. Host access: SSH key only, user `deploy`,
`ufw` allows 22/80/443, `fail2ban` on.

Health: `https://api.<domain>/up` (Laravel), `/health` on ai-svc inside the network; the uptime check calls the API
and the portal login page every minute.

## 3. Routine

| When | What | How |
|---|---|---|
| Every 5 min | host alarm | `infra/ops/host-alert.sh` from cron |
| Nightly 02:30 | encrypted backup off the server | `infra/ops/backup.sh` from cron; failures post to the alert channel |
| Nightly | stats roll-up, retention purge | scheduler container |
| Daily 07:40 | AI health digest email (refusals, citation validity, cost, failures; flagged days also go to the alert channel) | scheduler: `ai:health-digest` |
| Weekly | eval run, AI cost and refusal review | `python -m app.eval run …` on staging; admin page «سجل الذكاء الاصطناعي» |
| Weekly | citation sample | the lawyer judges the sample in «عينة الاستشهادات»; accuracy shows on the admin overview |
| Monthly | restore test into staging | `infra/ops/restore.sh`, recorded in `infra/ops/README.md` |
| Monthly | dependency and security updates | CI audits; `apt upgrade` on the host with a snapshot first |
| Before each release | Hostinger snapshot | hPanel → VPS → Snapshots |

## 4. Deploy **(after S0.4)**

- **Staging:** every merge to `main` deploys automatically.
- **Production:** create a `v*` tag; the deploy job waits for approval in the GitHub `production` environment.

Before approving:

1. CI is green on the tagged commit, and the dashboard smoke test passed against staging (`web/dashboard/e2e/README.md`).
2. Take a Hostinger snapshot.
3. If the release has a migration, read it. Migrations run with `php artisan migrate --force` during the deploy.

After the deploy:

```bash
curl -fsS https://api.<domain>/up
```

```bash
docker compose -f /srv/dentlegal/compose.yaml ps
```

```bash
docker compose -f /srv/dentlegal/compose.yaml exec app php artisan security:check
```

Then sign in to the portal and open the admin overview.

**Rollback:** redeploy the previous tag. If a migration changed data, restore the snapshot taken before the release
(this loses anything written since; tell the client first).

## 5. Common tasks

All commands run on the VPS as `deploy`, from `/srv/dentlegal`. `dc` stands for
`docker compose -f /srv/dentlegal/compose.yaml`.

| Task | Command / place |
|---|---|
| See logs | `dc logs --since 1h app horizon ai-svc` (logs never contain document text or prompts) |
| Failed jobs | Horizon at `https://api.<domain>/horizon` (admins with two-factor only) |
| Retry a failed case | the dentist taps retry in the app, or `dc exec app php artisan queue:retry <id>` |
| Restart one service | `dc restart ai-svc` |
| Check configuration | `dc exec app php artisan security:check` |
| Disable a user | admin portal → «الأطباء» or «المحامون» → «إيقاف» (signs them out everywhere) |
| Resend an invitation | admin portal → the user's row → «إعادة إرسال الدعوة» |
| Lawyer lost two-factor | the lawyer uses a recovery code; if none is left, an admin disables and re-invites the account |
| New regulation edition | admin portal → «قاعدة المعرفة» → «رفع إصدار جديد», check the chunks, then «نشر الإصدار» |
| Retention purge dry run | `dc exec app php artisan retention:purge --dry-run` |

## 6. Alerts and what to do

| Alert | Likely cause | Action |
|---|---|---|
| Uptime: API or portal down | container stopped, disk full, VPS down | `dc ps`; `df -h`; restart the stopped service; hPanel if the VPS is unreachable |
| Host: disk over the limit | logs, old images, documents growth | `docker system prune` (images only), check `/srv/dentlegal/documents` size, add disk in hPanel |
| Host: memory | ai-svc models plus load | `dc restart ai-svc`; if it repeats, look at traffic and OCR jobs |
| Backup failed | bucket key, network, disk | run `infra/ops/backup.sh` by hand and read the error; a missed night is acceptable once (RPO 24 h) |
| Many `ai_unavailable` runs | Anthropic outage or spend limit | Anthropic status page and Console spend limit; cases retry, consultations show a friendly error |
| Refusal rate or cost jump | knowledge source archived, prompt change, abuse | admin «سجل الذكاء الاصطناعي» with «المشكلات فقط»; check «قاعدة المعرفة» has an active version of each source |

## 7. Security incident (PROJECT1_PLAN.md §9.9)

1. **Detect:** an alert, an error report, a user report or something odd in the audit log.
2. **Contain** (minutes, not hours):
   - disable affected accounts in the admin portal (this revokes their tokens);
   - rotate any exposed secret: the Anthropic key in the Console, `.env` values on the VPS, then `dc up -d`;
   - block an abusive IP in `ufw` and hPanel's firewall.
3. **Assess** from the audit log: who did what and when (`audit_logs` table; read-only by design).
4. **Notify the client within 24 hours** with the facts known. The client notifies SDAIA within 72 hours where PDPL
   requires it, and the affected users without undue delay.
5. **Recover:** fix, redeploy, restore from backup if data was damaged (`infra/ops/README.md`).
6. **Post-mortem within 5 working days:** timeline, cause, fixes and who owns them.

Never send case content, documents or patient details in an incident message; refer to case codes only.

## 8. Restore after losing the server

Targets: RPO 24 h, RTO 4 h.

1. Create a new VPS of the same size (or restore the latest Hostinger backup, if the account is intact).
2. Install Docker, clone the repository, put back the `.env` files and the document key from the password manager.
3. Restore the latest off-site backup: `infra/ops/restore.sh` into the new database and `/srv/dentlegal/documents`.
4. Start the stack, run `security:check`, point DNS at the new IP, check the uptime monitor turns green.
5. Open one case document in the portal to prove the documents decrypt.

## 9. Handover checklist (S9.6)

- [ ] Production deployed from a tag; sources indexed and published; smoke test passed against production.
- [ ] Every credential in section 1 belongs to the client, with Abdelrahman added where needed.
- [ ] Rotated at handover: `APP_KEY` is **not** rotated (it would invalidate encrypted columns); rotated: Anthropic
      keys, database and Redis passwords, the ai-svc service token, SMS/email API keys, deploy SSH key, bucket key.
- [ ] Backup key and document key held offline by both the client and Abdelrahman.
- [ ] A test alert received from the host alarm, the uptime check and error tracking.
- [ ] Restore test recorded.
- [ ] Arabic admin guide (`docs/admin-guide-ar.md`) handed to the client's admin.
