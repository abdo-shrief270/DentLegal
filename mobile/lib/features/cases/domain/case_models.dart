import 'package:freezed_annotation/freezed_annotation.dart';

part 'case_models.freezed.dart';

/// Platform status of a case (PROJECT1_PLAN.md §1.1). The six legal stages come in Project 2.
enum CaseStatus { draft, analyzing, aiReady, lawyerReview, approved, failed }

enum CaseType { complaint, officialNotice, shariaBoard, preventive }

enum ClaimType { compensation, disciplinary, unknown }

enum RiskLevel { low, medium, high }

enum DocumentKind { complaintLetter, medicalRecord, consentForm, radiology, referral, insurance, other }

@freezed
abstract class CaseDocument with _$CaseDocument {
  const factory CaseDocument({
    required String id,
    required String ref,
    required String name,
    required DocumentKind kind,
    required int pages,
    required String sizeLabel,
    @Default(false) bool isImage,
    @Default(false) bool lowQuality,
  }) = _CaseDocument;
}

@freezed
abstract class DentalCase with _$DentalCase {
  const factory DentalCase({
    required String id,
    required String code,
    required String title,
    required CaseType caseType,
    required ClaimType claimType,
    required String authority,
    required String complaintNo,
    required DateTime receivedOn,
    required String procedure,
    required DateTime procedureDate,
    required String patientCode,
    required String facts,
    required CaseStatus status,
    required DateTime createdAt,
    required DateTime updatedAt,
    @Default([]) List<CaseDocument> documents,
    RiskLevel? risk,
    int? positionStrength,
    String? lawyerName,
    DateTime? submittedAt,
    DateTime? approvedAt,
  }) = _DentalCase;
}

/// A regulation excerpt retrieved by the engine (shown in the «نص المادة» sheet).
@freezed
abstract class RegulationSource with _$RegulationSource {
  const factory RegulationSource({
    required String ref,
    required String law,
    required String label,
    required String title,
    required String text,
    required double score,
  }) = _RegulationSource;
}

/// Citation inside an answer, analysis point or draft paragraph: a regulation (S…) or a case document (D…).
@freezed
abstract class Citation with _$Citation {
  const factory Citation({required String ref, required String label, @Default(true) bool verified}) = _Citation;

  const Citation._();

  bool get isDocument => ref.startsWith('D');
}

enum Urgency { urgent, beforeHearing, normal }

@freezed
abstract class AnalysisPoint with _$AnalysisPoint {
  const factory AnalysisPoint({required String text, @Default([]) List<Citation> citations, RiskLevel? severity, Urgency? urgency}) = _AnalysisPoint;
}

@freezed
abstract class CaseAnalysis with _$CaseAnalysis {
  const factory CaseAnalysis({
    required String caseId,
    required int positionStrength,
    required RiskLevel risk,
    required String summary,
    required List<AnalysisPoint> strengths,
    required List<AnalysisPoint> weaknesses,
    required List<AnalysisPoint> actions,
    required List<RegulationSource> sources,
    @Default(true) bool sourcesSufficient,
  }) = _CaseAnalysis;
}

@freezed
abstract class DraftParagraph with _$DraftParagraph {
  const factory DraftParagraph({required String text, @Default(false) bool legalClaim, @Default([]) List<Citation> citations}) = _DraftParagraph;

  const DraftParagraph._();

  /// A legal claim without a verified regulation citation is shown as «غير مسند».
  bool get unsupported => legalClaim && !citations.any((c) => !c.isDocument && c.verified);
}

@freezed
abstract class DraftSection with _$DraftSection {
  const factory DraftSection({required String heading, required List<DraftParagraph> paragraphs}) = _DraftSection;
}

@freezed
abstract class CaseDraft with _$CaseDraft {
  const factory CaseDraft({
    required String caseId,
    required int versionNo,
    required bool byLawyer,
    required String title,
    required String addressee,
    required String salutation,
    required DraftParagraph intro,
    required List<DraftSection> sections,
    required String closing,
    required List<String> attachments,
    String? approvedBy,
    DateTime? approvedAt,
  }) = _CaseDraft;
}

enum PipelineStep { ocr, masking, retrieval, analysis, drafting, verification }

enum PipelineStepState { pending, running, done }

@freezed
abstract class PipelineProgress with _$PipelineProgress {
  const factory PipelineProgress({required Map<PipelineStep, PipelineStepState> steps, @Default(false) bool finished, String? error}) = _PipelineProgress;

  const PipelineProgress._();

  double get fraction => steps.values.where((s) => s == PipelineStepState.done).length / steps.length;
}

/// What the dentist enters in the four-step wizard.
@freezed
abstract class NewCaseInput with _$NewCaseInput {
  const factory NewCaseInput({
    required CaseType caseType,
    required String authority,
    required String complaintNo,
    required DateTime receivedOn,
    required String title,
    required String procedure,
    required DateTime procedureDate,
    required String patientCode,
    required ClaimType claimType,
    required String facts,
    required List<CaseDocument> documents,
  }) = _NewCaseInput;
}

enum RatingTarget { consultAnswer, analysis, draft }
