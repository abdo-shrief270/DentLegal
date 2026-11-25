#!/usr/bin/env bash
# Nightly off-site backup (PROJECT1_PLAN.md §9.6 layer 2, S0.5).
#
# 1. pg_dump -Fc of the database, encrypted with age to the recipients in AGE_RECIPIENTS_FILE.
# 2. A tar of the documents directory (each file is already encrypted by DocumentVault), encrypted the same way.
# 3. A manifest with row counts of the main tables and SHA-256 of both archives, for the restore check.
# 4. Upload of the three files to DEST: an rclone remote ("b2:bucket/dentlegal", "r2:bucket/dentlegal") or a local
#    directory ("file:///path"). The bucket's 30-day lifecycle rule removes old backups; the key is write-only.
#
# Nothing unencrypted leaves the server: the dump is piped straight into age. The private age key is not on the server.
#
# Environment (see infra/ops/README.md):
#   DEST                  rclone remote path or file:///dir                      (required)
#   AGE_RECIPIENTS_FILE   age public keys, one per line                           (required)
#   DOCUMENTS_ROOT        documents directory                                     (required)
#   PG_EXEC               command prefix to reach Postgres, e.g. "docker compose -f /srv/dentlegal/compose.yaml exec -T postgres"
#   PGDATABASE PGUSER PGHOST PGPORT PGPASSWORD   libpq settings, as usual
#   ALERT_WEBHOOK_URL     optional: POSTed a JSON message when the backup fails
set -euo pipefail

: "${DEST:?DEST is required}"
: "${AGE_RECIPIENTS_FILE:?AGE_RECIPIENTS_FILE is required}"
: "${DOCUMENTS_ROOT:?DOCUMENTS_ROOT is required}"
: "${PGDATABASE:?PGDATABASE is required}"
PG_EXEC="${PG_EXEC:-}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

fail() {
  echo "backup failed: $1" >&2
  if [[ -n "${ALERT_WEBHOOK_URL:-}" ]]; then
    curl -fsS -m 10 -H 'Content-Type: application/json' \
      -d "{\"text\":\"DentLegal backup failed on $(hostname): $1\"}" "$ALERT_WEBHOOK_URL" >/dev/null || true
  fi
  exit 1
}
trap 'fail "line $LINENO"' ERR

[[ -s "$AGE_RECIPIENTS_FILE" ]] || fail "no age recipients"
[[ -d "$DOCUMENTS_ROOT" ]] || fail "documents directory missing: $DOCUMENTS_ROOT"

psql_q() { $PG_EXEC psql -X -At -v ON_ERROR_STOP=1 -d "$PGDATABASE" -c "$1"; }

# 1. database
$PG_EXEC pg_dump -Fc --no-owner --no-privileges -d "$PGDATABASE" \
  | age -R "$AGE_RECIPIENTS_FILE" -o "$WORK/db-$STAMP.dump.age"

# 2. documents (relative paths, so they restore into any root)
tar -C "$DOCUMENTS_ROOT" -cf - . | age -R "$AGE_RECIPIENTS_FILE" -o "$WORK/documents-$STAMP.tar.age"

# 3. manifest: row counts let the restore check prove the data came back, without exposing any content.
{
  echo "{"
  echo "  \"created_at\": \"$STAMP\","
  echo "  \"host\": \"$(hostname)\","
  echo "  \"migration\": \"$(psql_q "select migration from migrations order by id desc limit 1")\","
  echo "  \"documents_files\": $(find "$DOCUMENTS_ROOT" -type f | wc -l),"
  echo "  \"rows\": {"
  first=1
  for t in users cases documents document_pages drafts draft_versions consultation_messages audit_logs knowledge_chunks citations; do
    n="$(psql_q "select count(*) from $t" 2>/dev/null || echo -1)"
    [[ $first -eq 1 ]] || echo ","
    printf '    "%s": %s' "$t" "$n"
    first=0
  done
  echo
  echo "  },"
  echo "  \"sha256\": {"
  echo "    \"db\": \"$(sha256sum "$WORK/db-$STAMP.dump.age" | cut -d' ' -f1)\","
  echo "    \"documents\": \"$(sha256sum "$WORK/documents-$STAMP.tar.age" | cut -d' ' -f1)\""
  echo "  }"
  echo "}"
} > "$WORK/manifest-$STAMP.json"

# 4. upload
if [[ "$DEST" == file://* ]]; then
  target="${DEST#file://}/$STAMP"
  mkdir -p "$target"
  cp "$WORK"/* "$target/"
else
  rclone copy --no-traverse "$WORK" "$DEST/$STAMP"
fi

echo "backup $STAMP: $(du -ch "$WORK"/*.age | tail -1 | cut -f1) uploaded to $DEST/$STAMP"
