"""PII masking (PROJECT1_PLAN.md §5.10). Fail-closed: if a layer can't run, nothing is returned.

Layers: 1 digits → ASCII · 2 pattern detectors · 3 label heuristics · 4 NER · 5 allowlist · 6 dentist identity
· 7 residual guard (patterns re-run on the result).

The same value always maps to the same placeholder across all texts of a case. The state that carries this between
calls holds keyed hashes of values, never the values, so it can sit in a queue payload without leaking identifiers.
"""

from __future__ import annotations

import hashlib
import hmac
import re
from dataclasses import dataclass, field
from typing import Any

from app.masking.ner import LatinNameHeuristic, Ner, Span

ARABIC_INDIC = str.maketrans("٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹", "01234567890123456789")

# Layer 2. Order matters: longer, more specific patterns first.
PATTERNS: list[tuple[str, re.Pattern[str]]] = [
    ("IBAN", re.compile(r"\bSA\d{2}[ ]?(?:\d{4}[ ]?){4}\d{4}\b", re.IGNORECASE)),
    ("EMAIL", re.compile(r"[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}")),
    ("PHONE", re.compile(r"(?<!\d)(?:\+?966|00966|0)?5\d{8}(?!\d)")),
    ("ID", re.compile(r"(?<!\d)[12]\d{9}(?!\d)")),
    ("PHONE", re.compile(r"(?<!\d)(?:\+?966|0)1[1-7]\d{7}(?!\d)")),
    ("MRN", re.compile(r"(?:رقم الملف|رقم السجل الطبي|رقم السجل|MRN|File No\.?)\s*[:：]?\s*([A-Za-z0-9\-/]{3,20})", re.IGNORECASE)),
    ("PASSPORT", re.compile(r"(?:جواز(?: السفر)?|Passport(?: No\.?)?)\s*[:：]?\s*([A-Za-z]{1,2}\d{6,9})", re.IGNORECASE)),
]

# Layer 3: the value after a label. Names take up to 4 words; addresses up to the end of the clause.
_NAME_VALUE = r"([^\n،,.:؛;()\[\]]{2,60}?)(?=\s*(?:[\n،,.؛;()]|$|\s(?:و?(?:رقم|عمر|العمر|تاريخ|الجنس|هوية|جوال))))"
LABELS: list[tuple[str, re.Pattern[str]]] = [
    ("PATIENT", re.compile(r"(?:اسم المريضة?|المريضة?\s*/|Patient(?:'s)? name)\s*[:：]?\s*" + _NAME_VALUE, re.IGNORECASE)),
    ("PERSON", re.compile(r"(?:ولي الأمر|ولي امر|المرافق|Guardian)\s*[:：]?\s*" + _NAME_VALUE, re.IGNORECASE)),
    ("DOB", re.compile(r"(?:تاريخ الميلاد|تاريخ ميلاد|DOB|Date of birth)\s*[:：]?\s*([0-9/\-\.\s]{6,12}(?:هـ|م)?)", re.IGNORECASE)),
    ("ADDRESS", re.compile(r"((?:حي|شارع|طريق)\s+[^\n،,.؛;]{2,40})")),
    ("ADDRESS", re.compile(r"(?:العنوان|Address)\s*[:：]?\s*([^\n؛;]{3,80})", re.IGNORECASE)),
]

_NUMBERED = re.compile(r"\[(ID|PHONE|EMAIL|IBAN|MRN|PASSPORT|PERSON|ADDRESS)_(\d+)\]")
PLACEHOLDER = re.compile(r"\[(?:PATIENT|DOB|(?:ID|PHONE|EMAIL|IBAN|MRN|PASSPORT|PERSON|ADDRESS)_\d+)\]|\{\{DENTIST_(?:NAME|LICENSE)\}\}")


class MaskingError(Exception):
    """Masking could not run completely. Callers must not send or store the text."""


@dataclass
class PlaceholderState:
    """Per-case numbering. `values` maps HMAC(value) → placeholder; `counters` holds the last number per kind."""

    counters: dict[str, int] = field(default_factory=dict)
    values: dict[str, str] = field(default_factory=dict)

    @classmethod
    def from_dict(cls, data: dict[str, Any] | None) -> PlaceholderState:
        if not data:
            return cls()
        return cls(counters=dict(data.get("counters", {})), values=dict(data.get("values", {})))

    def to_dict(self) -> dict[str, Any]:
        return {"counters": dict(self.counters), "values": dict(self.values)}

    @classmethod
    def continuing(cls, texts: list[str]) -> PlaceholderState:
        """For a residual pass over already-masked text: new numbers start after the highest one present."""
        state = cls()
        for kind, number in _NUMBERED.findall("\n".join(texts)):
            state.counters[kind] = max(state.counters.get(kind, 0), int(number))
        return state


@dataclass(frozen=True)
class DentistIdentity:
    name: str | None = None
    license_no: str | None = None


@dataclass
class MaskResult:
    texts: list[str]
    entity_counts: dict[str, int]
    state: PlaceholderState
    residual_pii: int


