// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'auth_controller.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(authRepository)
final authRepositoryProvider = AuthRepositoryProvider._();

final class AuthRepositoryProvider extends $FunctionalProvider<AuthRepository, AuthRepository, AuthRepository> with $Provider<AuthRepository> {
  AuthRepositoryProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'authRepositoryProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$authRepositoryHash();

  @$internal
  @override
  $ProviderElement<AuthRepository> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  AuthRepository create(Ref ref) {
    return authRepository(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(AuthRepository value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<AuthRepository>(value));
  }
}

String _$authRepositoryHash() => r'1ab899401398f79d4621b9e6cafdb536132532c7';

/// The signed-in dentist, or null. The token lives in secure storage (Keychain/Keystore), never in state.

@ProviderFor(AuthController)
final authControllerProvider = AuthControllerProvider._();

/// The signed-in dentist, or null. The token lives in secure storage (Keychain/Keystore), never in state.
final class AuthControllerProvider extends $NotifierProvider<AuthController, Session?> {
  /// The signed-in dentist, or null. The token lives in secure storage (Keychain/Keystore), never in state.
  AuthControllerProvider._()
    : super(
        from: null,
        argument: null,
        retry: null,
        name: r'authControllerProvider',
        isAutoDispose: false,
        dependencies: null,
        $allTransitiveDependencies: null,
      );

  @override
  String debugGetCreateSourceHash() => _$authControllerHash();

  @$internal
  @override
  AuthController create() => AuthController();

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(Session? value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<Session?>(value));
  }
}

String _$authControllerHash() => r'936dcacabcb368623a38baf526045e7338646bdf';

/// The signed-in dentist, or null. The token lives in secure storage (Keychain/Keystore), never in state.

abstract class _$AuthController extends $Notifier<Session?> {
  Session? build();
  @$mustCallSuper
  @override
  WhenComplete runBuild() {
    final ref = this.ref as $Ref<Session?, Session?>;
    final element = ref.element as $ClassProviderElement<AnyNotifier<Session?, Session?>, Session?, Object?, Object?>;
    return element.handleCreate(ref, build);
  }
}
