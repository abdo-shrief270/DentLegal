import 'case_models.dart';

/// Cases, documents, analysis and drafts (PROJECT1_PLAN.md §4.2, dentist endpoints).
/// Implemented by the fake repository now and by the Laravel API client later.
abstract interface class CasesRepository {
  Future<List<DentalCase>> listCases();
  Future<DentalCase> getCase(String id);
  Future<DentalCase> createCase(NewCaseInput input);

  /// Starts OCR → masking → retrieval → analysis → draft → verification.
  Future<void> startAnalysis(String caseId);

  /// Live pipeline status (the app polls `/cases/{id}/analysis/status` every 3 s).
  Stream<PipelineProgress> watchPipeline(String caseId);

  Future<CaseAnalysis> getAnalysis(String caseId);
  Future<CaseDraft> getDraft(String caseId);
  Future<void> submitForReview(String caseId);

  /// Regulation text behind a citation (`GET /knowledge/chunks/{id}`), for the «نص المادة» sheet.
  Future<RegulationSource> getSource(Citation citation);

  /// The original file through a 5-minute signed link (`GET /documents/{id}/view-url`), into memory, never to disk.
  Future<DocumentFile> openDocument(String documentId);

  Future<void> rate({required RatingTarget target, required String targetId, required bool useful, String? comment});
}
