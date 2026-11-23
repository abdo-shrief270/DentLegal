"""Output schemas v1 (PROJECT1_PLAN.md §5.8).

Two layers: the JSON Schema sent as `output_config.format` (only what structured outputs support: no length or range
keywords, additionalProperties false, every property required), and Pydantic models that add the range and length
checks the schema can't express. A Pydantic failure is retried once, then the run fails as ai_invalid_output.
"""

from __future__ import annotations

from typing import Any, Literal

from pydantic import BaseModel, ConfigDict, Field, field_validator

SCHEMA_VERSION = "v1"

_DEFS: dict[str, Any] = {
    "cite": {
        "type": "object",
        "additionalProperties": False,
        "required": ["ref", "quote"],
        "properties": {"ref": {"type": "string", "pattern": "^[SD][0-9]{1,3}$"}, "quote": {"type": "string"}},
    },
    "para": {
        "type": "object",
        "additionalProperties": False,
        "required": ["text", "legal_claim", "citations"],
        "properties": {
            "text": {"type": "string"},
            "legal_claim": {"type": "boolean"},
            "citations": {"type": "array", "items": {"$ref": "#/$defs/cite"}},
        },
    },
}

CONSULT_SCHEMA: dict[str, Any] = {
    "type": "object",
    "additionalProperties": False,
    "required": ["answerable", "answer", "suggest_lawyer", "follow_ups"],
    "properties": {
        "answerable": {"type": "boolean"},
        "answer": {"type": "array", "items": {"$ref": "#/$defs/para"}},
        "suggest_lawyer": {"type": "boolean"},
        "follow_ups": {"type": "array", "items": {"type": "string"}},
    },
    "$defs": _DEFS,
}


class _Strict(BaseModel):
    model_config = ConfigDict(extra="forbid", str_strip_whitespace=True)


class Cite(_Strict):
    ref: str = Field(pattern=r"^[SD][0-9]{1,3}$")
    quote: str = Field(min_length=10, max_length=300)


class Para(_Strict):
    text: str = Field(min_length=1, max_length=3000)
    legal_claim: bool
    citations: list[Cite]


class ConsultAnswer(_Strict):
    answerable: bool
    answer: list[Para] = Field(min_length=1, max_length=6)
    suggest_lawyer: bool
    follow_ups: list[str] = Field(max_length=3)

    @field_validator("follow_ups")
    @classmethod
    def _short_follow_ups(cls, v: list[str]) -> list[str]:
        return [q for q in (s.strip() for s in v) if q][:3]


# --- case analysis ---------------------------------------------------------------------------------------------------

_ITEM_PROPERTIES: dict[str, Any] = {"text": {"type": "string"}, "citations": {"type": "array", "items": {"$ref": "#/$defs/cite"}}}
_ITEM: dict[str, Any] = {"type": "object", "additionalProperties": False, "required": ["text", "citations"], "properties": _ITEM_PROPERTIES}

CASE_ANALYSIS_SCHEMA: dict[str, Any] = {
    "type": "object",
    "additionalProperties": False,
    "required": [
        "position_strength",
        "risk_level",
        "summary",
        "strengths",
        "weaknesses",
        "recommended_actions",
        "missing_information",
        "sources_sufficient",
    ],
    "properties": {
        "position_strength": {"type": "integer", "description": "0-100"},
        "risk_level": {"type": "string", "enum": ["low", "medium", "high"]},
        "summary": {"type": "string"},
        "strengths": {"type": "array", "items": _ITEM},
        "weaknesses": {
            "type": "array",
            "items": {
                **_ITEM,
                "required": ["text", "severity", "citations"],
                "properties": {**_ITEM_PROPERTIES, "severity": {"type": "string", "enum": ["low", "medium", "high"]}},
            },
        },
        "recommended_actions": {
            "type": "array",
            "items": {
                **_ITEM,
                "required": ["text", "urgency", "citations"],
                "properties": {**_ITEM_PROPERTIES, "urgency": {"type": "string", "enum": ["urgent", "before_hearing", "normal"]}},
            },
        },
        "missing_information": {"type": "array", "items": {"type": "string"}},
        "sources_sufficient": {"type": "boolean"},
    },
    "$defs": _DEFS,
}


