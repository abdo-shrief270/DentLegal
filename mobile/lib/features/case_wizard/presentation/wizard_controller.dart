import 'package:freezed_annotation/freezed_annotation.dart';
import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../cases/domain/case_models.dart';
import '../../cases/presentation/cases_providers.dart';
import '../../../core/api/api_config.dart';
import '../data/device_document_picker.dart';
import '../data/fake_document_picker.dart';
import '../domain/document_picker.dart';

part 'wizard_controller.freezed.dart';
part 'wizard_controller.g.dart';

@Riverpod(keepAlive: true)
DocumentPicker documentPicker(Ref ref) => ApiConfig.useRealApi ? DeviceDocumentPicker() : FakeDocumentPicker();

@freezed
abstract class WizardState with _$WizardState {
  const factory WizardState({
    @Default(1) int step,
    @Default(CaseType.complaint) CaseType caseType,
    @Default('المديرية العامة للشؤون الصحية بمحافظة جدة') String authority,
    @Default('') String complaintNo,
    DateTime? receivedOn,
    @Default('') String title,
    @Default('') String procedure,
    DateTime? procedureDate,
    @Default('') String patientCode,
    @Default(ClaimType.compensation) ClaimType claimType,
    @Default('') String facts,
    @Default([]) List<CaseDocument> documents,
    @Default(false) bool picking,
    @Default(false) bool confirmAccuracy,
    @Default(false) bool confirmProcessing,
    @Default(false) bool submitting,
  }) = _WizardState;

  const WizardState._();

  static final _idOrPhone = RegExp(r'(?<!\d)([12]\d{9}|(?:\+?966|0)?5\d{8})(?!\d)');

  /// Warns when the facts look like they contain a national ID or a phone number (FR-2.2).
  bool get factsContainIdentifier => _idOrPhone.hasMatch(facts);

  bool get stepValid => switch (step) {
    1 => authority.trim().isNotEmpty && complaintNo.trim().isNotEmpty,
    2 => title.trim().length >= 5 && procedure.trim().isNotEmpty && patientCode.trim().isNotEmpty && facts.trim().length >= 20,
    3 => documents.isNotEmpty,
    _ => confirmAccuracy && confirmProcessing,
  };
}

@riverpod
class WizardController extends _$WizardController {
  @override
  WizardState build() => const WizardState();

  void update(WizardState Function(WizardState s) change) => state = change(state);

  void next() => state = state.copyWith(step: (state.step + 1).clamp(1, 4));

  void back() => state = state.copyWith(step: (state.step - 1).clamp(1, 4));

  Future<void> pick(PickSource source) async {
    state = state.copyWith(picking: true);
    try {
      final doc = await ref.read(documentPickerProvider).pick(source);
      if (!ref.mounted) return;
      if (doc != null) {
        // The wizard asks for the complaint letter first: the first file is it unless the picker knows better.
        final kind = state.documents.isEmpty && doc.kind == DocumentKind.other ? DocumentKind.complaintLetter : doc.kind;
        state = state.copyWith(
          documents: [
            ...state.documents,
            doc.copyWith(kind: kind),
          ],
        );
      }
    } finally {
      if (ref.mounted) state = state.copyWith(picking: false);
    }
  }

  void removeDocument(String id) => state = state.copyWith(documents: state.documents.where((d) => d.id != id).toList());

  /// Creates the case and starts the analysis; returns the new case id.
  Future<String> submit() async {
    state = state.copyWith(submitting: true);
    try {
      final repo = ref.read(casesRepositoryProvider);
      final now = DateTime.now();
      final created = await repo.createCase(
        NewCaseInput(
          caseType: state.caseType,
          authority: state.authority.trim(),
          complaintNo: state.complaintNo.trim(),
          receivedOn: state.receivedOn ?? now,
          title: state.title.trim(),
          procedure: state.procedure.trim(),
          procedureDate: state.procedureDate ?? now,
          patientCode: state.patientCode.trim(),
          claimType: state.claimType,
          facts: state.facts.trim(),
          documents: state.documents,
        ),
      );
      await repo.startAnalysis(created.id);
      ref.invalidate(casesListProvider);
      return created.id;
    } finally {
      if (ref.mounted) state = state.copyWith(submitting: false);
    }
  }
}
