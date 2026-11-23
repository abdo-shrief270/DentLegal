import 'package:dentlegal/core/api/api_client.dart';
import 'package:dentlegal/core/api/api_error.dart';
import 'package:dentlegal/core/api/token_store.dart';
import 'package:dentlegal/features/auth/data/http_auth_repository.dart';
import 'package:dentlegal/features/auth/domain/auth_models.dart';
import 'package:dentlegal/features/auth/domain/auth_repository.dart';
import 'package:dentlegal/features/cases/data/http_cases_repository.dart';
import 'package:dentlegal/features/cases/domain/case_models.dart';
import 'package:dentlegal/features/consult/data/http_consult_repository.dart';
import 'package:dentlegal/features/consult/domain/consult_models.dart';
import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fake_adapter.dart';

/// The HTTP layer against recorded API shapes (backend/app/Modules/*/Http). Synthetic data only.
void main() {
  late MemoryTokenStore tokens;
  late FakeAdapter adapter;

  ApiClient client(Map<String, Object Function(RequestOptions r)> routes, {void Function()? onUnauthorized}) {
    adapter = FakeAdapter(routes);
    final api = ApiClient(tokens: tokens, baseUrl: 'http://api.test', onUnauthorized: onUnauthorized);
    api.dio.httpClientAdapter = adapter;
    return api;
  }

  final me = {
    'id': 'u1',
    'name': 'د. رافع الحميد',
    'phone': '+966551234417',
    'role': 'dentist',
    'needs_profile': false,
    'profile': {'specialty': 'زراعة الأسنان', 'city': 'جدة', 'facility_name': 'عيادة تجريبية', 'scfhs_license_no': '00-TEST-1'},
    'consents': {
      'terms': {'granted': true, 'current_version': '2026-11'},
      'privacy': {'granted': true, 'current_version': '2026-11'},
      'ai_processing': {'granted': false, 'current_version': '2026-11'},
      'lawyer_access': {'granted': false, 'current_version': '2026-12'},
    },
  };

  setUp(() => tokens = MemoryTokenStore());

  group('ApiClient', () {
    test('sends the app version and the bearer token, and unwraps data', () async {
      await tokens.write('tok-1');
      final api = client({
        'GET /me': (_) => {'data': me},
      });

      final data = await api.get<Json>('/me');

      expect(data['name'], 'د. رافع الحميد');
      expect(adapter.sent.single.headers['Authorization'], 'Bearer tok-1');
      expect(adapter.sent.single.headers['X-App-Version'], isNotEmpty);
    });

    test('maps the error envelope and signs out on 401', () async {
      await tokens.write('expired');
      var signedOut = false;
      final api = client({
        'GET /cases': (_) => FakeAdapter.status(429, {
          'error': {
            'code': 'rate_limited',
            'message': 'محاولات كثيرة.',
            'details': {'retry_after_s': 30},
          },
        }),
        'GET /me': (_) => FakeAdapter.status(401, {
          'error': {'code': 'unauthenticated', 'message': 'انتهت الجلسة.'},
        }),
      }, onUnauthorized: () => signedOut = true);

      await expectLater(api.get<Json>('/cases'), throwsA(isA<ApiError>().having((e) => e.retryAfterSeconds, 'retry', 30)));
      await expectLater(api.get<Json>('/me'), throwsA(isA<ApiError>().having((e) => e.code, 'code', 'unauthenticated')));
      expect(signedOut, isTrue);
      expect(await tokens.read(), isNull);
    });
  });

  group('auth', () {
    test('verify stores the token and maps the session; errors become AuthException', () async {
      final repo = HttpAuthRepository(
        client({
          'POST /auth/otp/verify': (_) => {
            'data': {'token': 'tok-new', 'needs_profile': false, 'user': me},
          },
          'POST /auth/otp/request': (_) => FakeAdapter.status(403, {
            'error': {'code': 'invitation_required', 'message': 'هذا الرقم غير مدعو.'},
          }),
        }),
        tokens,
        platform: 'android',
      );

      final session = await repo.verifyOtp('551234417', '123456');

      expect(await tokens.read(), 'tok-new');
      expect(session.profile?.facility, 'عيادة تجريبية');
      expect(adapter.bodies('POST', '/auth/otp/verify').single['device'], {'platform': 'android', 'app_version': isNotEmpty});
      await expectLater(repo.requestOtp('500000000'), throwsA(isA<AuthException>().having((e) => e.code, 'code', 'invitation_required')));
    });

    test('profile completion records the current consent versions', () async {
      await tokens.write('tok');
      final repo = HttpAuthRepository(
        client({
          'PUT /me/profile': (_) => {'data': me},
          'GET /me': (_) => {'data': me},
          'POST /me/consents': (_) => {'data': {}},
        }),
        tokens,
        platform: 'ios',
      );

      await repo.completeProfile(
        const DentistProfile(name: 'د. رافع الحميد', specialty: 'زراعة الأسنان', city: 'جدة', facility: 'عيادة تجريبية'),
        acceptedTerms: true,
        acceptedAiProcessing: true,
      );

      expect(adapter.bodies('POST', '/me/consents').single['consents'], [
        {'type': 'terms', 'version': '2026-11'},
        {'type': 'privacy', 'version': '2026-11'},
        {'type': 'ai_processing', 'version': '2026-11'},
      ]);
      expect(adapter.bodies('PUT', '/me/profile').single['scfhs_license_no'], isNull);
    });

    test('restore: no token → null; a revoked token is dropped', () async {
      final repo = HttpAuthRepository(
        client({
          'GET /me': (_) => FakeAdapter.status(401, {
            'error': {'code': 'unauthenticated', 'message': 'x'},
          }),
        }),
        tokens,
        platform: 'ios',
      );
      expect(await repo.restore(), isNull);
      expect(adapter.sent, isEmpty);

      await tokens.write('revoked');
      expect(await repo.restore(), isNull);
      expect(await tokens.read(), isNull);
    });
  });

  group('cases', () {
    final caseJson = {
      'id': 'c1',
      'code': 'DL-2026-0001',
      'title': 'شكوى تنميل بعد زراعة',
      'case_type': 'official_notice',
      'claim_type': 'compensation',
      'authority': 'الشؤون الصحية بجدة',
      'status': 'ai_ready',
      'received_on': '2026-11-01',
      'created_at': '2026-11-02T08:00:00Z',
      'updated_at': '2026-11-03T08:00:00Z',
      'risk': 'medium',
      'position_strength': 72,
      'lawyer_name': null,
      'documents': [
        {'id': 'd1', 'kind': 'complaint_letter', 'name': 'letter.pdf', 'mime': 'application/pdf', 'size_bytes': 2048000, 'page_count': 2, 'low_quality': false},
      ],
    };

    test('maps cases with snake_case enums', () async {
      final repo = HttpCasesRepository(
        client({
          'GET /cases/c1': (_) => {'data': caseJson},
        }),
      );

      final c = await repo.getCase('c1');

      expect(c.caseType, CaseType.officialNotice);
      expect(c.status, CaseStatus.aiReady);
      expect(c.risk, RiskLevel.medium);
      expect(c.documents.single.ref, 'D1');
      expect(c.documents.single.kind, DocumentKind.complaintLetter);
      expect(c.documents.single.sizeLabel, '2.0 م.ب');
    });

    test('pipeline polling stops when the run succeeds', () async {
      var calls = 0;
      final repo = HttpCasesRepository(
        client({
          'GET /cases/c1/analysis/status': (_) {
            calls++;
            return {
              'data': {
                'status': calls < 2 ? 'running' : 'succeeded',
                'steps': [
                  {'key': 'ocr', 'status': 'done'},
                  {'key': 'masking', 'status': calls < 2 ? 'running' : 'done'},
                ],
              },
            };
          },
        }),
        pollEvery: Duration.zero,
      );

      final progress = await repo.watchPipeline('c1').toList();

      expect(progress, hasLength(2));
      expect(progress.first.steps[PipelineStep.masking], PipelineStepState.running);
      expect(progress.last.finished, isTrue);
    });

    test('analysis and draft carry the ids used for ratings and the chunk ids behind citations', () async {
      final repo = HttpCasesRepository(
        client({
          'GET /cases/c1/analysis': (_) => {
            'data': {
              'id': 'a1',
              'position_strength': 72,
              'risk_level': 'medium',
              'summary': 'موقف قابل للدفاع.',
              'sources_sufficient': true,
              'strengths': [
                {
                  'text': 'إقرار موقّع',
                  'citations': [
                    {'ref': 'S1', 'kind': 'knowledge', 'chunk_id': 412, 'label': 'المادة 19', 'verified': true},
                  ],
                },
              ],
              'weaknesses': [
                {'text': 'لا توجد تعليمات مكتوبة', 'severity': 'high', 'citations': []},
              ],
              'recommended_actions': [
                {'text': 'إبلاغ شركة التأمين', 'urgency': 'before_hearing', 'citations': []},
              ],
              'sources': [
                {'ref': 'S1', 'chunk_id': 412, 'law': 'نظام مزاولة المهن الصحية', 'label': 'المادة 19', 'title': 'الموافقة', 'text': 'نص', 'score': 0.81},
              ],
            },
          },
          'GET /cases/c1/draft': (_) => {
            'data': {
              'draft_id': 'dr1',
              'status': 'approved',
              'version': {
                'id': 'v3',
                'no': 3,
                'author': 'lawyer',
                'title': 'إفادة الممارس الصحي',
                'addressee': 'سعادة المدير',
                'salutation': 'السلام عليكم',
                'intro': {'text': 'أتقدم أنا د. رافع', 'legal_claim': false, 'citations': []},
                'sections': [
                  {
                    'kind': 'defenses',
                    'heading': 'ثانيًا: الدفوع',
                    'paragraphs': [
                      {
                        'text': 'دفع',
                        'legal_claim': true,
                        'citations': [
                          {'ref': 'S1', 'chunk_id': 412, 'verified': true},
                        ],
                      },
                    ],
                  },
                ],
                'closing': 'وتقبلوا التحية',
                'attachments': ['صورة الإقرار'],
              },
              'approval': {'approved_at': '2026-11-20T10:00:00Z', 'approved_by': 'أ. سارة'},
            },
          },
        }),
      );

      final a = await repo.getAnalysis('c1');
      final d = await repo.getDraft('c1');

      expect(a.id, 'a1');
      expect(a.strengths.single.citations.single.chunkId, 412);
      expect(a.weaknesses.single.severity, RiskLevel.high);
      expect(a.actions.single.urgency, Urgency.beforeHearing);
      expect(a.sources.single.score, 0.81);
      expect(d.versionId, 'v3');
      expect(d.byLawyer, isTrue);
      expect(d.approvedBy, 'أ. سارة');
      expect(d.sections.single.paragraphs.single.unsupported, isFalse);
    });

    test('sending for review records the lawyer-access consent first; ratings use the API target names', () async {
      final repo = HttpCasesRepository(
        client({
          'GET /me': (_) => {'data': me},
          'POST /me/consents': (_) => {'data': {}},
          'POST /cases/c1/submit-review': (_) => {
            'data': {'status': 'lawyer_review'},
          },
          'POST /ratings': (_) => {'data': {}},
        }),
      );

      await repo.submitForReview('c1');
      await repo.rate(target: RatingTarget.draft, targetId: 'v3', useful: true, comment: '  ');

      expect(adapter.bodies('POST', '/me/consents').single['consents'], [
        {'type': 'lawyer_access', 'version': '2026-12'},
      ]);
      expect(adapter.sent.map((r) => r.uri.path).toList(), ['/api/v1/me', '/api/v1/me/consents', '/api/v1/cases/c1/submit-review', '/api/v1/ratings']);
      expect(adapter.bodies('POST', '/ratings').single, {'target_type': 'draft_version', 'target_id': 'v3', 'useful': true});
    });
  });

  group('consultant', () {
    Map<String, Object?> answer(String status) => {
      'data': {
        'id': 'm2',
        'role': 'assistant',
        'consultation_id': 't1',
        'status': status,
        'error': status == 'failed' ? 'ai_unavailable' : null,
        'answer': status == 'pending' || status == 'failed'
            ? null
            : {
                'paragraphs': [
                  {
                    'text': 'نعم، بالقدر اللازم.',
                    'citations': [
                      {'ref': 'S1', 'kind': 'knowledge', 'chunk_id': 7, 'label': 'المادة 21', 'verified': true},
                    ],
                  },
                  {
                    'text': 'ويُنصح باستخدام رمز المريض.',
                    'citations': [
                      {'ref': 'S1', 'kind': 'knowledge', 'chunk_id': 7, 'label': 'المادة 21', 'verified': true},
                    ],
                  },
                ],
              },
      },
    };

    test('asks, polls until answered, then follows up in the same thread', () async {
      var polls = 0;
      final repo = HttpConsultRepository(
        client({
          'POST /consultations': (_) => answer('pending'),
          'POST /consultations/t1/messages': (_) => answer('pending'),
          'GET /consultation-messages/m2': (_) => answer(++polls < 2 ? 'pending' : 'answered'),
        }),
        pollEvery: Duration.zero,
      );

      // The chat passes its messages including the question just typed.
      const q1 = ConsultMessage(id: 'q0', fromDentist: true, text: 'هل يحق لي الإفصاح؟');
      final m = await repo.ask(q1.text, history: const [q1]);
      await repo.ask(
        'وما الحد المسموح؟',
        history: [
          q1,
          m,
          const ConsultMessage(id: 'q1', fromDentist: true, text: 'وما الحد المسموح؟'),
        ],
      );

      expect(m.text, 'نعم، بالقدر اللازم.\n\nويُنصح باستخدام رمز المريض.');
      expect(m.citations.single.chunkId, 7, reason: 'each source once');
      expect(adapter.bodies('POST', '/consultations/t1/messages'), hasLength(1));
    });

    test('a failed answer surfaces as an ApiError', () async {
      final repo = HttpConsultRepository(
        client({'POST /consultations': (_) => answer('pending'), 'GET /consultation-messages/m2': (_) => answer('failed')}),
        pollEvery: Duration.zero,
      );

      await expectLater(repo.ask('سؤال', history: const []), throwsA(isA<ApiError>().having((e) => e.code, 'code', 'ai_unavailable')));
    });
  });
}
