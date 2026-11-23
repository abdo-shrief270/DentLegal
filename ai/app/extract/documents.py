"""Case documents → masked page text (PROJECT1_PLAN.md §6.5, §5.10; story S3.3).

The raw OCR or text-layer output never leaves this function: pages are masked (fail-closed) before they are returned,
so Laravel only ever stores masked text. Case documents are never sent to an LLM for transcription.
"""

from __future__ import annotations

from typing import Any

from app.core.errors import ServiceError
from app.extract.ocr import Ocr
from app.extract.pages import ExtractionError, PageText, extract_image, extract_pdf
from app.masking.masker import DentistIdentity, Masker, PlaceholderState

MAX_BYTES = 20 * 1024 * 1024
MAX_PAGES = 50
LOW_QUALITY = 0.6  # mean OCR confidence below this → «جودة المسح منخفضة» (§5.12)


def sniff(data: bytes) -> str:
    if data.startswith(b"%PDF-"):
        return "application/pdf"
    if data.startswith(b"\xff\xd8\xff"):
        return "image/jpeg"
    if data.startswith(b"\x89PNG\r\n\x1a\n"):
        return "image/png"
    raise ServiceError(415, "unsupported_media_type", "only PDF, JPEG and PNG are accepted")


def extract_document(
    data: bytes,
    ocr: Ocr,
    masker: Masker,
    allowlist: list[str],
    dentist: DentistIdentity | None,
    state: PlaceholderState,
) -> dict[str, Any]:
    if len(data) > MAX_BYTES:
        raise ServiceError(413, "file_too_large", "the file is larger than 20 MB")
    mime = sniff(data)
    try:
        pages: list[PageText] = extract_pdf(data, ocr, "auto", None, MAX_PAGES) if mime == "application/pdf" else extract_image(data, ocr)
    except ExtractionError as exc:
        status = 413 if exc.code == "too_many_pages" else 422
        raise ServiceError(status, exc.code, str(exc)) from exc

    masked = masker.mask([p.text for p in pages], allowlist, dentist, state)
    ocr_confidences = [p.confidence for p in pages if p.method == "ocr"]
    mean = sum(ocr_confidences) / len(ocr_confidences) if ocr_confidences else 1.0
    return {
        "mime": mime,
        "page_count": len(pages),
        "pages": [
            {
                "page_no": p.page_no,
                "text": text,
                "method": p.method,
                "confidence": round(p.confidence, 3),
                "low_quality": p.confidence < LOW_QUALITY,
            }
            for p, text in zip(pages, masked.texts, strict=True)
        ],
        "ocr_confidence": round(mean, 3),
        "low_quality": mean < LOW_QUALITY,
        "entity_counts": masked.entity_counts,
        "placeholder_state": masked.state.to_dict(),
        "residual_pii": masked.residual_pii,
    }