def _lower(v: object) -> object:
    return v.strip().lower() if isinstance(v, str) else v  # enum casing is not guaranteed by structured outputs


class Item(_Strict):
    text: str = Field(min_length=1, max_length=1500)
    citations: list[Cite]


class Weakness(Item):
    severity: Literal["low", "medium", "high"]

    @field_validator("severity", mode="before")
    @classmethod
    def _norm_severity(cls, v: object) -> object:
        return _lower(v)


class Action(Item):
    urgency: Literal["urgent", "before_hearing", "normal"]

    @field_validator("urgency", mode="before")
    @classmethod
    def _norm_urgency(cls, v: object) -> object:
        return _lower(v)


class CaseAnalysis(_Strict):
    position_strength: int = Field(ge=0, le=100)
    risk_level: Literal["low", "medium", "high"]
    summary: str = Field(min_length=20, max_length=3000)
    strengths: list[Item] = Field(max_length=12)
    weaknesses: list[Weakness] = Field(max_length=12)
    recommended_actions: list[Action] = Field(max_length=12)
    missing_information: list[str] = Field(max_length=12)
    sources_sufficient: bool

    @field_validator("risk_level", mode="before")
    @classmethod
    def _norm_risk_level(cls, v: object) -> object:
        return _lower(v)


# --- draft ------------------------------------------------------------------------------------------------------------

DRAFT_SCHEMA: dict[str, Any] = {
    "type": "object",
    "additionalProperties": False,
    "required": ["title", "addressee", "salutation", "intro", "sections", "closing", "attachments", "unsupported_points"],
    "properties": {
        "title": {"type": "string"},
        "addressee": {"type": "string"},
        "salutation": {"type": "string"},
        "intro": {"$ref": "#/$defs/para"},
        "sections": {
            "type": "array",
            "items": {
                "type": "object",
                "additionalProperties": False,
                "required": ["kind", "heading", "paragraphs"],
                "properties": {
                    "kind": {"type": "string", "enum": ["facts", "defenses", "requests"]},
                    "heading": {"type": "string"},
                    "paragraphs": {"type": "array", "items": {"$ref": "#/$defs/para"}},
                },
            },
        },
        "closing": {"type": "string"},
        "attachments": {"type": "array", "items": {"type": "string"}},
        "unsupported_points": {"type": "array", "items": {"type": "string"}},
    },
    "$defs": _DEFS,
}

SECTION_ORDER = ("facts", "defenses", "requests")


class DraftSection(_Strict):
    kind: Literal["facts", "defenses", "requests"]
    heading: str = Field(min_length=3, max_length=120)
    paragraphs: list[Para] = Field(min_length=1, max_length=15)

    @field_validator("kind", mode="before")
    @classmethod
    def _norm_kind(cls, v: object) -> object:
        return _lower(v)


class DraftResponse(_Strict):
    title: str = Field(min_length=5, max_length=300)
    addressee: str = Field(min_length=3, max_length=400)
    salutation: str = Field(max_length=200)
    intro: Para
    sections: list[DraftSection]
    closing: str = Field(max_length=500)
    attachments: list[str] = Field(max_length=20)
    unsupported_points: list[str] = Field(max_length=20)

    @field_validator("sections")
    @classmethod
    def _three_sections_in_order(cls, v: list[DraftSection]) -> list[DraftSection]:
        if tuple(s.kind for s in v) != SECTION_ORDER:
            raise ValueError("sections must be facts, defenses, requests in this order")
        return v

    def paragraphs(self) -> list[Para]:
        return [self.intro, *(p for s in self.sections for p in s.paragraphs)]
