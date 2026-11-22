import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../domain/case_models.dart';
import 'cases_providers.dart';

part 'case_actions.g.dart';

/// Commands on a case. Kept out of widgets so screens only render state and call these.
@riverpod
class CaseActions extends _$CaseActions {
  @override
  void build() {}

  Future<void> submitForReview(String caseId) async {
    await ref.read(casesRepositoryProvider).submitForReview(caseId);
    invalidateCases(ref, caseId);
  }

  Future<void> analysisFinished(String caseId) async => invalidateCases(ref, caseId);

  Future<void> rate(RatingTarget target, String targetId, {required bool useful, String? comment}) =>
      ref.read(casesRepositoryProvider).rate(target: target, targetId: targetId, useful: useful, comment: comment);
}
