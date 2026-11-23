import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../../../core/api/api_config.dart';
import '../../../core/api/api_providers.dart';
import '../data/fake_auth_repository.dart';
import '../data/http_auth_repository.dart';
import '../domain/auth_models.dart';
import '../domain/auth_repository.dart';

part 'auth_controller.g.dart';

@Riverpod(keepAlive: true)
AuthRepository authRepository(Ref ref) =>
    ApiConfig.useRealApi ? HttpAuthRepository(ref.watch(apiClientProvider), ref.watch(tokenStoreProvider)) : FakeAuthRepository();

/// The signed-in dentist, or null. The token lives in secure storage (Keychain/Keystore), never in state.
@Riverpod(keepAlive: true)
class AuthController extends _$AuthController {
  @override
  Session? build() {
    // A 401 anywhere (expired token, account disabled by an admin) returns the app to sign-in.
    ref.read(apiClientProvider).onUnauthorized = () {
      if (ref.mounted) state = null;
    };
    return null;
  }

  /// At app start, before the first frame: reuse a stored token if it still works.
  Future<void> restore() async {
    state = await _repo.restore();
  }

  AuthRepository get _repo => ref.read(authRepositoryProvider);

  Future<OtpRequest> requestOtp(String phone) => _repo.requestOtp(phone);

  Future<void> verifyOtp(String phone, String code) async {
    state = await _repo.verifyOtp(phone, code);
  }

  Future<void> completeProfile(DentistProfile profile, {required bool acceptedTerms, required bool acceptedAiProcessing}) async {
    state = await _repo.completeProfile(profile, acceptedTerms: acceptedTerms, acceptedAiProcessing: acceptedAiProcessing);
  }

  Future<void> logout() async {
    await _repo.logout();
    state = null;
  }

  Future<void> deleteAccount() async {
    await _repo.requestAccountDeletion();
    state = null;
  }
}
