import 'dart:async';
import 'dart:math';

import 'package:dio/dio.dart';

import '../../../core/api/api_client.dart';
import '../../../core/api/api_error.dart';
import '../domain/case_models.dart';
import '../domain/cases_repository.dart';
import 'api_mappers.dart';

/// Cases, documents, analysis and drafts against the Laravel API (PROJECT1_PLAN.md §4.2, dentist endpoints).
class HttpCasesRepository implements CasesRepository {
  HttpCasesRepository(this._api, {this.pollEvery = const Duration(seconds: 3)});

  final ApiClient _api;
  final Duration pollEvery;

  @override
  Future<List<DentalCase>> listCases() async {
    final rows = await _api.get<List<dynamic>>('/cases', query: {'per_page': 50});
    return [for (final c in rows.cast<Json>()) ApiMappers.dentalCase(c)];
  }

  @override
  Future<DentalCase> getCase(String id) async => ApiMappers.dentalCase(await _api.get<Json>('/cases/$id'));

  /// Creates the case (facts are pattern-masked by the API before storage), then uploads each picked file
  /// (encrypted at rest by the API). A failed upload leaves the case as a draft the dentist can finish later.
  @override
  Future<DentalCase> createCase(NewCaseInput input) async {
    String day(DateTime d) => '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';
    final created = await _api.post<Json>(
      '/cases',
      body: {
        'case_type': ApiMappers.wire(input.caseType),
        'authority': input.authority,
        'complaint_no': input.complaintNo,
        'received_on': day(input.receivedOn),
        'title': input.title,
        'procedure': input.procedure,
        'procedure_date': day(input.procedureDate),
        'patient_code': input.patientCode,
        'claim_type': ApiMappers.wire(input.claimType),
        'facts': input.facts,
      },
    );
    final id = created['id'] as String;
    for (final doc in input.documents.where((d) => d.localPath != null)) {
      await _api.post<Json>(
        '/cases/$id/documents',
        body: FormData.fromMap({'kind': ApiMappers.wire(doc.kind), 'file': await MultipartFile.fromFile(doc.localPath!, filename: doc.name)}),
      );
    }
    return getCase(id);
  }

  @override
  Future<void> startAnalysis(String caseId) async {
    // One key per tap, so a retry after a dropped connection doesn't start a second run.
    final key = '${DateTime.now().microsecondsSinceEpoch}-${Random.secure().nextInt(1 << 32)}';
    await _api.post<Json>('/cases/$caseId/analysis', headers: {'Idempotency-Key': key});
  }

  /// Polls the status every [pollEvery] until the run succeeds or fails. Network blips are retried, not surfaced.
  @override
  Stream<PipelineProgress> watchPipeline(String caseId) async* {
    while (true) {
      PipelineProgress progress;
      try {
        progress = ApiMappers.progress(await _api.get<Json>('/cases/$caseId/analysis/status'));
      } on ApiError catch (e) {
        if (e.code == 'network_error' || e.code == 'timeout') {
          await Future<void>.delayed(pollEvery);
          continue;
        }
        rethrow;
      }
      yield progress;
      if (progress.finished || progress.error != null) return;
      await Future<void>.delayed(pollEvery);
    }
  }

  @override
  Future<CaseAnalysis> getAnalysis(String caseId) async => ApiMappers.analysis(caseId, await _api.get<Json>('/cases/$caseId/analysis'));

  @override
  Future<CaseDraft> getDraft(String caseId) async => ApiMappers.draft(caseId, await _api.get<Json>('/cases/$caseId/draft'));

  /// The confirmation dialog has the dentist accept lawyer access; that consent is recorded, then the case is sent.
  @override
  Future<void> submitForReview(String caseId) async {
    final me = await _api.get<Json>('/me');
    final consent = ((me['consents'] as Map?)?['lawyer_access'] as Map?)?.cast<String, dynamic>();
    if (consent != null && consent['granted'] != true) {
      await _api.post<Object?>(
        '/me/consents',
        body: {
          'consents': [
            {'type': 'lawyer_access', 'version': consent['current_version']},
          ],
        },
      );
    }
    await _api.post<Json>('/cases/$caseId/submit-review');
  }

  @override
  Future<RegulationSource> getSource(Citation citation) => fetchRegulationSource(_api, citation);

  /// «نص المادة» for a regulation citation (`GET /knowledge/chunks/{id}`), also used by the consultant.
  static Future<RegulationSource> fetchRegulationSource(ApiClient api, Citation citation) async {
    final id = citation.chunkId;
    if (id == null) throw const ApiError('not_found', 'نص هذه المادة غير متاح.');
    final c = await api.get<Json>('/knowledge/chunks/$id');
    return RegulationSource(
      ref: citation.ref,
      law: c['law'] as String? ?? '',
      label: c['label'] as String? ?? citation.label,
      title: c['title'] as String? ?? '',
      text: c['text'] as String? ?? '',
      score: 0,
    );
  }

  @override
  Future<void> rate({required RatingTarget target, required String targetId, required bool useful, String? comment}) => _api.post<Json>(
    '/ratings',
    body: {
      'target_type': switch (target) {
        RatingTarget.consultAnswer => 'consultation_message',
        RatingTarget.analysis => 'analysis',
        RatingTarget.draft => 'draft_version',
      },
      'target_id': targetId,
      'useful': useful,
      if (comment != null && comment.trim().isNotEmpty) 'comment': comment.trim(),
    },
  );
}
