-- Mirror of backend/database/migrations/2026_11_23_000400_create_knowledge_tables.php (the source of truth).
-- tests/test_schema_contract.py fails if a column used here disappears from the Laravel migration.
CREATE TABLE knowledge_sources (
    id uuid PRIMARY KEY,
    code varchar(40) UNIQUE NOT NULL,
    title_ar varchar(200) NOT NULL,
    issuer varchar(160),
    type varchar(20) NOT NULL
);
CREATE TABLE knowledge_source_versions (
    id uuid PRIMARY KEY,
    source_id uuid NOT NULL REFERENCES knowledge_sources(id),
    version_label varchar(60) NOT NULL,
    edition varchar(120),
    status varchar(12) NOT NULL DEFAULT 'uploaded',
    chunk_count integer NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX ksv_one_active_per_source ON knowledge_source_versions (source_id) WHERE status = 'active';
CREATE TABLE knowledge_chunks (
    id bigserial PRIMARY KEY,
    version_id uuid NOT NULL REFERENCES knowledge_source_versions(id),
    source_id uuid NOT NULL REFERENCES knowledge_sources(id),
    status varchar(12) NOT NULL,
    chunk_key varchar(80) NOT NULL,
    ordinal integer NOT NULL,
    chunk_type varchar(16) NOT NULL,
    article_no smallint,
    paragraph_ref varchar(20),
    page_from smallint,
    page_to smallint,
    display_text text NOT NULL,
    search_text text NOT NULL,
    token_count smallint NOT NULL,
    heading_path text[] NOT NULL DEFAULT '{}',
    embedding vector(1024),
    tsv tsvector GENERATED ALWAYS AS (to_tsvector('arabic'::regconfig, search_text)) STORED,
    CONSTRAINT kc_active_embedded CHECK (status <> 'active' OR embedding IS NOT NULL)
);
CREATE INDEX kc_embedding_hnsw ON knowledge_chunks USING hnsw (embedding vector_cosine_ops) WITH (m = 16, ef_construction = 64) WHERE status = 'active';
CREATE INDEX kc_tsv_gin ON knowledge_chunks USING gin (tsv) WHERE status = 'active';
CREATE INDEX kc_trgm_gin ON knowledge_chunks USING gin (search_text gin_trgm_ops) WHERE status = 'active';
