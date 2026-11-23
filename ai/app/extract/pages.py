"""Page text from PDFs and images (PROJECT1_PLAN.md §5.3 step 2 for sources, §6.5 and S3.3 for case documents).

Per page, `auto` mode: text layer if it is sound; OCR-guided repair if only the «لا» ligature is damaged; OCR (or, for
public sources only, Claude transcription) if the layer is empty or encoding-corrupted. Reversed lines are fixed in
any text-layer result.
"""

from __future__ import annotations

import io
from collections.abc import Callable
from dataclasses import dataclass
from typing import Literal

import pypdfium2 as pdfium
from PIL import Image

from app.extract.ocr import Ocr
from app.extract.quality import assess
from app.extract.repair import encoding_damage, fix_reversed_lines, ocr_agreement, repair_lam_alef

Mode = Literal["auto", "text_layer", "ocr", "llm_transcription"]
Method = Literal["text_layer", "text_layer_repaired", "ocr", "llm_transcription"]
RENDER_DPI = 300
MAX_PAGES = 120
# Measured on the supplied sources (S1.1): health-practice law pages ≤ 0.03 encoding damage and 0.56-0.90 agreement;
# dental services regulation pages ≥ 0.06 damage. Above MAX_ENCODING_DAMAGE, or below MIN_AGREEMENT, OCR wins.
MAX_ENCODING_DAMAGE = 0.04
MIN_AGREEMENT = 0.5
MIN_OCR_CONFIDENCE = 0.75  # below this, OCR is no better (forms with dotted fields): keep the repaired layer


class ExtractionError(Exception):
    def __init__(self, code: str, message: str) -> None:
        super().__init__(message)
        self.code = code


@dataclass(frozen=True)
class PageText:
    page_no: int
    text: str  # raw (unmasked, not normalised): callers normalise or mask
    method: Method
    confidence: float  # 1.0 for a sound text layer; OCR word confidence otherwise
    layer_verdict: str


Transcriber = Callable[[Image.Image], str]


def extract_pdf(
    data: bytes, ocr: Ocr, mode: Mode = "auto", transcribe: Transcriber | None = None, max_pages: int = MAX_PAGES
) -> list[PageText]:
    try:
        pdf = pdfium.PdfDocument(data)
    except pdfium.PdfiumError as exc:
        raise ExtractionError("unreadable_pdf", "the PDF could not be opened") from exc
    if len(pdf) > max_pages:
        raise ExtractionError("too_many_pages", f"{len(pdf)} pages (limit {max_pages})")
    pages = []
    for index in range(len(pdf)):
        page = pdf[index]
        layer = page.get_textpage().get_text_range().replace("\r", "")
        pages.append(_page(index + 1, layer, _renderer(page), ocr, mode, transcribe))
    return pages


def _renderer(page: pdfium.PdfPage) -> Callable[[], Image.Image]:
    def render() -> Image.Image:
        image: Image.Image = page.render(scale=RENDER_DPI / 72).to_pil()
        return image

    return render


def extract_image(data: bytes, ocr: Ocr) -> list[PageText]:
    try:
        image = Image.open(io.BytesIO(data))
        image.load()
    except Exception as exc:
        raise ExtractionError("unreadable_image", "the image could not be opened") from exc
    result = ocr.read(image.convert("RGB"))
    return [PageText(1, result.text, "ocr", result.confidence, "empty")]


def _page(no: int, layer: str, render: Callable[[], Image.Image], ocr: Ocr, mode: Mode, transcribe: Transcriber | None) -> PageText:
    quality = assess(layer)
    if mode == "text_layer" or (mode == "auto" and quality.verdict == "ok"):
        text, _ = fix_reversed_lines(layer)
        return PageText(no, text, "text_layer", 1.0, quality.verdict)
    if mode == "llm_transcription" or (mode == "auto" and quality.verdict == "encoding" and transcribe is not None):
        if transcribe is None:
            raise ExtractionError("transcription_unavailable", "LLM transcription is not configured")
        return PageText(no, transcribe(render()), "llm_transcription", 1.0, quality.verdict)

    result = ocr.read(render())
    if mode == "auto" and quality.verdict == "lam_alef":
        text, _ = fix_reversed_lines(layer)
        repaired, _ = repair_lam_alef(text, result.words)
        # Encoding damage the «ى» check missed («عمى» for «على») is detected against what OCR read: use OCR then.
        trusted_layer = (
            encoding_damage(repaired, result.words) <= MAX_ENCODING_DAMAGE and ocr_agreement(repaired, result.words) >= MIN_AGREEMENT
        )
        if trusted_layer or result.confidence < MIN_OCR_CONFIDENCE:
            return PageText(no, repaired, "text_layer_repaired", 1.0, quality.verdict)
        return PageText(no, result.text, "ocr", result.confidence, "encoding")
    return PageText(no, result.text, "ocr", result.confidence, quality.verdict)
