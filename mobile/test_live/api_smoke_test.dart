// Live smoke test of the HTTP layer against a running Laravel API with the demo seeder (synthetic data only).
// Not part of `flutter test`; run it on purpose:
//   php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder && SMS_DRIVER=log php artisan serve --port=8123
//   flutter test test_live --dart-define=API_BASE_URL=http://127.0.0.1:8123 --dart-define=SMS_LOG=<backend>/storage/logs
import 'dart:io';

import 'package:dentlegal/core/api/api_client.dart';
import 'package:dentlegal/core/api/token_store.dart';
import 'package:dentlegal/features/auth/data/http_auth_repository.dart';
import 'package:dentlegal/features/cases/data/http_cases_repository.dart';
import 'package:dentlegal/features/cases/domain/case_models.dart';
import 'package:flutter_test/flutter_test.dart';

const baseUrl = String.fromEnvironment('API_BASE_URL');
const smsLog = String.fromEnvironment('SMS_LOG');

/// The newest 6-digit code the log SMS driver wrote for the phone.
String latestOtp() {
  final logs = Directory(smsLog).listSync().whereType<File>().where((f) => f.path.contains('sms')).toList()
    ..sort((a, b) => a.lastModifiedSync().compareTo(b.lastModifiedSync()));
  final matches = RegExp(r'\b(\d{6})\b').allMatches(logs.last.readAsStringSync()).toList();
  return matches.last.group(1)!;
}

void main() {
  test('sign in, read the profile, create a case with a document, list cases', () async {
    final tokens = MemoryTokenStore();
    final api = ApiClient(tokens: tokens, baseUrl: baseUrl);
    final auth = HttpAuthRepository(api, tokens, platform: 'android');
    final cases = HttpCasesRepository(api);

    await auth.requestOtp('0551234417');
    final session = await auth.verifyOtp('0551234417', latestOtp());
    expect(await tokens.read(), isNotNull);
    expect(session.profile, isNotNull);
    expect((await auth.restore())?.phone, session.phone);

    final pdf = File('${Directory.systemTemp.path}/dentlegal-smoke.pdf')..writeAsStringSync('%PDF-1.7\n% synthetic smoke-test letter\n%%EOF');
    final created = await cases.createCase(
      NewCaseInput(
        caseType: CaseType.complaint,
        authority: 'الشؤون الصحية بجدة',
        complaintNo: 'SMOKE-1',
        receivedOn: DateTime.now().subtract(const Duration(days: 3)),
        title: 'اختبار تكامل التطبيق',
        procedure: 'خلع ضرس',
        procedureDate: DateTime.now().subtract(const Duration(days: 20)),
        patientCode: 'P-SMOKE',
        claimType: ClaimType.unknown,
        facts: 'وقائع تجريبية لاختبار التكامل بين التطبيق والخادم، جوال المريض 0551112233.',
        documents: [
          CaseDocument(id: 'local-1', ref: '', name: 'letter.pdf', kind: DocumentKind.complaintLetter, pages: 1, sizeLabel: '1 ك.ب', localPath: pdf.path),
        ],
      ),
    );
    expect(created.code, startsWith('DL-'));
    expect(created.documents.single.kind, DocumentKind.complaintLetter);
    expect(created.facts, isNot(contains('0551112233')), reason: 'facts are pattern-masked before storage');

    final list = await cases.listCases();
    expect(list.map((c) => c.id), contains(created.id));

    await auth.logout();
    expect(await tokens.read(), isNull);
  });
}
