# Operations scripts (S0.5)

Backups, restore checks and the host alarm from PROJECT1_PLAN.md §9.6. They need `bash`, `age`, `curl`, `python3`,
the Postgres client tools and, for the off-site bucket, `rclone`.

| Script | What it does | When |
|---|---|---|
| `backup.sh` | `pg_dump -Fc` and a tar of the documents directory, both piped straight into `age` (nothing unencrypted is written), plus a manifest with row counts and checksums; uploads all three to the off-site bucket | nightly, 02:30 Riyadh |
| `restore.sh` | downloads a backup, checks the checksums, restores into an **empty** database and documents directory, and compares every row count, the file count and the last migration with the manifest | before go-live into staging, then monthly |
| `host-alert.sh` | load per CPU, available memory and disk use against thresholds; one message when a problem starts or changes, one when it clears; `--test` sends a test alert | every 5 minutes |

The off-site bucket is decision **D29** (Backblaze B2 or Cloudflare R2, EU region): an rclone remote with a
**write-only** key on the server and a 30-day lifecycle rule on the bucket.

## Keys

- Create the age key pair **offline**: `age-keygen -o dentlegal-backup.key`. The client and Abdelrahman each keep a
  copy of the private key in their password managers. It never stays on the server.
- Put the public key (`age-keygen -y dentlegal-backup.key`) in `/etc/dentlegal/backup-recipients.txt`. A second
  recipient (one key per keeper) can go on the next line.
- For a restore, copy the private key in, run `restore.sh` and delete the key afterwards.

## Server setup

`/etc/dentlegal/ops.env` (mode 600, owner root). Postgres is published on the server's loopback address only
(`infra/prod/compose.yaml`), so the scripts use the host's client tools (`postgresql-client-17`, `age`, `rclone`):

```bash
DEST=b2:dentlegal-backups/prod
AGE_RECIPIENTS_FILE=/etc/dentlegal/backup-recipients.txt
DOCUMENTS_ROOT=/srv/dentlegal/documents
PGHOST=127.0.0.1
PGPORT=5432
PGDATABASE=dentlegal
PGUSER=dentlegal
PGPASSWORD=<POSTGRES_APP_PASSWORD from /srv/dentlegal/.env>
PG_ADMIN_USER=postgres
PG_ADMIN_PASSWORD=<POSTGRES_ADMIN_PASSWORD, for restores only>
PROD_DATABASE=dentlegal
ALERT_WEBHOOK_URL=https://…
```

`/etc/cron.d/dentlegal` (times in UTC; 02:30 Riyadh is 23:30 UTC):

```bash
30 23 * * * root set -a; . /etc/dentlegal/ops.env; /srv/dentlegal/infra/ops/backup.sh >> /var/log/dentlegal-backup.log 2>&1
*/5 * * * * root set -a; . /etc/dentlegal/ops.env; /srv/dentlegal/infra/ops/host-alert.sh > /dev/null
```

The Hostinger snapshot before each production release and the provider's own backups are separate from these
(layer 1 in §9.6). The uptime check and error tracking (Sentry/GlitchTip) are set up in their own services.

## Restore test

On staging:

```bash
set -a; . /etc/dentlegal/ops.env; set +a
AGE_IDENTITY_FILE=/root/dentlegal-backup.key \
  /srv/dentlegal/infra/ops/restore.sh b2:dentlegal-backups/prod/<stamp> dentlegal_restore /srv/restore/documents
```

The script refuses to write into the database named in `PROD_DATABASE`, into a database that already has tables, or
into a non-empty documents directory. `PG_ADMIN_USER` (default `postgres`) creates the database and the `vector` and
`pg_trgm` extensions, which the application role can't. Then point a staging app at the restored database and open
one case document in the dashboard, to prove the documents still decrypt with the production document key.

Record each restore:

| Date | Backup | Restored to | Counts | Document opened | By |
|---|---|---|---|---|---|
| 2026-11-25 | local test dump | local `dentlegal_restore_test` | all match (10 tables, 2 files) | n/a (no app keys in the test) | build check |

## Checked locally (25 Nov 2026)

- Backup of the dev database and a sample documents folder: three files, both archives age-encrypted, manifest with
  row counts and checksums.
- Restore into a new database: every row count, the file count and the last migration matched; documents identical
  byte for byte; `migrate:status` showed all 17 migrations and none pending; the audit log still rejects updates.
- Refusals: a changed byte in the dump fails the checksum before anything is decrypted; a non-empty target database
  and the production database name are refused.
- Host alarm: the test alert, a single alert for a repeated problem, and the recovery message all reached a local
  webhook.
