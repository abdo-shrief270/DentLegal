import 'package:dentlegal/features/cases/data/fake_cases_repository.dart';
import 'package:dentlegal/features/cases/domain/case_models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('a new case runs the six pipeline steps and becomes ready', () async {
    final repo = FakeCasesRepository(currentProfile: () => null)..stepDuration = Duration.zero;
    final now = DateTime(2026, 11, 22);
    final created = await repo.createCase(
      NewCaseInput(
        caseType: CaseType.complaint,
        authority: 'جهة تجريبية',
        complaintNo: '1/1448',
        receivedOn: now,
        title: 'قضية تجريبية',
        procedure: 'حشو عصب',
        procedureDate: now,
        patientCode: 'P-0001',
        claimType: ClaimType.compensation,
        facts: 'وقائع تجريبية بالكامل لأغراض الاختبار فقط.',
        documents: const [],
      ),
    );
    expect(created.status, CaseStatus.draft);

    final progress = repo.watchPipeline(created.id);
    await repo.startAnalysis(created.id);
    final last = await progress.firstWhere((p) => p.finished);

    expect(last.fraction, 1);
    expect((await repo.getCase(created.id)).status, CaseStatus.aiReady);
  });
}
