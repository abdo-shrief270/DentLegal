import json

import pytest

from app.masking.masker import DentistIdentity, Masker, MaskingError, PlaceholderState
from app.masking.ner import NoNer, Span

SECRET = b"test-secret"


class FakeNer:
    def __init__(self, names: list[str]) -> None:
        self.names = names

    def find(self, text: str) -> list[Span]:
        spans = []
        for n in self.names:
            i = text.find(n)
            if i >= 0:
                spans.append(Span(i, i + len(n), "PERSON"))
        return spans


class BrokenNer:
    def find(self, text: str) -> list[Span]:
        raise RuntimeError("model not loaded")


def test_identifiers_are_masked_with_consistent_placeholders() -> None:
    m = Masker(NoNer(), SECRET)
    result = m.mask(
        [
            "هوية المريض 1023456789 وجواله ٠٥٥١٢٣٤٥٦٧ وبريده p@example.com وآيبان SA0380000000608010167519",
            "اتصل مجددًا من +966551234567 ومن 0559876543",
        ]
    )
    first, second = result.texts
    assert first == "هوية المريض [ID_1] وجواله [PHONE_1] وبريده [EMAIL_1] وآيبان [IBAN_1]"
    assert second == "اتصل مجددًا من [PHONE_1] ومن [PHONE_2]"
    assert result.residual_pii == 0


def test_labels_mask_names_dates_and_addresses() -> None:
    text = "اسم المريض: سالم عبدالرحمن الحربي، تاريخ الميلاد: 1990/05/14، العنوان: جدة حي الروضة شارع التحلية. رقم الملف: MR-88213"
    masked = Masker(NoNer(), SECRET).mask([text]).texts[0]
    for leaked in ("سالم", "1990", "الروضة", "MR-88213"):
        assert leaked not in masked
    assert "[PATIENT]" in masked and "[DOB]" in masked and "[MRN_1]" in masked


def test_building_numbers_in_an_address_are_masked() -> None:
    masked = Masker(NoNer(), SECRET).mask(["العنوان: الرياض، حي النرجس، فيلا 12، وتم العلاج في السن 12. Villa No. 7B"]).texts[0]
    assert "فيلا 12" not in masked and "7B" not in masked
    assert "السن 12" in masked


def test_state_carries_numbering_across_calls_without_holding_values() -> None:
    m = Masker(NoNer(), SECRET)
    first = m.mask(["الجوال 0551234567"])
    state = PlaceholderState.from_dict(json.loads(json.dumps(first.state.to_dict())))
    second = m.mask(["الجوال 0551234567 والآخر 0559876543"], state=state)
    assert second.texts[0] == "الجوال [PHONE_1] والآخر [PHONE_2]"
    assert "0551234567" not in json.dumps(second.state.to_dict())


def test_dentist_identity_becomes_template_variables() -> None:
    m = Masker(NoNer(), SECRET)
    text = "راجع د. أحمد الغامدي، ترخيص رقم 21-RD-0045871، العيادة"
    masked = m.mask([text], dentist=DentistIdentity("د. أحمد بن سعد الغامدي", "21-RD-0045871")).texts[0]
    assert "{{DENTIST_LICENSE}}" in masked
    assert "21-RD-0045871" not in masked


def test_ner_names_are_masked_but_allowlisted_terms_are_kept() -> None:
    m = Masker(FakeNer(["خالد السبيعي", "جدة"]), SECRET)
    masked = m.mask(["حضر خالد السبيعي إلى مديرية جدة"], allowlist=["جدة"]).texts[0]
    assert masked == "حضر [PERSON_1] إلى مديرية جدة"


def test_latin_names_are_masked() -> None:
    masked = Masker(NoNer(), SECRET).mask(["Referred by Sarah Johnson for CBCT"]).texts[0]
    assert "Sarah" not in masked


def test_ner_failure_fails_closed() -> None:
    with pytest.raises(MaskingError):
        Masker(BrokenNer(), SECRET).mask(["نص"])


def test_secret_is_required() -> None:
    with pytest.raises(MaskingError):
        Masker(NoNer(), b"")


def test_phone_numbers_written_in_groups_are_masked() -> None:
    # Found by the pii_v1 eval: spaced and dashed mobiles, with bidi marks, leaked before.
    m = Masker(NoNer(), SECRET)
    text = "اتصلوا على ‎+966 50 111 2233‎ أو 055-123-4567 أو 0551234567، والشكوى رقم 2026-100-55."
    out = m.mask([text]).texts[0]
    assert "111 2233" not in out and "123-4567" not in out and "0551234567" not in out
    assert out.count("[PHONE_") == 3
    assert "2026-100-55" in out
