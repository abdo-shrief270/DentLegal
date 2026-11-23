import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../features/auth/presentation/auth_controller.dart';
import '../api/api_config.dart';
import 'biometrics.dart';

part 'app_lock.g.dart';

@Riverpod(keepAlive: true)
Biometrics biometrics(Ref ref) => ApiConfig.useRealApi ? DeviceBiometrics() : const NoBiometrics();

@Riverpod(keepAlive: true)
LockSettings lockSettings(Ref ref) => ApiConfig.useRealApi ? const SecureLockSettings() : MemoryLockSettings();

class AppLockState {
  const AppLockState({this.available = false, this.enabled = true, this.locked = false});

  /// The device has Face ID / fingerprint enrolled.
  final bool available;

  /// The dentist's «الدخول بالبصمة» switch.
  final bool enabled;

  /// The lock screen is up.
  final bool locked;

  AppLockState copyWith({bool? available, bool? enabled, bool? locked}) =>
      AppLockState(available: available ?? this.available, enabled: enabled ?? this.enabled, locked: locked ?? this.locked);
}

/// Biometric lock (PROJECT1_PLAN.md §6.6, S5.4): on cold start and after 5 minutes in the background, for a signed-in
/// dentist with the switch on and biometrics enrolled. The OTP sign-in is the fallback.
@Riverpod(keepAlive: true)
class AppLock extends _$AppLock {
  static const backgroundLimit = Duration(minutes: 5);

  DateTime? _backgroundedAt;

  /// Injected for tests.
  DateTime Function() now = DateTime.now;

  @override
  AppLockState build() => const AppLockState();

  bool get _signedIn => ref.read(authControllerProvider) != null;

  /// At start, after the session is restored.
  Future<void> init() async {
    final available = await ref.read(biometricsProvider).available();
    final enabled = await ref.read(lockSettingsProvider).enabled();
    state = AppLockState(available: available, enabled: enabled, locked: available && enabled && _signedIn);
  }

  void backgrounded() => _backgroundedAt = now();

  void resumed() {
    final since = _backgroundedAt;
    _backgroundedAt = null;
    if (since != null && now().difference(since) >= backgroundLimit && state.available && state.enabled && _signedIn) {
      state = state.copyWith(locked: true);
    }
  }

  Future<bool> unlock() async {
    final ok = await ref.read(biometricsProvider).authenticate('افتح DentLegal');
    if (ok) state = state.copyWith(locked: false);
    return ok;
  }

  /// «تسجيل الدخول برمز التحقق»: sign out and use the OTP flow instead.
  Future<void> useOtpInstead() async {
    await ref.read(authControllerProvider.notifier).logout();
    state = state.copyWith(locked: false);
  }

  /// Turning the lock on asks for the biometric first, so nobody enables it on someone else's face.
  Future<bool> setEnabled(bool value) async {
    if (value && !await ref.read(biometricsProvider).authenticate('تفعيل الدخول بالبصمة')) return false;
    await ref.read(lockSettingsProvider).setEnabled(value);
    state = state.copyWith(enabled: value);
    return true;
  }
}
