from app.ingest.chunker import MAX_WORDS, OVERLAP_WORDS, chunk
from app.ingest.parser import parse

HEADER = "www.example.gov 937 PortalHeader"
LAW = [
    (1, "نظام تجريبي\nالإصدار الأول"),
    (
        2,
        f"{HEADER}\nالفصل الأول\nالترخيص\nالمادة الأولى:\nيقصد بالكلمات الآتية المعاني الموضحة أمامها:\nالممارس الصحي: كل من يرخص له بمزاولة المهنة الصحية.\nالهيئة: الهيئة السعودية للتخصصات الصحية في المملكة.",
    ),
    (
        3,
        f"{HEADER}\nاللائحة:\n1-1 تحدث الهيئة قائمة الممارسين كلما دعت الحاجة لذلك.\nالمادة الثانية:\nلا يجوز مزاولة المهنة إلا بعد الحصول على ترخيص.\nاللائحة:\n1-1 يمنح الترخيص من قبل الوزارة وفق الشروط.\n2-1 يجوز منح ترخيص محدد المدة لبعض الفئات.",
    ),
    (
        4,
        f"{HEADER}\nالفصل الثاني\nالمادة السابعة والعشرون:\nكل خطأ مهني صحي يلتزم من ارتكبه بالتعويض.\nاللائحة:\n1-27 تحدد الهيئة الصحية الشرعية مقدار التعويض.",
    ),
    (5, f"{HEADER}\nنموذج (1)\nنموذج رفع شكوى طبية\nرقم الشكوى ........ تاريخ الشكوى ........"),
    (6, f"{HEADER}\nالمادة الأولى:\nتسري هذه القواعد على لجان المخالفات الصحية في الوزارة."),
]


def test_articles_regulations_definitions_forms_and_parts() -> None:
    units = parse(LAW, "نظام تجريبي")
    kinds = [(u.kind, u.article_no, u.paragraph_ref, u.part) for u in units]
    assert ("definition", 1, None, 1) in kinds
    assert ("regulation", 2, "2/1", 1) in kinds and ("regulation", 2, "2/2", 1) in kinds
    assert ("article", 27, None, 1) in kinds and ("regulation", 27, "27/1", 1) in kinds
    assert ("form", None, None, 1) in kinds
    assert ("article", 1, None, 2) in kinds  # a second document restarting at article 1
    terms = [u.term for u in units if u.kind == "definition"]
    assert terms == ["الممارس الصحي", "الهيئة"]
    art27 = next(u for u in units if u.article_no == 27 and u.kind == "article")
    assert art27.heading_path == ["الفصل الثاني", "المادة السابعة والعشرون"]
    assert all(HEADER not in u.text for u in units)  # running header removed, «اللائحة:» kept as a marker


def test_chunk_keys_are_unique_and_parts_are_prefixed() -> None:
    chunks = chunk(parse(LAW, "نظام تجريبي"), "TEST", "1440")
    keys = [c.chunk_key for c in chunks]
    assert len(keys) == len(set(keys))
    assert "TEST:1440:A27" in keys and "TEST:1440:R27-1" in keys and "TEST:1440:P2-A1" in keys
    assert [c.ordinal for c in chunks] == list(range(1, len(chunks) + 1))


def test_sources_without_articles_split_by_headings() -> None:
    pages = [
        (
            1,
            "مدير الإدارة:\nأن يكون استشاريًا في طب الأسنان سعودي الجنسية مشهودًا له بالكفاءة والخبرة.\nالمهام الرئيسية:\nالتخطيط للخدمات العلاجية والوقائية لأمراض الفم والأسنان في المناطق الصحية.",
        )
    ]
    units = parse(pages, "لائحة تجريبية")
    assert [(u.kind, u.heading_path) for u in units] == [("section", ["مدير الإدارة"]), ("section", ["المهام الرئيسية"])]


def test_long_units_are_split_with_overlap() -> None:
    body = "\n".join(f"سطر رقم {i} " + "كلمة " * 30 for i in range(30))
    pages = [(1, "المادة الأولى:\n" + body)]
    chunks = chunk(parse(pages, "نظام"), "T", "1")
    assert len(chunks) > 1
    assert all(len(c.display_text.split()) <= MAX_WORDS + OVERLAP_WORDS + 40 for c in chunks)
    assert chunks[1].display_text.startswith("…") and chunks[1].chunk_key.endswith("-p2")
