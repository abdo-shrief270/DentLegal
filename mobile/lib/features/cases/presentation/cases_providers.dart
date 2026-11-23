import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../auth/presentation/auth_controller.dart';
import '../../../core/api/api_config.dart';
import '../../../core/api/api_providers.dart';
import '../data/fake_cases_repository.dart';
import '../data/http_cases_repository.dart';
import '../domain/case_models.dart';
import '../domain/cases_repository.dart';

part 'cases_providers.g.dart';

@Riverpod(keepAlive: true)
CasesRepository casesRepository(Ref ref) => ApiConfig.useRealApi
    ? HttpCasesRepository(ref.watch(apiClientProvider))
    : FakeCasesRepository(currentProfile: () => ref.read(authControllerProvider)?.profile);

@riverpod
Future<List<DentalCase>> casesList(Ref ref) => ref.watch(casesRepositoryProvider).listCases();

@riverpod
Future<DentalCase> caseDetail(Ref ref, String id) => ref.watch(casesRepositoryProvider).getCase(id);

@riverpod
Future<CaseAnalysis> caseAnalysis(Ref ref, String id) => ref.watch(casesRepositoryProvider).getAnalysis(id);

@riverpod
Future<CaseDraft> caseDraft(Ref ref, String id) => ref.watch(casesRepositoryProvider).getDraft(id);

@riverpod
Stream<PipelineProgress> casePipeline(Ref ref, String id) => ref.watch(casesRepositoryProvider).watchPipeline(id);

@riverpod
Future<RegulationSource> regulationSource(Ref ref, Citation citation) => ref.watch(casesRepositoryProvider).getSource(citation);

/// Refresh every case view after a change (create, analysis finished, submit).
void invalidateCases(Ref ref, String caseId) {
  ref.invalidate(casesListProvider);
  ref.invalidate(caseDetailProvider(caseId));
}
