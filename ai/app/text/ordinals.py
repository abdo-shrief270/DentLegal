"""Arabic ordinals and explicit article references.

Used by the structure parser («المادة الرابعة والأربعون» → 44) and by retrieval to find articles a question names
(«المادة 27», «م27», «اللائحة 40/1», «27-1»). Matching runs on search_text, so spelling variants (ة/ه, أ/ا) fold.
"""

from __future__ import annotations

import re
from dataclasses import dataclass

from app.text.normalize import search_text

# Feminine ordinals as used for «المادة», plus the masculine/accusative forms people type in questions.
_UNITS = {
    1: ["الاولي", "الاول", "الحاديه", "الحادي", "واحد"],
    2: ["الثانيه", "الثاني", "اثنان", "اثنين"],
    3: ["الثالثه", "الثالث"],
    4: ["الرابعه", "الرابع"],
    5: ["الخامسه", "الخامس"],
    6: ["السادسه", "السادس"],
    7: ["السابعه", "السابع"],
    8: ["الثامنه", "الثامن"],
    9: ["التاسعه", "التاسع"],
}
_TENS = {
    20: ["العشرون", "العشرين"],
    30: ["الثلاثون", "الثلاثين", "الثالثون", "الثالثين"],  # the last two: «لا» ligature damage in extracted PDFs
    40: ["الاربعون", "الاربعين"],
    50: ["الخمسون", "الخمسين"],
    60: ["الستون", "الستين"],
    70: ["السبعون", "السبعين"],
    80: ["الثمانون", "الثمانين"],
    90: ["التسعون", "التسعين"],
}
_TEN = ["العاشره", "العاشر"]
_TEENS_SUFFIX = ["عشره", "عشر"]


def _build_table() -> dict[str, int]:
    table: dict[str, int] = {}
    for n, words in _UNITS.items():
        for w in words:
            table.setdefault(w, n)
    for w in _TEN:
        table[w] = 10
    for n, words in _UNITS.items():
        for w in words:
            for suffix in _TEENS_SUFFIX:
                table[f"{w} {suffix}"] = 10 + n
    for tens, tens_words in _TENS.items():
        for tw in tens_words:
            table[tw] = tens
            for n, words in _UNITS.items():
                for w in words:
                    table[f"{w} و{tw}"] = tens + n
    table["المائه"] = table["الماىه"] = 100
    return table


ORDINALS = _build_table()
# Longest phrases first so «الحاديه عشره» wins over «الحاديه».
_ORDINAL_PATTERN = "|".join(sorted((re.escape(k) for k in ORDINALS), key=len, reverse=True))


def ordinal_to_int(phrase: str) -> int | None:
    """«الرابعة والأربعون» → 44. Returns None if the phrase is not an ordinal."""
    return ORDINALS.get(search_text(phrase))


@dataclass(frozen=True)
class ArticleRef:
    article_no: int
    paragraph_ref: str | None = None  # "27/1" for a regulation paragraph


# «م27» counts only when «م» starts a word (optionally after «و»), so «رقم 123» is not article 123.
_ARTICLE_DIGITS = re.compile(r"(?:الماده|ماده|(?:(?<=^)|(?<=[\s(]))و?م)\s*\.?\s*\(?(\d{1,3})\)?(?:\s*[/\-]\s*(\d{1,2}))?")
_REGULATION = re.compile(r"(?:اللاىحه|اللايحه|لاىحه|لايحه|الفقره|فقره)\s*\(?(\d{1,3})\s*[/\-]\s*(\d{1,2})\)?")
_BARE_PARAGRAPH = re.compile(r"(?<![\d/\-])(\d{1,3})\s*[/\-]\s*(\d{1,2})(?![\d/\-])")
_ARTICLE_WORDS = re.compile(rf"(?:الماده|ماده)\s+({_ORDINAL_PATTERN})")


def find_article_refs(text: str) -> list[ArticleRef]:
    """Explicit references in a question, in order of appearance, without duplicates."""
    s = search_text(text)
    found: list[tuple[int, ArticleRef]] = []
    for m in _REGULATION.finditer(s):
        found.append((m.start(), ArticleRef(int(m.group(1)), f"{int(m.group(1))}/{int(m.group(2))}")))
    for m in _ARTICLE_DIGITS.finditer(s):
        para = f"{int(m.group(1))}/{int(m.group(2))}" if m.group(2) else None
        found.append((m.start(), ArticleRef(int(m.group(1)), para)))
    for m in _ARTICLE_WORDS.finditer(s):
        found.append((m.start(), ArticleRef(ORDINALS[m.group(1)])))
    if not found:
        # "27-1" alone, only when nothing more specific was found (dates use 4-digit years and are not matched).
        for m in _BARE_PARAGRAPH.finditer(s):
            found.append((m.start(), ArticleRef(int(m.group(1)), f"{int(m.group(1))}/{int(m.group(2))}")))

    seen: set[ArticleRef] = set()
    refs: list[ArticleRef] = []
    for _, ref in sorted(found, key=lambda x: x[0]):
        if ref not in seen and 0 < ref.article_no <= 200:
            seen.add(ref)
            refs.append(ref)
    return refs
