#!/usr/bin/env bash
# Restore a backup made by backup.sh into a database and documents directory, then check it against the manifest
# (PROJECT1_PLAN.md §9.6: restore into staging before go-live, then monthly).
#
#   restore.sh <source> <target-database> <target-documents-dir>
#
# <source> is the backup folder: an rclone path ("b2:bucket/dentlegal/20261125T020000Z") or file:///dir/<stamp>.
# The target database must be empty or not exist; it is never the database named in PROD_DATABASE.
#
# Environment: AGE_IDENTITY_FILE (the private age key, kept offline; copy it in only for the restore), PG_EXEC and
# the libpq settings as for backup.sh. PG_ADMIN_USER (default: postgres) creates the database and the extensions
# (vector, pg_trgm), which the application role may not (PG_ADMIN_PASSWORD if it differs); restored objects are owned
# by PGUSER.
set -euo pipefail

SRC="${1:?usage: restore.sh <source> <target-database> <target-documents-dir>}"
DB="${2:?target database}"
DOCS="${3:?target documents directory}"
: "${AGE_IDENTITY_FILE:?AGE_IDENTITY_FILE is required}"
PG_EXEC="${PG_EXEC:-}"

if [[ -n "${PROD_DATABASE:-}" && "$DB" == "$PROD_DATABASE" ]]; then
  echo "refusing to restore over the production database ($DB)" >&2
  exit 2
fi

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT

if [[ "$SRC" == file://* ]]; then cp "${SRC#file://}"/* "$WORK/"; else rclone copy "$SRC" "$WORK"; fi
manifest="$(ls "$WORK"/manifest-*.json)"
db_file="$(ls "$WORK"/db-*.dump.age)"
docs_file="$(ls "$WORK"/documents-*.tar.age)"

json() { python3 -c "import json,sys; d=json.load(open(sys.argv[1])); print(eval('d'+sys.argv[2]))" "$manifest" "$1"; }

# Integrity before anything is decrypted.
[[ "$(sha256sum "$db_file" | cut -d' ' -f1)" == "$(json "['sha256']['db']")" ]] || { echo "db archive checksum mismatch" >&2; exit 1; }
[[ "$(sha256sum "$docs_file" | cut -d' ' -f1)" == "$(json "['sha256']['documents']")" ]] || { echo "documents archive checksum mismatch" >&2; exit 1; }

PG_ADMIN_USER="${PG_ADMIN_USER:-postgres}"
psql_admin() { PGPASSWORD="${PG_ADMIN_PASSWORD:-${PGPASSWORD:-}}" $PG_EXEC psql -X -At -v ON_ERROR_STOP=1 -U "$PG_ADMIN_USER" -d "${2:-postgres}" -c "$1"; }
psql_q() { $PG_EXEC psql -X -At -v ON_ERROR_STOP=1 -d "$DB" -c "$1"; }

if [[ "$(psql_admin "select count(*) from pg_database where datname = '$DB'")" == "1" ]]; then
  tables="$(psql_q "select count(*) from information_schema.tables where table_schema = 'public'")"
  [[ "$tables" == "0" ]] || { echo "target database $DB is not empty ($tables tables)" >&2; exit 2; }
else
  psql_admin "create database \"$DB\" owner \"${PGUSER:-$(whoami)}\""
fi
# The dump's own CREATE EXTENSION IF NOT EXISTS then finds them in place.
psql_admin "create extension if not exists vector; create extension if not exists pg_trgm" "$DB"

# pg_restore needs a file to filter its table of contents: the decrypted dump stays in the private work directory and
# is deleted on exit. Extension comments are skipped (only the extension owner may set them).
umask 077
age -d -i "$AGE_IDENTITY_FILE" -o "$WORK/db.dump" "$db_file"
$PG_EXEC pg_restore -l "$WORK/db.dump" | grep -v 'COMMENT - EXTENSION' > "$WORK/toc.list"
$PG_EXEC pg_restore --no-owner --no-privileges --exit-on-error -L "$WORK/toc.list" -d "$DB" "$WORK/db.dump"
rm -f "$WORK/db.dump"

mkdir -p "$DOCS"
[[ -z "$(ls -A "$DOCS")" ]] || { echo "target documents directory $DOCS is not empty" >&2; exit 2; }
age -d -i "$AGE_IDENTITY_FILE" "$docs_file" | tar -C "$DOCS" -xf -

# Check: every table's row count and the document file count match the manifest.
failed=0
for t in $(python3 -c "import json,sys; print(' '.join(json.load(open(sys.argv[1]))['rows']))" "$manifest"); do
  want="$(json "['rows']['$t']")"
  got="$(psql_q "select count(*) from $t" 2>/dev/null || echo -1)"
  status="ok"; [[ "$want" == "$got" ]] || { status="MISMATCH"; failed=1; }
  printf '%-24s %8s %8s  %s\n' "$t" "$want" "$got" "$status"
done
want_files="$(json "['documents_files']")"
got_files="$(find "$DOCS" -type f | wc -l)"
printf '%-24s %8s %8s  %s\n' "documents (files)" "$want_files" "$got_files" "$([[ "$want_files" == "$got_files" ]] && echo ok || echo MISMATCH)"
[[ "$want_files" == "$got_files" ]] || failed=1
[[ "$(psql_q "select migration from migrations order by id desc limit 1")" == "$(json "['migration']")" ]] || { echo "migration mismatch" >&2; failed=1; }

if [[ $failed -eq 0 ]]; then echo "restore of $(json "['created_at']") verified"; else echo "restore check FAILED" >&2; exit 1; fi
