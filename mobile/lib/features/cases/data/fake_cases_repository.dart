import 'dart:typed_data';
import 'dart:convert';
import 'dart:async';

import '../../../core/fake/fake_latency.dart';
import '../../auth/domain/auth_models.dart';
import '../domain/case_models.dart';
import '../domain/cases_repository.dart';
import 'fake_data.dart';

/// In-memory cases with a simulated analysis pipeline. Changes last until the app restarts.
class FakeCasesRepository implements CasesRepository {
  FakeCasesRepository({required DentistProfile? Function() currentProfile, DateTime Function()? clock})
    : _currentProfile = currentProfile, // ignore: prefer_initializing_formals
      _clock = clock ?? DateTime.now {
    _cases.addAll(seedCases(_clock()));
  }

  final DentistProfile? Function() _currentProfile;
  final DateTime Function() _clock;
  final List<DentalCase> _cases = [];
  final Map<String, StreamController<PipelineProgress>> _pipelines = {};
  final Map<String, PipelineProgress> _progress = {};
  int _next = 160;

  /// How long each pipeline step takes in the demo (the real target is under 10 minutes in total).
  Duration stepDuration = const Duration(milliseconds: 1100);

  DentalCase _find(String id) => _cases.firstWhere((c) => c.id == id, orElse: () => throw StateError('case not found: $id'));

  void _replace(DentalCase updated) => _cases[_cases.indexWhere((c) => c.id == updated.id)] = updated;

  @override
  Future<List<DentalCase>> listCases() async {
    await fakeLatency();
    return [..._cases]..sort((a, b) => b.updatedAt.compareTo(a.updatedAt));
  }

  @override
  Future<DentalCase> getCase(String id) async {
    await fakeLatency(250);
    return _find(id);
  }

  @override
  Future<DentalCase> createCase(NewCaseInput input) async {
    await fakeLatency(700);
    final now = _clock();
    final created = DentalCase(
      id: 'c-${_next++}',
      code: 'DL-${now.year}-0${_next - 1}',
      title: input.title,
      caseType: input.caseType,
      claimType: input.claimType,
      authority: input.authority,
      complaintNo: input.complaintNo,
      receivedOn: input.receivedOn,
      procedure: input.procedure,
      procedureDate: input.procedureDate,
      patientCode: input.patientCode,
      facts: input.facts,
      status: CaseStatus.draft,
      createdAt: now,
      updatedAt: now,
      documents: input.documents,
    );
    _cases.add(created);
    return created;
  }

  @override
  Future<void> startAnalysis(String caseId) async {
    await fakeLatency(300);
    _replace(_find(caseId).copyWith(status: CaseStatus.analyzing, updatedAt: _clock()));
    _progress[caseId] = PipelineProgress(steps: {for (final s in PipelineStep.values) s: PipelineStepState.pending});
    unawaited(_run(caseId));
  }

  Future<void> _run(String caseId) async {
    for (final step in PipelineStep.values) {
      _emit(caseId, _progress[caseId]!.copyWith(steps: {..._progress[caseId]!.steps, step: PipelineStepState.running}));
      await Future<void>.delayed(stepDuration);
      _emit(caseId, _progress[caseId]!.copyWith(steps: {..._progress[caseId]!.steps, step: PipelineStepState.done}));
    }
    _replace(_find(caseId).copyWith(status: CaseStatus.aiReady, risk: RiskLevel.medium, positionStrength: 68, updatedAt: _clock()));
    _emit(caseId, _progress[caseId]!.copyWith(finished: true));
  }

  void _emit(String caseId, PipelineProgress progress) {
    _progress[caseId] = progress;
    _pipelines[caseId]?.add(progress);
  }

  @override
  Stream<PipelineProgress> watchPipeline(String caseId) {
    final controller = _pipelines.putIfAbsent(caseId, StreamController<PipelineProgress>.broadcast);
    final current = _progress[caseId] ?? PipelineProgress(steps: {for (final s in PipelineStep.values) s: PipelineStepState.done}, finished: true);
    return Stream<PipelineProgress>.value(current).asyncExpand((first) async* {
      yield first;
      if (!first.finished) yield* controller.stream;
    });
  }

  @override
  Future<CaseAnalysis> getAnalysis(String caseId) async {
    await fakeLatency();
    return analysisFor(_find(caseId));
  }

  @override
  Future<CaseDraft> getDraft(String caseId) async {
    await fakeLatency();
    final profile = _currentProfile();
    return draftFor(_find(caseId), dentistName: profile?.name ?? 'الممارس الصحي', licenseNo: profile?.licenseNo);
  }

  @override
  Future<void> submitForReview(String caseId) async {
    await fakeLatency(600);
    final now = _clock();
    _replace(_find(caseId).copyWith(status: CaseStatus.lawyerReview, submittedAt: now, updatedAt: now));
  }

  @override
  Future<RegulationSource> getSource(Citation citation) async {
    await fakeLatency(150);
    return sourceByRef(citation.ref);
  }

  @override
  Future<void> rate({required RatingTarget target, required String targetId, required bool useful, String? comment}) => fakeLatency(250);

  /// Fake mode has no stored files: a text page stands in for the original.
  @override
  Future<DocumentFile> openDocument(String documentId) async {
    await fakeLatency(400);
    final text = 'معاينة تجريبية للمستند\n\nفي الوضع الحقيقي يُعرض الملف الأصلي (PDF أو صورة) من الخادم عبر رابط مؤقت، في الذاكرة فقط، مع منع لقطات الشاشة.';
    return DocumentFile(bytes: Uint8List.fromList(utf8.encode(text)), mime: 'text/plain', name: 'معاينة');
  }
}
