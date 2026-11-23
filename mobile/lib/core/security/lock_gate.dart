import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:lucide_icons_flutter/lucide_icons.dart';
import 'package:material_ui/material_ui.dart';

import '../theme/app_colors.dart';
import 'app_lock.dart';

/// Sits above the router: tracks background time and covers the app with the lock screen when locked, keeping the
/// navigation state underneath.
class LockGate extends ConsumerStatefulWidget {
  const LockGate({required this.child, super.key});

  final Widget child;

  @override
  ConsumerState<LockGate> createState() => _LockGateState();
}

class _LockGateState extends ConsumerState<LockGate> {
  late final AppLifecycleListener _lifecycle;

  @override
  void initState() {
    super.initState();
    final lock = ref.read(appLockProvider.notifier);
    _lifecycle = AppLifecycleListener(onHide: lock.backgrounded, onShow: lock.resumed);
  }

  @override
  void dispose() {
    _lifecycle.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final locked = ref.watch(appLockProvider.select((s) => s.locked));
    return Stack(
      children: [
        widget.child,
        if (locked) const Positioned.fill(child: _LockScreen()),
      ],
    );
  }
}

class _LockScreen extends ConsumerStatefulWidget {
  const _LockScreen();

  @override
  ConsumerState<_LockScreen> createState() => _LockScreenState();
}

class _LockScreenState extends ConsumerState<_LockScreen> {
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _unlock());
  }

  Future<void> _unlock() async {
    if (_busy) return;
    setState(() => _busy = true);
    await ref.read(appLockProvider.notifier).unlock();
    if (mounted) setState(() => _busy = false);
  }

  @override
  Widget build(BuildContext context) => Material(
    color: AppColors.bg,
    child: SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(LucideIcons.lock, size: 44, color: AppColors.teal700),
            const SizedBox(height: 14),
            Text('DentLegal مقفل', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 6),
            const Text(
              'استخدم البصمة أو الوجه للمتابعة.',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.muted),
            ),
            const SizedBox(height: 24),
            FilledButton.icon(onPressed: _busy ? null : _unlock, icon: const Icon(LucideIcons.fingerprint), label: const Text('فتح')),
            const SizedBox(height: 8),
            TextButton(onPressed: () => ref.read(appLockProvider.notifier).useOtpInstead(), child: const Text('تسجيل الدخول برمز التحقق')),
          ],
        ),
      ),
    ),
  );
}
