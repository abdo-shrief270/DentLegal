"""/v1/extract (case documents, masked) and /v1/kb/ingest (sources → chunks for review)."""

import io
import json
import shutil
import uuid
from collections.abc import Iterator
from pathlib import Path
from types import SimpleNamespace
from typing import Any

import psycopg
import pytest
from fastapi.testclient import TestClient
from PIL import Image, ImageDraw, ImageFont

from app.core.config import Settings
from app.main import create_app
from tests.conftest import FakeAnthropic
from tests.test_extract_real import one_page

ROOT = Path(__file__).parents[2]
AUTH = {"X-Service-Token": "test-token"}
pytestmark = pytest.mark.skipif(shutil.which("tesseract") is None, reason="Tesseract is not installed")


def transcription(kw: dict[str, Any], n: int) -> SimpleNamespace:
    assert kw["messages"][0]["content"][0]["type"] == "image"
    text = (
        "المادة الأولى:\nيقصد بالعيادة كل منشأة لطب الأسنان مرخصة من الوزارة.\nالمادة الثانية:\nتلتزم العيادة بتعقيم الأدوات بعد كل مريض."
    )
    return SimpleNamespace(
        content=[SimpleNamespace(type="text", text=text)],
        stop_reason="end_turn",
        model="claude-opus-5-5",
        usage=SimpleNamespace(
            input_tokens=1500, output_tokens=200, cache_read_input_tokens=0, cache_creation_input_tokens=0, iterations=[]
        ),
    )


@pytest.fixture
def client(settings: Settings) -> Iterator[tuple[TestClient, FakeAnthropic]]:
    fake = FakeAnthropic(transcription)
    with TestClient(create_app(settings, anthropic_client=fake)) as tc:
        yield tc, fake


def png_with_text(lines: list[str]) -> bytes:
    image = Image.new("RGB", (1400, 120 + 80 * len(lines)), "white")
    draw = ImageDraw.Draw(image)
    font = ImageFont.load_default(size=44)
    for i, line in enumerate(lines):
        draw.text((40, 40 + 80 * i), line, fill="black", font=font)
    buf = io.BytesIO()
    image.save(buf, format="PNG")
    return buf.getvalue()


# --- /v1/extract -------------------------------------------------------------------------------------------------------


def test_scanned_image_is_ocrd_and_masked(client: tuple[TestClient, FakeAnthropic]) -> None:
    tc, _ = client
    data = png_with_text(["Referral letter", "Patient mobile 0551234567", "Consent signed before implant"])
    body = tc.post("/v1/extract", headers=AUTH, files={"file": ("scan.png", data, "image/png")}).json()
    page = body["pages"][0]
    assert body["mime"] == "image/png" and page["method"] == "ocr"
    assert "0551234567" not in page["text"] and "[PHONE_1]" in page["text"]
    assert "Consent" in page["text"]
    assert body["entity_counts"]["PHONE"] == 1


def test_pdf_pages_are_masked_with_the_case_state(client: tuple[TestClient, FakeAnthropic]) -> None:
    tc, _ = client
    options = {"allowlist": [], "placeholder_state": None}
    body = tc.post(
        "/v1/extract",
        headers=AUTH,
        files={"file": ("doc.pdf", one_page("Executive-Regulations-Health-Profession.pdf", 2), "application/pdf")},
        data={"options": json.dumps(options)},
    ).json()
    assert body["page_count"] == 1 and body["pages"][0]["method"] == "text_layer_repaired"
    assert body["low_quality"] is False and "counters" in body["placeholder_state"]


def test_unsupported_files_are_rejected(client: tuple[TestClient, FakeAnthropic]) -> None:
    tc, _ = client
    r = tc.post("/v1/extract", headers=AUTH, files={"file": ("x.docx", b"PK\x03\x04not a pdf", "application/octet-stream")})
    assert r.status_code == 415 and r.json()["error"]["code"] == "unsupported_media_type"


# --- /v1/kb/ingest ------------------------------------------------------------------------------------------------------


