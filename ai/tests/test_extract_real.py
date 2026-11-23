"""Extraction on single pages of the supplied sources (repo root), with real Tesseract. Skipped if Tesseract is missing."""

import io
import shutil
from pathlib import Path

import pypdfium2 as pdfium
import pytest

from app.extract.ocr import TesseractOcr
from app.extract.pages import extract_pdf
from app.text.normalize import display_text

ROOT = Path(__file__).parents[2]
pytestmark = pytest.mark.skipif(shutil.which("tesseract") is None, reason="Tesseract is not installed")


def one_page(filename: str, page_index: int) -> bytes:
    src = pdfium.PdfDocument(ROOT / filename)
    out = pdfium.PdfDocument.new()
    out.import_pages(src, [page_index])
    buf = io.BytesIO()
    out.save(buf)
    return buf.getvalue()


@pytest.fixture(scope="module")
def ocr() -> TesseractOcr:
    return TesseractOcr()


def test_law_page_keeps_the_text_layer_with_the_ligature_repaired(ocr: TesseractOcr) -> None:
    [page] = extract_pdf(one_page("Executive-Regulations-Health-Profession.pdf", 2), ocr)
    text = display_text(page.text)
    assert page.method == "text_layer_repaired"
    assert "اللائحة" in text and "خلال" in text and "يمنح الترخيص" in text
    assert "الالئحة" not in text and "خالل" not in text


def test_encoding_corrupted_page_falls_back_to_ocr(ocr: TesseractOcr) -> None:
    [page] = extract_pdf(one_page("اللائحة التنظيمية لخدمات طب الأسنان.pdf", 2), ocr)
    text = display_text(page.text)
    assert page.method == "ocr" and page.confidence > 0.8
    assert "المؤهلات" in text and "العلمية" in text
    assert "المؤىالت" not in text


def test_reversed_header_lines_are_fixed(ocr: TesseractOcr) -> None:
    pages = extract_pdf((ROOT / "Dental-GP-Job.pdf").read_bytes(), ocr)
    assert {p.method for p in pages} == {"text_layer"}
    assert "المملكة العربية السعودية" in display_text(pages[0].text)
