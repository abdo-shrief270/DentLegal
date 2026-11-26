#!/bin/sh
# First start of the production database: the application role (not a superuser), its database and the extensions
# the migrations need. The `postgres` superuser is only for administration and restores (infra/ops/restore.sh).
set -eu
psql -v ON_ERROR_STOP=1 --username postgres <<SQL
CREATE ROLE dentlegal LOGIN PASSWORD '${POSTGRES_APP_PASSWORD}';
CREATE DATABASE dentlegal OWNER dentlegal;
\connect dentlegal
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
SQL