class Masker:
    def __init__(self, ner: Ner, secret: bytes) -> None:
        if not secret:
            raise MaskingError("masking secret is not configured")
        self._ner = ner
        self._latin = LatinNameHeuristic()
        self._secret = secret

    def mask(
        self,
        texts: list[str],
        allowlist: list[str] | None = None,
        dentist: DentistIdentity | None = None,
        state: PlaceholderState | None = None,
    ) -> MaskResult:
        state = state or PlaceholderState()
        counts: dict[str, int] = {}
        allow = [a.strip() for a in (allowlist or []) if a and a.strip()]
        out: list[str] = []
        residual = 0
        for text in texts:
            masked = self._mask_one(text, allow, dentist, state, counts)
            # Layer 7: nothing a pattern detector recognises may survive.
            leftovers = sum(1 for _, p in PATTERNS[:5] for _ in p.finditer(masked))
            if leftovers:
                residual += leftovers
                masked = self._apply_patterns(masked, state, counts)
            out.append(masked)
        return MaskResult(out, counts, state, residual)

    # --- layers ---

    def _mask_one(
        self, text: str, allow: list[str], dentist: DentistIdentity | None, state: PlaceholderState, counts: dict[str, int]
    ) -> str:
        text = text.translate(ARABIC_INDIC)  # layer 1
        text = self._mask_dentist(text, dentist)  # layer 6 first, so the dentist is never numbered as a person
        text = self._apply_patterns(text, state, counts)  # layer 2
        text = self._apply_labels(text, state, counts)  # layer 3
        try:
            spans = self._ner.find(text) + self._latin.find(text)  # layer 4
        except Exception as exc:  # any NER failure aborts: fail closed
            raise MaskingError("NER failed") from exc
        return self._apply_spans(text, spans, allow, state, counts)  # layer 5 inside

    @staticmethod
    def _mask_dentist(text: str, dentist: DentistIdentity | None) -> str:
        if dentist is None:
            return text
        if dentist.license_no:
            text = re.sub(re.escape(dentist.license_no), "{{DENTIST_LICENSE}}", text, flags=re.IGNORECASE)
        if dentist.name:
            core = re.sub(r"^د\.?\s*", "", dentist.name.strip())
            for variant in sorted({dentist.name.strip(), core, " ".join(core.split()[:2])}, key=len, reverse=True):
                if len(variant) >= 4:
                    text = re.sub(r"(?:د\.?\s*)?" + re.escape(variant), "{{DENTIST_NAME}}", text)
        return text

    def _apply_patterns(self, text: str, state: PlaceholderState, counts: dict[str, int]) -> str:
        for kind, pattern in PATTERNS:
            text = self._replace(pattern, kind, text, state, counts, group=1 if pattern.groups else 0)
        return text

    def _apply_labels(self, text: str, state: PlaceholderState, counts: dict[str, int]) -> str:
        for kind, pattern in LABELS:
            text = self._replace(pattern, kind, text, state, counts, group=1)
        return text

    def _replace(self, pattern: re.Pattern[str], kind: str, text: str, state: PlaceholderState, counts: dict[str, int], group: int) -> str:
        def sub(m: re.Match[str]) -> str:
            value = m.group(group).strip()
            if not value or PLACEHOLDER.fullmatch(value) or PLACEHOLDER.search(value):
                return m.group(0)
            token = self._placeholder(kind, value, state, counts)
            start, end = m.span(group)
            whole_start = m.start(0)
            prefix = m.group(0)[: start - whole_start]
            suffix = m.group(0)[end - whole_start :]
            return prefix + token + suffix

        return pattern.sub(sub, text)

    def _apply_spans(self, text: str, spans: list[Span], allow: list[str], state: PlaceholderState, counts: dict[str, int]) -> str:
        for span in sorted(spans, key=lambda s: s.start, reverse=True):
            value = text[span.start : span.end]
            if PLACEHOLDER.search(value) or any(value in a or a in value for a in allow):
                continue
            kind = "PERSON" if span.label == "PERSON" else "ADDRESS"
            text = text[: span.start] + self._placeholder(kind, value, state, counts) + text[span.end :]
        return text

    def _placeholder(self, kind: str, value: str, state: PlaceholderState, counts: dict[str, int]) -> str:
        normalized = re.sub(r"\s+", " ", value).strip().lower()
        if kind in ("PHONE", "ID", "IBAN"):
            normalized = re.sub(r"\D", "", normalized)[-9:] if kind == "PHONE" else re.sub(r"\s", "", normalized)
        key = hmac.new(self._secret, f"{kind}|{normalized}".encode(), hashlib.sha256).hexdigest()
        counts[kind] = counts.get(kind, 0) + 1
        if key in state.values:
            return state.values[key]
        if kind in ("PATIENT", "DOB"):
            token = f"[{kind}]"
        else:
            state.counters[kind] = state.counters.get(kind, 0) + 1
            token = f"[{kind}_{state.counters[kind]}]"
        state.values[key] = token
        return token
