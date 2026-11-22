import 'package:riverpod_annotation/riverpod_annotation.dart';

import '../data/fake_auth_repository.dart';
import '../domain/auth_models.dart';
import '../domain/auth_repository.dart';

part 'auth_controller.g.dart';

@Riverpod(keepAlive: true)
AuthRepository authRepository(Ref ref) => FakeAuthRepository();

/// The signed-in dentist, or null. The token itself would live in secure storage (Keychain/Keystore).
@Riverpod(keepAlive: true)
class AuthController extends _$AuthController {
  @override
  Session? build() => null;

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
