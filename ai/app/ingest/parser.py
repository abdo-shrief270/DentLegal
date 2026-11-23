"""Structure parser for regulatory sources (PROJECT1_PLAN.md §5.3 step 4, story S1.3).

Laws with articles: «الفصل …» chapters, «المادة (ordinal):» articles, an «اللائحة:» block after an article whose
paragraphs start «n-m». Printed paragraph numbers don't follow the article number consistently in the supplied
editions, so the reference is computed from the article the paragraph sits under («27/1»); the printed number stays
in the text. Article 1's definitions become one unit per term. Sources without articles are split by headings.
"""

from __future__ import annotations

import re
from collections import Counter
from dataclasses import dataclass, field

from app.text.normalize import display_text, search_text
from app.text.ordinals import ordinal_to_int

CHAPTER = re.compile(r"^(?:الفصل|الباب)\s+\S+")
ARTICLE = re.compile(r"^الماده\s+(?:رقم\s+)?\(?([^:()]+?)\)?\s*:?\s*$")
REGULATION = re.compile(r"^اللايحه\s*:?\s*$")
PARAGRAPH = re.compile(r"^\(?(\d{1,2})\s*[-/]\s*(\d{1,2})\)?\s+\S")
FORM = re.compile(r"^نموذج\s*\(?\s*\d{1,2}\s*\)?\s*$")
MIN_WORDS = 4
DEFINITION = re.compile(r"^([^:.،]{2,40}):\s*(.*)$")
SENTENCE_END = re.compile(r"[.!؟?؛]\s*$")


@dataclass
class Unit:
    kind: str  # article | regulation | definition | section
    heading_path: list[str]
    lines: list[str] = field(default_factory=list)
    article_no: int | None = None
    paragraph_ref: str | None = None
    term: str | None = None
    page_from: int = 0
    page_to: int = 0
    part: int = 1  # a PDF can hold a second document whose articles restart at 1

    @property
    def text(self) -> str:
        return "\n".join(line for line in self.lines if line.strip()).strip()

    def add(self, line: str, page: int) -> None:
        if not self.lines:
            self.page_from = page
        self.lines.append(line)
        self.page_to = page


def _is_marker(line: str) -> bool:
    s = search_text(line)
    return bool(CHAPTER.match(s) or ARTICLE.match(s) or REGULATION.match(s) or FORM.match(s))


def _clean_pages(pages: list[tuple[int, str]]) -> list[tuple[int, str]]:
    """Normalise and drop running headers/footers: lines repeated on more than half the pages (never structure markers)."""
    normalised = [(no, display_text(text)) for no, text in pages]
    counts = Counter(line for _, text in normalised for line in set(text.split("\n")) if line.strip())
    boilerplate = {line for line, n in counts.items() if len(normalised) >= 4 and n > len(normalised) / 2 and not _is_marker(line)}
    return [(no, "\n".join(line for line in text.split("\n") if line not in boilerplate)) for no, text in normalised]


def parse(pages: list[tuple[int, str]], source_title: str) -> list[Unit]:
    cleaned = _clean_pages(pages)
    if not any(ARTICLE.match(search_text(line)) for _, text in cleaned for line in text.split("\n")):
        return _parse_sections(cleaned, source_title)
    return _parse_articles(cleaned, source_title)


def _parse_articles(pages: list[tuple[int, str]], source_title: str) -> list[Unit]:
    units: list[Unit] = []
    chapter: str | None = None
    article_no: int | None = None
    article_heading = ""
    part = 1
    current: Unit | None = None
    in_regulation = False
    paragraph_counter = 0
    previous_line = ""

    def path(*extra: str) -> list[str]:
        prefix = [f"الجزء {part}"] if part > 1 else []
        return [*prefix, *(p for p in (chapter, article_heading, *extra) if p)]

    def start(unit: Unit) -> Unit:
        units.append(unit)
        return unit

    for page, text in pages:
        for raw in text.split("\n"):
            line = raw.strip()
            if not line:
                continue
            s = search_text(line)
            if CHAPTER.match(s) and len(line.split()) <= 6:
                chapter, current = line, None
                previous_line = line
                continue
            if FORM.match(s):
                article_heading, in_regulation = line, False
                current = start(Unit("form", path(), part=part))
                previous_line = line
                continue
            m = ARTICLE.match(s)
            number = (ordinal_to_int(m.group(1)) or (int(m.group(1)) if m.group(1).strip().isdigit() else None)) if m else None
            if number is not None:
                if article_no is not None and number < article_no and number == 1:
                    part, chapter = part + 1, None  # a second document in the same PDF
                article_no, article_heading = number, line.rstrip(":").strip()
                in_regulation, paragraph_counter = False, 0
                current = start(Unit("article", path(), article_no=article_no, part=part))
                previous_line = line
                continue
            if article_no is None:
                continue  # title pages before the first article
            if REGULATION.match(s):
                in_regulation, current = True, None
                previous_line = line
                continue
            if in_regulation and PARAGRAPH.match(s):
                paragraph_counter += 1
                ref = f"{article_no}/{paragraph_counter}"
                current = start(Unit("regulation", path("اللائحة"), article_no=article_no, paragraph_ref=ref, part=part))
            elif (
                article_no == 1
                and not in_regulation
                and current is not None
                and current.kind in ("article", "definition")
                and (d := DEFINITION.match(line))
                and (SENTENCE_END.search(previous_line) or previous_line.endswith(":"))
            ):
                current = start(Unit("definition", path(), article_no=1, term=d.group(1).strip(), part=part))
            elif current is None:
                kind = "regulation" if in_regulation else "article"
                current = start(Unit(kind, path(*(["اللائحة"] if in_regulation else [])), article_no=article_no, part=part))
            current.add(line, page)
            previous_line = line
    return [u for u in units if len(u.text.split()) >= MIN_WORDS]


def _is_heading(line: str, next_line: str | None) -> bool:
    words = line.split()
    if not words or len(words) > 8 or SENTENCE_END.search(line) or re.match(r"^[\d\-.()]", line):
        return False
    return line.endswith(":") or (next_line is not None and len(next_line.split()) > len(words))


def _parse_sections(pages: list[tuple[int, str]], source_title: str) -> list[Unit]:
    units: list[Unit] = []
    heading = source_title
    current: Unit | None = None
    lines = [(page, line.strip()) for page, text in pages for line in text.split("\n") if line.strip()]
    for i, (page, line) in enumerate(lines):
        nxt = lines[i + 1][1] if i + 1 < len(lines) else None
        if _is_heading(line, nxt):
            heading = line.rstrip(":").strip()
            current = Unit("section", [heading])
            units.append(current)
            continue
        if current is None:
            current = Unit("section", [heading])
            units.append(current)
        current.add(line, page)
    return [u for u in units if u.text]
