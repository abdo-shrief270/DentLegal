# Production and staging deployment (S0.3, S0.4)

The same files run staging (Hostinger KVM 4, synthetic data) and production (KVM 8). PROJECT1_PLAN.md §8.

| File | Purpose |
|---|---|
| `compose.yaml` | the stack: `caddy` (edge, TLS), `app` (Laravel on FrankenPHP), `horizon`, `scheduler`, `ai`, `postgres`, `redis` |
| `Caddyfile` | the three hosts: API, portal (dashboard), site; baked into the edge image |
| `web.Dockerfile` | the edge image: Caddy + the dashboard and site builds for one environment |
| `postgres-init.sh` | first start: the non-superuser `dentlegal` role, its database and the extensions |
| `deploy.sh` | runs on the server for each release: pull, migrate, start, wait for health, `security:check` |
| `env/*.example` | the three settings files on the server |

Images (GHCR, tagged with the commit SHA): `dentlegal-api`, `dentlegal-ai`, and `dentlegal-web` per environment.
`.github/workflows/images.yml` builds all three on every change to their inputs; `deploy.yml` pushes them and
deploys: staging on every merge to `main`, production on a `v*` tag after approval.

## One-time server setup

As root on a fresh Ubuntu 24.04 VPS:

1. **User and SSH:** create `deploy`, add the deploy public key (and Abdelrahman's) to
   `/home/deploy/.ssh/authorized_keys`; in `/etc/ssh/sshd_config` set `PasswordAuthentication no` and
   `PermitRootLogin no`; restart `ssh`.
2. **Firewall:** `ufw allow 22,80,443/tcp && ufw allow 443/udp && ufw enable`; the same rules in hPanel's firewall.
   `apt install fail2ban unattended-upgrades`.
3. **Docker:** install Docker Engine and the Compose plugin from Docker's apt repository; `usermod -aG docker deploy`.
4. **Ops tools** (backups, `infra/ops/README.md`): `age`, `rclone`, `postgresql-client-17` (PGDG apt repository).
5. **Folders:** `mkdir -p /srv/dentlegal/documents && chown -R deploy: /srv/dentlegal`. Documents are owned by the
   app container's user: `chown 33:33 /srv/dentlegal/documents` (`www-data`).
6. **Settings:** copy the three examples to `/srv/dentlegal/.env`, `app.env` and `ai.env`, fill them in, then
   `chmod 600` and `chown deploy:` them. Generate the secrets on the server:

   ```bash
   openssl rand -base64 36
   ```

   Use a separate value for each password and token. `APP_KEY` is `base64:` followed by `openssl rand -base64 32`.
   `DOCUMENT_MASTER_KEY` is also `openssl rand -base64 32` (no prefix). `AI_SERVICE_TOKEN` must be the same in
   `app.env` and `ai.env`. Copy every value into the shared password manager, and keep `DOCUMENT_MASTER_KEY` offline
   as well: without it the stored documents can't be read.
7. **Registry access:** a GitHub token with `read:packages` only, then as `deploy`:

   ```bash
   docker login ghcr.io -u <github-user>
   ```

8. **DNS:** A (and AAAA) records for `api.`, `portal.`, the bare domain and `www.` pointing at the VPS, at the
   registrar or in Cloudflare **DNS-only** mode. Caddy gets the certificates on first start.
9. **GitHub environment** (`staging`, `production`): variables `API_HOST`, `PORTAL_HOST`, `SITE_HOST` and the site
   texts (`CONTACT_EMAIL`, `WHATSAPP`, `LEGAL_APPROVED`, store URLs); secrets `DEPLOY_HOST`, `DEPLOY_USER` (`deploy`),
   `DEPLOY_SSH_KEY` (a key used only for deploys) and `DEPLOY_KNOWN_HOSTS` (from `ssh-keyscan <host>`). Give
   `production` required reviewers.
10. **First deploy:** re-run the latest `Deploy` workflow (staging) or push a tag (production). Then, on the server,
    create the roles and the first admin's invitation:

    ```bash
    cd /srv/dentlegal && docker compose exec app php artisan db:seed --class=RoleSeeder --force
    ```

    ```bash
    docker compose exec app php artisan dentlegal:invite-admin admin@<domain> "اسم المدير"
    ```

    The second command prints a one-time link; send it to the admin privately. They set a password and enrol
    two-factor, then invite the lawyers and dentists from the portal. Finally index the sources in «قاعدة المعرفة» and
    install the cron entries from `infra/ops/README.md`.

## What a deploy does

`deploy.sh <environment> <sha> <web-tag>`: sets the tags in `.env`, pulls the images, starts Postgres and Redis,
runs `php artisan migrate --force` in a one-off container, then `docker compose up -d --wait` (it waits for the API's
and ai-svc's health checks; ai-svc loads its models for up to 3 minutes), checks `https://<api>/up` through Caddy,
and runs `php artisan security:check` (production must pass; staging only reports).

Rollback: re-run the `Deploy` workflow for the previous tag, or on the server:

```bash
bash /srv/dentlegal/deploy.sh production <previous-sha> <previous-sha>-production
```

A migration that changed data needs the snapshot taken before the release (`docs/runbook.md` §4).

## Notes

- Only Caddy publishes ports to the internet. Postgres listens on the server's loopback address for the backup
  scripts; Redis and the app are reachable only inside the Compose network.
- Laravel trusts `X-Forwarded-For` from the Compose network only (`TRUSTED_PROXIES`), so rate limits see the client.
- Staging uses the production images with `APP_ENV=staging`, the SMS log driver and the smoke-test accounts
  (`web/dashboard/e2e/README.md`). Never put real data on staging.
