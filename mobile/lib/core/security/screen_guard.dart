import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:material_ui/material_ui.dart';
import 'package:screen_protector/screen_protector.dart';

/// No screenshots or screen recording while this screen is shown (PROJECT1_PLAN.md §6.6, S9.1): Android FLAG_SECURE
/// (which also hides it in the app switcher), iOS screenshot protection. Nested guards share one switch.
class ScreenGuard extends StatefulWidget {
  const ScreenGuard({required this.child, super.key});

  final Widget child;

  static int _active = 0;

  @override
  State<ScreenGuard> createState() => _ScreenGuardState();
}

class _ScreenGuardState extends State<ScreenGuard> {
  @override
  void initState() {
    super.initState();
    if (!kIsWeb && ScreenGuard._active++ == 0) ScreenProtector.preventScreenshotOn().ignore();
  }

  @override
  void dispose() {
    if (!kIsWeb && --ScreenGuard._active == 0) ScreenProtector.preventScreenshotOff().ignore();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
