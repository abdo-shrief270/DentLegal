import 'auth_models.dart';

/// Invite-only OTP sign-in (PROJECT1_PLAN.md §4.2). Fake now; Laravel + SMS provider later.
abstract interface class AuthRepository {
  /// Throws [AuthException] with `invitation_required` when the phone is not invited.
  Future<OtpRequest> requestOtp(String phone);
  Future<Session> verifyOtp(String phone, String code);
  Future<Session> completeProfile(DentistProfile profile, {required bool acceptedTerms, required bool acceptedAiProcessing});
  Future<void> logout();
  Future<void> requestAccountDeletion();

  /// The saved session at app start (a stored token that still works), or null.
  Future<Session?> restore();
}

class AuthException implements Exception {
  const AuthException(this.code, this.message);
  final String code;
  final String message;

  @override
  String toString() => message;
}
