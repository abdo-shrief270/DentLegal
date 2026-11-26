import 'dart:async';

import 'package:sentry_flutter/sentry_flutter.dart';

/// Error tracking (PROJECT1_PLAN.md §8.6, D14): crashes and uncaught errors go to Sentry or GlitchTip, scrubbed.
///
/// Off unless the build passes `--dart-define=ERROR_TRACKING_DSN=…`. What leaves the phone: the error type, a masked
/// and shortened message, the stack, the app version and the device model. Never screenshots, the view tree,
/// breadcrumbs (they could hold screen text), the user, or request data.
const errorTrackingDsn = String.fromEnvironment('ERROR_TRACKING_DSN');
const _environment = String.fromEnvironment('APP_ENV', defaultValue: 'production');

const _maxMessage = 300;
final _patterns = <RegExp, String>{
  RegExp(r'[\w.%+-]+@[\w.-]+\.[A-Za-z]{2,}'): '[EMAIL]',
  RegExp(r'SA\d{2}\s?(?:\d{4}\s?){4}\d{4}', caseSensitive: false): '[IBAN]',
  // Saudi IDs and phone numbers, in Western or Arabic-Indic digits, with or without separators.
  RegExp(r'(?:\+|00)?[0-9٠-٩][0-9٠-٩\s\-]{7,14}[0-9٠-٩]'): '[NUMBER]',
  // The app's own messages are English: Arabic in one is quoted content (a question, a document line).
  RegExp(r'[؀-ۿ]+(?:[\s،.]+[؀-ۿ]+)*'): '[TEXT]',
};

String maskErrorText(String text) {
  var out = text;
  _patterns.forEach((pattern, token) => out = out.replaceAll(pattern, token));
  return out.length <= _maxMessage ? out : '${out.substring(0, _maxMessage)}…';
}

/// `beforeSend`: keeps metadata only.
SentryEvent scrubEvent(SentryEvent event, Hint hint) {
  event.user = null;
  event.request = null;
  event.breadcrumbs = null;
  for (final e in event.exceptions ?? const <SentryException>[]) {
    final value = e.value;
    if (value != null) e.value = maskErrorText(value);
  }
  final message = event.message;
  if (message != null) message.formatted = maskErrorText(message.formatted);
  return event;
}

/// Starts the app, inside Sentry when a DSN was given at build time.
Future<void> runWithErrorTracking(FutureOr<void> Function() appRunner) async {
  if (errorTrackingDsn.isEmpty) {
    await appRunner();
    return;
  }
  await SentryFlutter.init((o) {
    o.dsn = errorTrackingDsn;
    o.environment = _environment;
    o.sendDefaultPii = false;
    o.attachScreenshot = false;
    o.attachViewHierarchy = false;
    o.maxBreadcrumbs = 0;
    o.enableAutoNativeBreadcrumbs = false;
    o.tracesSampleRate = 0;
    o.beforeSend = scrubEvent;
  }, appRunner: appRunner);
}
