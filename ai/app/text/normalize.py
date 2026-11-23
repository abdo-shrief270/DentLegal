"""Arabic text normalisation (PROJECT1_PLAN.md §5.3 step 3, story S1.2).

display_text: what users see and what quotes are copied from. Keeps hamza and diacritics.
search_text: what is indexed and matched. Folds letter variants and strips diacritics.
Both are pure functions so ingestion, retrieval and citation verification normalise identically.
"""

from __future__ import annotations

import re
import unicodedata

BIDI_CONTROLS = re.compile("[‎‏‪-‮⁦-⁩؜﻿]")
TATWEEL = "ـ"
DIACRITICS = re.compile("[ً-ْٰۖ-ۭ]")
ARABIC_INDIC = str.maketrans("٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹", "01234567890123456789")
# Extracted PDF text often stores lam-alef with hamza in visual order: «اإل» for «الإ», «األ» for «الأ».
REVERSED_LAM_ALEF = re.compile("ا([إأآ])ل")
PAGE_NUMBER_LINE = re.compile(r"^[ \t]*(?:صفحة|ص)?[ \t]*[-–]?[ \t]*\d{1,4}[ \t]*[-–]?[ \t]*(?:\n|$)", re.MULTILINE)
WHITESPACE = re.compile(r"[ \t  -​]+")
BLANK_LINES = re.compile(r"\n{3,}")

# Word-level fixes for corruptions seen in the supplied sources (extended from real extraction errors in S1.1).
KNOWN_FIXES = {
    "و ازرة": "وزارة",
    "ىذا": "هذا",
    "ىذه": "هذه",
    "اﻹ": "الإ",
}

SEARCH_FOLD = str.maketrans({"أ": "ا", "إ": "ا", "آ": "ا", "ٱ": "ا", "ى": "ي", "ة": "ه", "ؤ": "و", "ئ": "ي"})
PUNCTUATION_FOLD = str.maketrans({"،": ",", "؛": ";", "؟": "?", "«": '"', "»": '"', "“": '"', "”": '"', "–": "-", "—": "-"})


def display_text(raw: str) -> str:
    text = unicodedata.normalize("NFKC", raw)  # also turns presentation forms (ﻻ, ﻹ) into base letters
    text = BIDI_CONTROLS.sub("", text).replace(TATWEEL, "")
    text = REVERSED_LAM_ALEF.sub(r"ال\1", text)
    for wrong, right in KNOWN_FIXES.items():
        text = text.replace(wrong, right)
    text = text.translate(ARABIC_INDIC)
    text = PAGE_NUMBER_LINE.sub("", text)
    text = WHITESPACE.sub(" ", text)
    text = "\n".join(line.strip() for line in text.split("\n"))
    return BLANK_LINES.sub("\n\n", text).strip()


def search_text(raw: str) -> str:
    text = DIACRITICS.sub("", display_text(raw))
    text = text.translate(SEARCH_FOLD).translate(PUNCTUATION_FOLD)
    return WHITESPACE.sub(" ", text.replace("\n", " ")).strip()
