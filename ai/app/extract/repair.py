"""Repairs for text layers (PROJECT1_PLAN.md §5.2, §5.3; story S1.1).

`repair_lam_alef`: keeps the text layer's exact letters, but where a word contains «ال» it tries the variants with
«لا» instead and keeps the one the OCR of the same page actually read. The text layer gets the letters right and the
ligature wrong; OCR gets the ligature right and sometimes a letter wrong; together they are better than either.
"""

from __future__ import annotations

import itertools
import re

from rapidfuzz import fuzz, process

from app.extract.quality import is_reversed_line
from app.text.normalize import search_text

TOKEN = re.compile(r"[ء-يـ]+")
MAX_SITES = 4  # at most 2^4 variants per word
FUZZY_MIN = 85  # a variant may also match an OCR word that is slightly misread
# Frequent words of the regulations, so a repair doesn't depend on OCR reading them right on that page.
SEED_LEXICON = frozenset(
    search_text(w)
    for w in (
        "لا",
        "إلا",
        "ولا",
        "فلا",
        "خلال",
        "اللائحة",
        "لائحة",
        "اللازمة",
        "لازمة",
        "الاشتراطات",
        "اشتراطات",
        "مؤهلات",
        "مؤهلاتهم",
        "علاج",
        "العلاج",
        "العلاجية",
        "الإسلامية",
        "الإجراءات",
        "الالتزام",
        "التزامات",
        "حالات",
        "الحالات",
        "إحالة",
        "مسؤولية",
        "المسؤولية",
        "الإخلال",
        "استقلال",
        "اللاحقة",
        "خلاف",
        "الاختصاص",
    )
)


def _variants(word: str) -> list[str]:
    sites = [m.start() for m in re.finditer("ال", word)][:MAX_SITES]
    out = []
    for flips in itertools.product((False, True), repeat=len(sites)):
        chars = list(word)
        for flip, pos in zip(flips, sites, strict=True):
            if flip:
                chars[pos], chars[pos + 1] = "ل", "ا"
        out.append("".join(chars))
    return out


def _fold(word: str) -> str:
    return search_text(word).replace(" ", "")


def repair_lam_alef(text: str, ocr_words: tuple[str, ...]) -> tuple[str, int]:
    """Returns the repaired text and the number of words changed."""
    lexicon = {_fold(w) for w in ocr_words} | SEED_LEXICON
    choices = sorted(lexicon)
    changed = 0

    def fix(m: re.Match[str]) -> str:
        nonlocal changed
        word = m.group(0)
        if "ال" not in word or _fold(word) in lexicon:
            return word
        variants = _variants(word)[1:]
        for candidate in variants:
            if _fold(candidate) in lexicon:
                changed += 1
                return candidate
        # No exact match: take the variant closest to an OCR word, if clearly closer than the original.
        best, best_score = word, _similarity(_fold(word), choices)
        for candidate in variants:
            score = _similarity(_fold(candidate), choices)
            if score >= FUZZY_MIN and score > best_score + 5:
                best, best_score = candidate, score
        if best != word:
            changed += 1
        return best

    return TOKEN.sub(fix, text), changed


def _similarity(word: str, choices: list[str]) -> float:
    if len(word) < 4 or not choices:
        return 0.0
    match = process.extractOne(word, choices, scorer=fuzz.ratio)
    return float(match[1]) if match else 0.0


def ocr_agreement(text: str, ocr_words: tuple[str, ...]) -> float:
    """Share of the layer's Arabic words (3+ letters) that the OCR also read. Low agreement → the layer is unreliable."""
    words = [_fold(w) for w in TOKEN.findall(text) if len(w) >= 3]
    if not words:
        return 0.0
    lexicon = {_fold(w) for w in ocr_words}
    return sum(w in lexicon for w in words) / len(words)


def fix_reversed_lines(text: str) -> tuple[str, int]:
    """Lines whose words come out with their letters reversed («ةكلمملا ةيبرعلا») get each Arabic word reversed back."""
    fixed = 0
    lines = []
    for line in text.split("\n"):
        if is_reversed_line(line):
            line = re.sub(r"[\u0621-\u064a\u06a9\u06cc]+", lambda m: m.group(0)[::-1], line)
            fixed += 1
        lines.append(line)
    return "\n".join(lines), fixed


# Font-encoding damage seen in the dental services regulation: «ل» stored as «م», «ه» as «ي» (medial) or «و» (final).
_ENCODING_SWAPS = (("م", "ل"), ("ي", "ه"))


def _undo_encoding(word: str) -> set[str]:
    candidates = {word}
    for wrong, right in _ENCODING_SWAPS:
        candidates |= {c.replace(wrong, right) for c in candidates}
    candidates |= {c[:-1] + "ه" for c in candidates if c.endswith("و")}
    candidates |= {c[:-1] + "ها" for c in candidates if c.endswith("يا")}
    return candidates - {word}


def encoding_damage(text: str, ocr_words: tuple[str, ...]) -> float:
    """Share of the layer's words that OCR didn't read as-is but would match once the known encoding swaps are undone."""
    lexicon = {_fold(w) for w in ocr_words}
    words = [w for w in (_fold(t) for t in TOKEN.findall(text)) if len(w) >= 3]
    if not words:
        return 0.0
    explained = sum(1 for w in words if w not in lexicon and any(c in lexicon for c in _undo_encoding(w)))
    return explained / len(words)
