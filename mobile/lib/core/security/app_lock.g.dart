// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_lock.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(biometrics)
final biometricsProvider = BiometricsProvider._();

final class BiometricsProvider extends $FunctionalProvider<Biometrics, Biometrics, Biometrics> with $Provider<Biometrics> {
  BiometricsProvider._()
    : super(from: null, argument: null, retry: null, name: r'biometricsProvider', isAutoDispose: false, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$biometricsHash();

  @$internal
  @override
  $ProviderElement<Biometrics> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  Biometrics create(Ref ref) {
    return biometrics(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(Biometrics value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<Biometrics>(value));
  }
}

String _$biometricsHash() => r'144a918aabfde981ceeb29e780f5f973963094b6';

@ProviderFor(lockSettings)
final lockSettingsProvider = LockSettingsProvider._();

final class LockSettingsProvider extends $FunctionalProvider<LockSettings, LockSettings, LockSettings> with $Provider<LockSettings> {
  LockSettingsProvider._()
    : super(from: null, argument: null, retry: null, name: r'lockSettingsProvider', isAutoDispose: false, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$lockSettingsHash();

  @$internal
  @override
  $ProviderElement<LockSettings> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  LockSettings create(Ref ref) {
    return lockSettings(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(LockSettings value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<LockSettings>(value));
  }
}

String _$lockSettingsHash() => r'931ce6b9e7919261096eb11023f4576e10f2e003';

/// Biometric lock (PROJECT1_PLAN.md §6.6, S5.4): on cold start and after 5 minutes in the background, for a signed-in
/// dentist with the switch on and biometrics enrolled. The OTP sign-in is the fallback.

@ProviderFor(AppLock)
final appLockProvider = AppLockProvider._();

/// Biometric lock (PROJECT1_PLAN.md §6.6, S5.4): on cold start and after 5 minutes in the background, for a signed-in
/// dentist with the switch on and biometrics enrolled. The OTP sign-in is the fallback.
final class AppLockProvider extends $NotifierProvider<AppLock, AppLockState> {
  /// Biometric lock (PROJECT1_PLAN.md §6.6, S5.4): on cold start and after 5 minutes in the background, for a signed-in
  /// dentist with the switch on and biometrics enrolled. The OTP sign-in is the fallback.
  AppLockProvider._()
    : super(from: null, argument: null, retry: null, name: r'appLockProvider', isAutoDispose: false, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$appLockHash();

  @$internal
  @override
  AppLock create() => AppLock();

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(AppLockState value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<AppLockState>(value));
  }
}

String _$appLockHash() => r'896c7a268a53c1aae34d944ee39a8cecfff3091c';

/// Biometric lock (PROJECT1_PLAN.md §6.6, S5.4): on cold start and after 5 minutes in the background, for a signed-in
/// dentist with the switch on and biometrics enrolled. The OTP sign-in is the fallback.

abstract class _$AppLock extends $Notifier<AppLockState> {
  AppLockState build();
  @$mustCallSuper
  @override
  WhenComplete runBuild() {
    final ref = this.ref as $Ref<AppLockState, AppLockState>;
    final element = ref.element as $ClassProviderElement<AnyNotifier<AppLockState, AppLockState>, AppLockState, Object?, Object?>;
    return element.handleCreate(ref, build);
  }
}
