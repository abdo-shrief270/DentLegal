-- Dev stack only (compose.yaml): the test database and the extensions both databases need (PROJECT1_PLAN.md §3).
CREATE DATABASE dentlegal_test OWNER dentlegal;
\connect dentlegal
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
\connect dentlegal_test
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS pg_trgm;
