import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:material_ui/material_ui.dart';

import 'app.dart';
import 'features/auth/presentation/auth_controller.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final container = ProviderContainer();
  // Restore a signed-in session before the first frame, so the router starts on the right screen.
  await container.read(authControllerProvider.notifier).restore();
  runApp(UncontrolledProviderScope(container: container, child: const DentLegalApp()));
}
