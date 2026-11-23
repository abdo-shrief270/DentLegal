"""Test setup: an isolated Postgres schema with the knowledge tables, synthetic chunks, and a fake Claude client."""

from __future__ import annotations

import json
import os
import re
from collections.abc import Iterator
from pathlib import Path
from types import SimpleNamespace
from typing import Any

import psycopg
import pytest

os.environ.setdefault("AI_ENV", "testing")
os.environ.setdefault("AI_EMBEDDER", "hashing")
os.environ.setdefault("AI_RERANKER", "lexical")
os.environ.setdefault("AI_NER", "none")
os.environ.setdefault("AI_SERVICE_TOKEN", "test-token")
os.environ.setdefault("AI_MASKING_SECRET", "test-masking-secret")

from app.core.config import Settings
from app.retrieval.models import HashingEmbedder, vector_literal
from app.text.normalize import display_text, search_text
from tests import fixtures

SCHEMA = "ai_test"
BASE_URL = os.environ.get("AI_TEST_DATABASE_URL", "postgresql://dentlegal:dentlegal@127.0.0.1:5432/dentlegal_test")
DB_URL = f"{BASE_URL}?options=-csearch_path%3D{SCHEMA},public"


@pytest.fixture(scope="session")
def db_url() -> str:
    with psycopg.connect(BASE_URL, autocommit=True) as conn:
        conn.execute("CREATE EXTENSION IF NOT EXISTS vector")
        conn.execute("CREATE EXTENSION IF NOT EXISTS pg_trgm")
        conn.execute(f"DROP SCHEMA IF EXISTS {SCHEMA} CASCADE")
        conn.execute(f"CREATE SCHEMA {SCHEMA}")
        conn.execute(f"SET search_path = {SCHEMA}, public")
        conn.execute((Path(__file__).parent / "schema.sql").read_text())
        for sid, code, title, kind in fixtures.SOURCES:
            conn.execute("INSERT INTO knowledge_sources (id, code, title_ar, type) VALUES (%s, %s, %s, %s)", [sid, code, title, kind])
        for vid, sid, label, edition, status in fixtures.VERSIONS:
            conn.execute(
                "INSERT INTO knowledge_source_versions (id, source_id, version_label, edition, status) VALUES (%s, %s, %s, %s, %s)",
                [vid, sid, label, edition, status],
            )
        embedder = HashingEmbedder()
        status_of = {v[0]: v[4] for v in fixtures.VERSIONS}
        title_of = {s[0]: s[2] for s in fixtures.SOURCES}
        for vid, sid, key, ordinal, ctype, article, para, path, text in fixtures.CHUNKS:
            vec = embedder.embed([" › ".join([title_of[sid], *path]) + "\n" + text])[0]
            conn.execute(
                "INSERT INTO knowledge_chunks (version_id, source_id, status, chunk_key, ordinal, chunk_type, article_no, paragraph_ref,"
                " display_text, search_text, token_count, heading_path, embedding) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s::vector)",
                [
                    vid,
                    sid,
                    status_of[vid],
                    key,
                    ordinal,
                    ctype,
                    article,
                    para,
                    display_text(text),
                    search_text(text),
                    40,
                    path,
                    vector_literal(vec),
                ],
            )
    return DB_URL


@pytest.fixture
def conn(db_url: str) -> Iterator[psycopg.Connection[Any]]:
    with psycopg.connect(db_url, autocommit=True) as c:
        yield c


@pytest.fixture
def settings(db_url: str) -> Settings:
    return Settings(database_url=db_url)


# --- fake Claude -------------------------------------------------------------------------------------------------

SOURCE_RE = re.compile(r'<source id="(S\d+)"[^>]*>\n(.*?)\n</source>', re.DOTALL)


def sources_in(kwargs: dict[str, Any]) -> dict[str, str]:
    first = kwargs["messages"][0]["content"]
    text = "\n".join(part["text"] for part in first)
    return {m.group(1): m.group(2) for m in SOURCE_RE.finditer(text)}


def message(
    payload: dict[str, Any] | None,
    stop_reason: str = "end_turn",
    category: str | None = None,
    fallback: bool = False,
    model: str = "claude-opus-5-5",
) -> SimpleNamespace:
    content = (
        [SimpleNamespace(type="thinking", thinking=""), SimpleNamespace(type="text", text=json.dumps(payload, ensure_ascii=False))]
        if payload
        else []
    )
    iterations = [SimpleNamespace(type="message"), SimpleNamespace(type="fallback_message")] if fallback else []
    return SimpleNamespace(
        content=content,
        stop_reason=stop_reason,
        model=model,
        stop_details=SimpleNamespace(category=category) if stop_reason == "refusal" else None,
        usage=SimpleNamespace(
            input_tokens=1200, output_tokens=300, cache_read_input_tokens=2000, cache_creation_input_tokens=0, iterations=iterations
        ),
        _request_id="req_test",
    )


class FakeStream:
    def __init__(self, msg: SimpleNamespace) -> None:
        self._msg = msg

    def __enter__(self) -> FakeStream:
        return self

    def __exit__(self, *exc: object) -> None:
        return None

    def __iter__(self) -> Iterator[SimpleNamespace]:
        yield SimpleNamespace(type="message_start")
        yield SimpleNamespace(type="content_block_delta")

    def get_final_message(self) -> SimpleNamespace:
        return self._msg


class FakeAnthropic:
    """Same surface as anthropic.Anthropic for beta.messages.stream. `responder(kwargs, call_no)` builds each reply."""

    def __init__(self, responder: Any) -> None:
        self.calls: list[dict[str, Any]] = []
        self._responder = responder
        self.beta = SimpleNamespace(messages=SimpleNamespace(stream=self._stream))

    def _stream(self, **kwargs: Any) -> FakeStream:
        self.calls.append(kwargs)
        return FakeStream(self._responder(kwargs, len(self.calls)))


def grounded_answer(kwargs: dict[str, Any], _n: int) -> SimpleNamespace:
    """A well-behaved model: cites S1 with an exact quote taken from the provided source."""
    src = sources_in(kwargs)
    quote = src["S1"][:60]
    return message(
        {
            "answerable": True,
            "answer": [
                {
                    "text": "يشترط النظام الحصول على موافقة المريض قبل الإجراء.",
                    "legal_claim": True,
                    "citations": [{"ref": "S1", "quote": quote}],
                }
            ],
            "suggest_lawyer": False,
            "follow_ups": ["هل يلزم أن تكون الموافقة مكتوبة؟"],
        }
    )
