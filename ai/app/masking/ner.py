"""Layer 4 of masking: named-entity recognition (PROJECT1_PLAN.md §5.10).

Production uses CAMeL Tools' Arabic NER. It is loaded once; if it can't load, masking refuses to run (fail-closed).
`NoNer` exists only for local work and tests, and Settings refuses it outside those environments.
"""

from __future__ import annotations

import re
from dataclasses import dataclass
from typing import Any, Protocol


@dataclass(frozen=True)
class Span:
    start: int
    end: int
    label: str  # PERSON | LOCATION


class Ner(Protocol):
    def find(self, text: str) -> list[Span]: ...


class NoNer:
    def find(self, text: str) -> list[Span]:
        return []


class CamelNer:
    """CAMeL Tools pretrained NER (PERS, LOC) on Arabic text. Imported lazily: it is a heavy optional dependency."""

    def __init__(self) -> None:
        from camel_tools.ner import NERecognizer
        from camel_tools.tokenizers.word import simple_word_tokenize

        self._recognizer: Any = NERecognizer.pretrained()
        self._tokenize: Any = simple_word_tokenize

    def find(self, text: str) -> list[Span]:
        tokens: list[str] = self._tokenize(text)
        labels: list[str] = self._recognizer.predict_sentence(tokens)
        spans: list[Span] = []
        cursor = 0
        current: Span | None = None
        for token, tag in zip(tokens, labels, strict=True):
            start = text.find(token, cursor)
            if start < 0:
                continue
            end = start + len(token)
            cursor = end
            kind = {"PERS": "PERSON", "LOC": "LOCATION"}.get(tag[2:]) if tag != "O" else None
            if kind and tag.startswith("I-") and current and current.label == kind:
                current = Span(current.start, end, kind)
                spans[-1] = current
            elif kind:
                current = Span(start, end, kind)
                spans.append(current)
            else:
                current = None
        return spans


_LATIN_NAME = re.compile(r"\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3}\b")


class LatinNameHeuristic:
    """Capitalised Latin-script name sequences (English parts of medical records). Over-masking is acceptable (§5.10)."""

    def find(self, text: str) -> list[Span]:
        return [Span(m.start(), m.end(), "PERSON") for m in _LATIN_NAME.finditer(text)]
