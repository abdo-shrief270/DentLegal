// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'api_providers.dart';

// **************************************************************************
// RiverpodGenerator
// **************************************************************************

// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, type=warning

@ProviderFor(tokenStore)
final tokenStoreProvider = TokenStoreProvider._();

final class TokenStoreProvider extends $FunctionalProvider<TokenStore, TokenStore, TokenStore> with $Provider<TokenStore> {
  TokenStoreProvider._()
    : super(from: null, argument: null, retry: null, name: r'tokenStoreProvider', isAutoDispose: false, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$tokenStoreHash();

  @$internal
  @override
  $ProviderElement<TokenStore> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  TokenStore create(Ref ref) {
    return tokenStore(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(TokenStore value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<TokenStore>(value));
  }
}

String _$tokenStoreHash() => r'e1af499041ec8e3fd653ab4576cba8185f657d98';

/// The shared API client. A 401 (token expired or revoked by an admin) signs the app out: see AuthController.

@ProviderFor(apiClient)
final apiClientProvider = ApiClientProvider._();

/// The shared API client. A 401 (token expired or revoked by an admin) signs the app out: see AuthController.

final class ApiClientProvider extends $FunctionalProvider<ApiClient, ApiClient, ApiClient> with $Provider<ApiClient> {
  /// The shared API client. A 401 (token expired or revoked by an admin) signs the app out: see AuthController.
  ApiClientProvider._()
    : super(from: null, argument: null, retry: null, name: r'apiClientProvider', isAutoDispose: false, dependencies: null, $allTransitiveDependencies: null);

  @override
  String debugGetCreateSourceHash() => _$apiClientHash();

  @$internal
  @override
  $ProviderElement<ApiClient> $createElement($ProviderPointer pointer) => $ProviderElement(pointer);

  @override
  ApiClient create(Ref ref) {
    return apiClient(ref);
  }

  /// {@macro riverpod.override_with_value}
  Override overrideWithValue(ApiClient value) {
    return $ProviderOverride(origin: this, providerOverride: $SyncValueProvider<ApiClient>(value));
  }
}

String _$apiClientHash() => r'1fa79b35a83ea3a0593b1cbb42be718724bd94a3';
