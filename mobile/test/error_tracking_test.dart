import 'package:dentlegal/core/monitoring/error_tracking.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

void main() {
  test('error text loses identifiers and quoted Arabic content', () {
    final masked = maskErrorText('upload failed for 1098765432 / +966 55 123 4567 / ١٠٩٨٧٦٥٤٣٢ / a@b.com: يدّعي المريض استمرار التنميل');
    for (final leaked in ['1098765432', '123 4567', '١٠٩٨٧٦٥٤٣٢', 'a@b.com', 'التنميل']) {
      expect(masked, isNot(contains(leaked)));
    }
    expect(masked, contains('upload failed'));
    expect(maskErrorText('x' * 1000).length, lessThanOrEqualTo(301));
  });

  test('events keep metadata only', () {
    final event = SentryEvent(
      user: SentryUser(id: 'u1', ipAddress: '10.0.0.1'),
      request: SentryRequest(url: 'https://api.example.test/v1/cases?x=1', data: {'facts': 'سر'}),
      breadcrumbs: [Breadcrumb(message: 'نص الشاشة')],
      exceptions: [SentryException(type: 'StateError', value: 'bad state for 0551234567')],
    );

    final out = scrubEvent(event, Hint());

    expect(out.user, isNull);
    expect(out.request, isNull);
    expect(out.breadcrumbs, isNull);
    expect(out.exceptions!.single.type, 'StateError');
    expect(out.exceptions!.single.value, isNot(contains('0551234567')));
  });

  test('without a DSN the app runs without Sentry', () async {
    var ran = false;
    await runWithErrorTracking(() => ran = true);
    expect(ran, isTrue);
    expect(Sentry.isEnabled, isFalse);
  });
}
