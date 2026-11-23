import 'package:riverpod_annotation/riverpod_annotation.dart';

import 'api_client.dart';
import 'token_store.dart';

part 'api_providers.g.dart';

@Riverpod(keepAlive: true)
TokenStore tokenStore(Ref ref) => SecureTokenStore();

/// The shared API client. A 401 (token expired or revoked by an admin) signs the app out: see AuthController.
@Riverpod(keepAlive: true)
ApiClient apiClient(Ref ref) => ApiClient(tokens: ref.watch(tokenStoreProvider));
