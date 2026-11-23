"""Output schemas v1 (PROJECT1_PLAN.md §5.8).

Two layers: the JSON Schema sent as `output_config.format` (only what structured outputs support: no length or range
keywords, additionalProperties false, every property required), and Pydantic models that add the range and length
checks the schema can't express. A Pydantic failure is retried once, then the run fails as ai_invalid_output.
"""

from __future__ import annotations

from typing import Any

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
