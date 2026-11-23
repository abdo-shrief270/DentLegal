import pytest

from app.text.normalize import display_text, search_text
from app.text.ordinals import ArticleRef, find_article_refs, ordinal_to_int


def test_display_text_repairs_extraction_damage_and_keeps_hamza() -> None:
    raw = "اإللتزام‏  بالـمـوافقة\n12\n‫المادة ١٩‬"
    assert display_text(raw) == "الإلتزام بالموافقة\nالمادة 19"


def test_presentation_forms_become_letters() -> None:
    assert display_text("ﻻ") == "لا"  # ﻻ ligature


def test_search_text_folds_letter_variants_and_diacritics() -> None:
    assert search_text("المادةُ الأولى: إقرارٌ مُستنير، مسؤولية") == "الماده الاولي: اقرار مستنير, مسوولية".replace("مسوولية", "مسووليه")


@pytest.mark.parametrize(
    ("phrase", "number"),
    [
        ("الأولى", 1),
        ("العاشرة", 10),
        ("الحادية عشرة", 11),
        ("التاسعة عشرة", 19),
        ("العشرون", 20),
        ("السابعة والعشرون", 27),
        ("الرابعة والأربعون", 44),
        ("الحادي والعشرين", 21),
    ],
)
def test_ordinals(phrase: str, number: int) -> None:
    assert ordinal_to_int(phrase) == number


def test_article_references_in_questions() -> None:
    refs = find_article_refs("ماذا تقول المادة 27 واللائحة ٤٠/١ وم19 والمادة الحادية والعشرون؟")
    assert refs == [ArticleRef(27), ArticleRef(40, "40/1"), ArticleRef(19), ArticleRef(21)]


def test_numbers_that_are_not_articles_are_ignored() -> None:
    assert find_article_refs("الشكوى رقم 123 بتاريخ 2026-10-19") == []
