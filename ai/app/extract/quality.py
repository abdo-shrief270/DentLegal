"""Can a PDF text layer be trusted? (PROJECT1_PLAN.md §5.3 step 2; findings in §5.2.)

Three kinds of damage were found in the supplied sources:
- `lam_alef`: the «لا» ligature extracted as «ال» («الئحة», «خالل», «إال») — repairable with the OCR lexicon;
- `encoding`: font-encoding corruption («المؤىالت العممية») — the layer is unusable, OCR or transcription instead;
- `reversed`: lines whose letters come out in reverse («ةكلمملا ةيبرعلا») — repairable by reversing the line.
"""

from __future__ import annotations

import re
from dataclasses import dataclass
from typing import Literal

ARABIC_WORD = re.compile(r"[ء-يکی]+")  # incl. Persian forms some PDF fonts use
# «ى» (alef maqsura) only ends words in correct text; inside a word it signals encoding damage («المؤىالت»).
MIDWORD_MAQSURA = re.compile(r"[ء-ي]ى[ء-ي]")
LAM_ALEF_SIGNS = re.compile(r"(?:^|\s)(?:ال|إال|وال|فال)(?=\s|$)|(?:^|\s)ا[اأإ]ل|\S+ال[ئؤ]")
REVERSED_START = re.compile(r"^[ةىی]")  # no Arabic word starts with «ة» or «ى»

Verdict = Literal["ok", "lam_alef", "encoding", "empty"]


@dataclass(frozen=True)
class LayerQuality:
    verdict: Verdict
    arabic_words: int
    encoding_rate: float
    lam_alef_signs: int


def assess(text: str) -> LayerQuality:
    words = ARABIC_WORD.findall(text)
    latin = len(re.findall(r"[A-Za-z]{2,}", text))
    if len(words) + latin < 15:
        return LayerQuality("empty", len(words), 0.0, 0)
    encoding_rate = len(MIDWORD_MAQSURA.findall(text)) / max(1, len(words))
    lam_alef = len(LAM_ALEF_SIGNS.findall(text))
    if encoding_rate > 0.01:
        return LayerQuality("encoding", len(words), encoding_rate, lam_alef)
    if lam_alef >= 2:
        return LayerQuality("lam_alef", len(words), encoding_rate, lam_alef)
    return LayerQuality("ok", len(words), encoding_rate, lam_alef)


def is_reversed_line(line: str) -> bool:
    """Signs: words starting with «ة»/«ى», or the definite article «ال» showing up at the end of words («…لا»)."""
    words = ARABIC_WORD.findall(line)
    if len(words) < 2:
        return False
    starts = sum(bool(REVERSED_START.match(w)) for w in words)
    article_at_end = sum(len(w) > 3 and w.endswith("لا") for w in words)
    return starts / len(words) >= 0.3 or article_at_end / len(words) >= 0.5
