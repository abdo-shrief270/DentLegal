"""OCR port (PROJECT1_PLAN.md §5.3 step 2, S3.3). Production and tests: Tesseract 5 with `ara+eng` (tessdata).

PaddleOCR is the alternative named in decision D6; it would be a second implementation of this port.
"""

from __future__ import annotations

from dataclasses import dataclass
from typing import Any, Protocol

from PIL.Image import Image


@dataclass(frozen=True)
class OcrResult:
    text: str
    confidence: float  # mean word confidence, 0..1
    words: tuple[str, ...]


class Ocr(Protocol):
    name: str

    def read(self, image: Image) -> OcrResult: ...


class TesseractOcr:
    name = "tesseract"

    def __init__(self, languages: str = "ara+eng", psm: int = 6) -> None:
        import pytesseract

        self._tess: Any = pytesseract
        self._lang = languages
        self._config = f"--psm {psm}"
        self._tess.get_tesseract_version()  # fails fast if the binary is missing

    def read(self, image: Image) -> OcrResult:
        data = self._tess.image_to_data(image, lang=self._lang, config=self._config, output_type=self._tess.Output.DICT)
        lines: dict[tuple[int, int, int], list[str]] = {}
        confs: list[float] = []
        words: list[str] = []
        for i, word in enumerate(data["text"]):
            word = word.strip()
            conf = float(data["conf"][i])
            if not word or conf < 0:
                continue
            key = (data["block_num"][i], data["par_num"][i], data["line_num"][i])
            lines.setdefault(key, []).append(word)
            confs.append(conf)
            words.append(word)
        text = "\n".join(" ".join(ws) for _, ws in sorted(lines.items()))
        return OcrResult(text=text, confidence=(sum(confs) / len(confs) / 100) if confs else 0.0, words=tuple(words))
