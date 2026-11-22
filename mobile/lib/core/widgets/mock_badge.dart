import 'package:material_ui/material_ui.dart';

import 'tone_badge.dart';

/// Shown while the app runs on fake data, like the dashboard's «بيانات تجريبية» badge.
class MockBadge extends StatelessWidget {
  const MockBadge({super.key});

  static const enabled = bool.fromEnvironment('MOCK_DATA', defaultValue: true);

  @override
  Widget build(BuildContext context) => enabled ? const ToneBadge('بيانات تجريبية', tone: Tone.amber) : const SizedBox.shrink();
}
