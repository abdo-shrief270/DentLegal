import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_ui/material_ui.dart';

import 'package:flutter/foundation.dart';
import 'package:screen_protector/screen_protector.dart';

import 'app.dart';
import 'core/monitoring/error_tracking.dart';
import 'core/security/app_lock.dart';
import 'features/auth/presentation/auth_controller.dart';

Future<void> main() => runWithErrorTracking(_start);

Future<void> _start() async {
  WidgetsFlutterBinding.ensureInitialized();
  final container = ProviderContainer();
  // Restore a signed-in session before the first frame, so the router starts on the right screen.
  await container.read(authControllerProvider.notifier).restore();
  // Cold start of a signed-in app: locked until Face ID / fingerprint (S5.4).
  await container.read(appLockProvider.notifier).init();
  // iOS: blur the app-switcher snapshot (Android hides guarded screens there via FLAG_SECURE, S9.1).
  if (!kIsWeb && defaultTargetPlatform == TargetPlatform.iOS) await ScreenProtector.protectDataLeakageWithBlur();
  runApp(UncontrolledProviderScope(container: container, child: const DentLegalApp()));
}
