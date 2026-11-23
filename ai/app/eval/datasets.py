"""Eval datasets (`eval/datasets/*.yaml` at the repo root, PROJECT1_PLAN.md §5.14). Synthetic only; gold labels are
lawyer-verified before a set is marked `verified: true` (S8.2)."""

from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

import yaml

REPO_ROOT = Path(__file__).resolve().parents[3]
DATASETS = REPO_ROOT / "eval" / "datasets"


@dataclass(frozen=True)
class Gold:
    """An expected source: an article (any chunk under it counts) or one regulation paragraph."""

    source: str
    article: int | None = None
    paragraph: str | None = None

    def matches(self, source_code: str, article_no: int | None, paragraph_ref: str | None) -> bool:
        if source_code != self.source:
            return False
        if self.paragraph is not None:
            return paragraph_ref == self.paragraph
        return self.article is not None and article_no == self.article

    def __str__(self) -> str:
        return f"{self.source} {self.paragraph or self.article}"


@dataclass(frozen=True)
class ConsultItem:
    id: str
    question: str
    answerable: bool
    gold: list[Gold] = field(default_factory=list)
    topic: str = ""


@dataclass(frozen=True)
class PiiItem:
    """A synthetic text with the values that must not survive masking, and terms that must survive."""

    id: str
    text: str
    planted: list[tuple[str, str]]  # (type, value)
    keep: list[str] = field(default_factory=list)
    dentist_name: str | None = None


@dataclass(frozen=True)
class Dataset:
    name: str
    kind: str
    verified: bool
    items: list[Any]


def load(name: str, root: Path = DATASETS) -> Dataset:
    raw = yaml.safe_load((root / f"{name}.yaml").read_text(encoding="utf-8"))
    kind = raw["kind"]
    if kind == "consult":
        items: list[Any] = [
            ConsultItem(
                id=str(i["id"]),
                question=i["question"],
                answerable=bool(i["answerable"]),
                gold=[Gold(g["source"], g.get("article"), g.get("paragraph")) for g in i.get("gold", [])],
                topic=i.get("topic", ""),
            )
            for i in raw["items"]
        ]
    elif kind == "pii":
        items = [
            PiiItem(
                id=str(i["id"]),
                text=i["text"],
                planted=[(p["type"], str(p["value"])) for p in i.get("planted", [])],
                keep=[str(k) for k in i.get("keep", [])],
                dentist_name=i.get("dentist_name"),
            )
            for i in raw["items"]
        ]
    else:
        raise ValueError(f"unknown dataset kind {kind!r}")
    return Dataset(name=name, kind=kind, verified=bool(raw.get("verified", False)), items=items)
