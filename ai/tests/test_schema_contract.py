"""tests/schema.sql mirrors the Laravel migration; fail if a column ai-svc relies on is missing there."""

import re
from pathlib import Path

MIGRATION = Path(__file__).parents[2] / "backend/database/migrations/2026_11_23_000400_create_knowledge_tables.php"
COLUMNS = [
    "status",
    "chunk_key",
    "ordinal",
    "chunk_type",
    "article_no",
    "paragraph_ref",
    "display_text",
    "search_text",
    "heading_path",
    "embedding vector(1024)",
    "to_tsvector('arabic'::regconfig, search_text)",
    "title_ar",
    "edition",
]


def test_laravel_migration_has_every_column_ai_svc_uses() -> None:
    php = MIGRATION.read_text(encoding="utf-8")
    missing = [c for c in COLUMNS if c not in php and not re.search(rf"'{re.escape(c)}'", php)]
    assert missing == []
