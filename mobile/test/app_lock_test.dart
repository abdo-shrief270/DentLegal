import 'package:dentlegal/core/security/app_lock.dart';
import 'package:dentlegal/core/security/biometrics.dart';
import 'package:dentlegal/features/auth/domain/auth_models.dart';
import 'package:dentlegal/features/auth/presentation/auth_controller.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

class _Bio implements Biometrics {
  bool ok = true;
  int prompts = 0;

  @override
  Future<bool> available() async => true;

  @override
  Future<bool> authenticate(String reason) async {
    prompts++;
    return ok;
  }
}

class _SignedIn extends AuthController {
  @override
  Session? build() => const Session(
    phone: '551234417',
    profile: DentistProfile(name: 'د. تجريبي', specialty: 'عام', city: 'جدة', facility: 'عيادة'),
  );

  @override
  Future<void> logout() async => state = null;
}

/// Biometric lock rules (PROJECT1_PLAN.md §6.6, S5.4).
void main() {
  late _Bio bio;
  late ProviderContainer c;
  var clock = DateTime(2026, 11, 23, 10);

  ProviderContainer make({bool enabled = true, bool signedIn = true}) {
    bio = _Bio();
    final container = ProviderContainer(
      overrides: [
        biometricsProvider.overrideWithValue(bio),
        lockSettingsProvider.overrideWithValue(MemoryLockSettings(enabled)),
        if (signedIn) authControllerProvider.overrideWith(_SignedIn.new),
      ],
    );
    container.read(appLockProvider.notifier).now = () => clock;
    addTearDown(container.dispose);
    return container;
  }

  test('a signed-in cold start is locked until the biometric succeeds', () async {
    c = make();
    await c.read(appLockProvider.notifier).init();
    expect(c.read(appLockProvider).locked, isTrue);

    bio.ok = false;
    expect(await c.read(appLockProvider.notifier).unlock(), isFalse);
    expect(c.read(appLockProvider).locked, isTrue);

    bio.ok = true;
    expect(await c.read(appLockProvider.notifier).unlock(), isTrue);
    expect(c.read(appLockProvider).locked, isFalse);
  });

  test('it locks again only after 5 minutes in the background', () async {
    c = make();
    final lock = c.read(appLockProvider.notifier);
    await lock.init();
    await lock.unlock();

    lock.backgrounded();
    clock = clock.add(const Duration(minutes: 4));
    lock.resumed();
    expect(c.read(appLockProvider).locked, isFalse);

    lock.backgrounded();
    clock = clock.add(const Duration(minutes: 5));
    lock.resumed();
    expect(c.read(appLockProvider).locked, isTrue);
  });

  test('no lock when switched off or signed out; the OTP fallback signs out', () async {
    c = make(enabled: false);
    await c.read(appLockProvider.notifier).init();
    expect(c.read(appLockProvider).locked, isFalse);

    c = make(signedIn: false);
    await c.read(appLockProvider.notifier).init();
    expect(c.read(appLockProvider).locked, isFalse);

    c = make();
    await c.read(appLockProvider.notifier).init();
    await c.read(appLockProvider.notifier).useOtpInstead();
    expect(c.read(appLockProvider).locked, isFalse);
    expect(c.read(authControllerProvider), isNull);
  });

  test('turning the lock on needs a successful biometric', () async {
    c = make(enabled: false);
    final lock = c.read(appLockProvider.notifier);
    await lock.init();

    bio.ok = false;
    expect(await lock.setEnabled(true), isFalse);
    expect(c.read(appLockProvider).enabled, isFalse);

    bio.ok = true;
    expect(await lock.setEnabled(true), isTrue);
    expect(c.read(appLockProvider).enabled, isTrue);
    expect(bio.prompts, 2);
  });
}
