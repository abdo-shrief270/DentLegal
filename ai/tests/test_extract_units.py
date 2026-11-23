"""Text-layer diagnosis and repair, on short synthetic strings shaped like the damage found in the supplied sources."""

from app.extract.quality import assess, is_reversed_line
from app.extract.repair import encoding_damage, fix_reversed_lines, ocr_agreement, repair_lam_alef
from app.text.ordinals import ordinal_to_int

CLEAN = "يجوز منح ترخيص محدد المدة واستكمال الاشتراطات اللازمة لدى الهيئة وذلك للفئات التالية شريطة الموافقة على مؤهلاتهم خلال المدة " * 2
LAM_ALEF = "الالئحة: ال يجوز منح الترخيص إال بعد استكمال االشتراطات الالزمة خالل المدة المحددة لدى الهيئة وفق الضوابط المعتمدة " * 2
ENCODING = "المؤىالت العممية مشيود لو بااللتزام والكفاءة من واقع ممارستو وسجمو الميني ولديو خبرة إدارية وعممية كافية " * 2


def test_quality_verdicts() -> None:
    assert assess(CLEAN).verdict == "ok"
    assert assess(LAM_ALEF).verdict == "lam_alef"
    assert assess(ENCODING).verdict == "encoding"
    assert assess("12 34").verdict == "empty"


def test_lam_alef_repair_uses_what_ocr_read() -> None:
    ocr_words = ("لا", "إلا", "الاشتراطات", "اللازمة", "خلال", "يجوز")
    repaired, changed = repair_lam_alef("ال يجوز إال بعد استكمال االشتراطات الالزمة خالل المدة", ocr_words)
    assert repaired == "لا يجوز إلا بعد استكمال الاشتراطات اللازمة خلال المدة"
    assert changed == 5


def test_lam_alef_repair_tolerates_ocr_misreads_and_leaves_correct_words() -> None:
    # OCR read «مؤّهلاتئهم» (noise); the fuzzy match still picks «مؤهلاتهم». «الممارس» is correct and must stay.
    repaired, _ = repair_lam_alef("الممارس الموافقة على مؤهالتهم", ("مؤهلاتئهم", "الممارس"))
    assert repaired == "الممارس الموافقة على مؤهلاتهم"


def test_encoding_damage_is_measured_against_ocr() -> None:
    ocr_words = ("المؤهلات", "العلمية", "مشهود", "له", "ممارسته", "وسجله", "المهني", "ولديه", "وعلمية")
    assert encoding_damage("المؤهلات العممية مشيود لو ممارستو وسجمو الميني ولديو", ocr_words) > 0.5
    assert encoding_damage("المؤهلات العلمية مشهود له ممارسته", ocr_words) == 0.0
    assert ocr_agreement("المؤهلات العلمية مشهود له ممارسته", ocr_words) == 1.0


def test_reversed_lines_are_fixed_word_by_word() -> None:
    assert fix_reversed_lines("ةكلمملا ةيبرعلا ةيدوعسلا\nوزارة الصحة")[0] == "المملكة العربية السعودية\nوزارة الصحة"
    assert fix_reversed_lines("Job Description يفیظولا فصولا")[0] == "Job Description الوظیفي الوصف"
    assert not is_reversed_line("لا يجوز إلا بموافقة المريض")


def test_ordinals_with_ligature_damage() -> None:
    assert ordinal_to_int("الرابعة والثالثون") == 34  # «الثلاثون» extracted as «الثالثون»