@pytest.fixture
def version(db_url: str) -> Iterator[dict[str, str]]:
    source_id, version_id = str(uuid.uuid4()), str(uuid.uuid4())
    code = f"T{uuid.uuid4().hex[:8].upper()}"
    with psycopg.connect(db_url, autocommit=True) as conn:
        conn.execute(
            "INSERT INTO knowledge_sources (id, code, title_ar, type) VALUES (%s, %s, %s, 'guideline')", [source_id, code, "مصدر تجريبي"]
        )
        conn.execute(
            "INSERT INTO knowledge_source_versions (id, source_id, version_label, status) VALUES (%s, %s, 'v1', 'processing')",
            [version_id, source_id],
        )
    yield {"version_id": version_id, "source_code": code, "source_title": "مصدر تجريبي", "version_label": "v1"}


def ingest(tc: TestClient, opts: dict[str, str], pdf: bytes, mode: str = "auto") -> Any:
    return tc.post(
        "/v1/kb/ingest",
        headers=AUTH,
        files={"file": ("s.pdf", pdf, "application/pdf")},
        data={"options": json.dumps({**opts, "mode": mode})},
    )


def test_ingest_writes_embedded_chunks_for_review_and_replaces_on_reingest(
    client: tuple[TestClient, FakeAnthropic], version: dict[str, str], db_url: str
) -> None:
    tc, _ = client
    pdf = (ROOT / "Dental-GP-Job.pdf").read_bytes()
    report = ingest(tc, version, pdf).json()
    assert report["chunk_count"] > 5 and report["extraction_method"] == "text_layer"
    assert report["chunks_by_type"] == {"section": report["chunk_count"]}

    with psycopg.connect(db_url, autocommit=True) as conn:
        rows = conn.execute(
            "SELECT status, embedding IS NOT NULL, tsv IS NOT NULL FROM knowledge_chunks WHERE version_id = %s", [version["version_id"]]
        ).fetchall()
    assert len(rows) == report["chunk_count"] and set(rows) == {("review", True, True)}

    again = ingest(tc, version, pdf).json()
    with psycopg.connect(db_url, autocommit=True) as conn:
        count = conn.execute("SELECT count(*) FROM knowledge_chunks WHERE version_id = %s", [version["version_id"]]).fetchone()
    assert count is not None and count[0] == again["chunk_count"]


def test_claude_transcription_mode_for_public_sources(client: tuple[TestClient, FakeAnthropic], version: dict[str, str]) -> None:
    tc, fake = client
    report = ingest(tc, version, one_page("اللائحة التنظيمية لخدمات طب الأسنان.pdf", 2), mode="llm_transcription").json()
    assert report["extraction_method"] == "llm_transcription"
    assert report["chunks_by_type"] == {"article": 2}
    assert report["transcription"]["pages"] == 1 and report["transcription"]["cost_usd"] > 0
    assert fake.calls[0]["output_config"] == {"effort": "low"} and fake.calls[0]["inference_geo"] == "us"


def test_active_versions_cannot_be_reingested(client: tuple[TestClient, FakeAnthropic], version: dict[str, str], db_url: str) -> None:
    tc, _ = client
    with psycopg.connect(db_url, autocommit=True) as conn:
        conn.execute("UPDATE knowledge_source_versions SET status = 'archived' WHERE id = %s", [version["version_id"]])
    r = ingest(tc, version, (ROOT / "Dental-GP-Job.pdf").read_bytes())
    assert r.status_code == 409 and r.json()["error"]["code"] == "invalid_state"


def test_unknown_version_and_wrong_source(client: tuple[TestClient, FakeAnthropic], version: dict[str, str]) -> None:
    tc, _ = client
    pdf = (ROOT / "Dental-GP-Job.pdf").read_bytes()
    assert ingest(tc, {**version, "version_id": str(uuid.uuid4())}, pdf).status_code == 404
    assert ingest(tc, {**version, "source_code": "OTHER"}, pdf).json()["error"]["code"] == "source_mismatch"
