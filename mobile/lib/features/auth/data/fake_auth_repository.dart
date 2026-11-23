import '../../../core/fake/fake_latency.dart';
import '../domain/auth_models.dart';
import '../domain/auth_repository.dart';

/// Demo rules: any Saudi mobile is "invited" except 500000000; OTP is 123456.
/// 551234417 is Dr Ahmed, who already completed his profile; other numbers go through profile completion.
class FakeAuthRepository implements AuthRepository {
  static const demoCode = '123456';
  static const demoPhone = '551234417';
  static const _notInvited = '500000000';

  final Map<String, DentistProfile> _profiles = {
    demoPhone: const DentistProfile(
      name: 'د. أحمد بن سعد الغامدي',
      specialty: 'جراحة وزراعة الأسنان',
      city: 'جدة',
      facility: 'مجمع ابتسامة الطبي (اسم تجريبي)',
      licenseNo: '21-RD-0045871',
    ),
  };
  String? _phone;

  @override
  Future<OtpRequest> requestOtp(String phone) async {
    await fakeLatency();
    if (phone == _notInvited) {
      throw const AuthException('invitation_required', 'هذا الرقم غير مدعو للتجربة. تواصل مع إدارة المنصة.');
    }
    return OtpRequest(phone: phone, resendAfter: const Duration(seconds: 60), expiresIn: const Duration(minutes: 5));
  }

  @override
  Future<Session> verifyOtp(String phone, String code) async {
    await fakeLatency();
    if (code != demoCode) throw const AuthException('otp_invalid', 'رمز التحقق غير صحيح.');
    _phone = phone;
    return Session(phone: phone, profile: _profiles[phone]);
  }

  @override
  Future<Session> completeProfile(DentistProfile profile, {required bool acceptedTerms, required bool acceptedAiProcessing}) async {
    await fakeLatency();
    if (!acceptedTerms || !acceptedAiProcessing) {
      throw const AuthException('consent_required', 'الموافقة على الشروط والمعالجة الآلية مطلوبة لاستخدام التطبيق.');
    }
    final phone = _phone ?? demoPhone;
    _profiles[phone] = profile;
    return Session(phone: phone, profile: profile);
  }

  @override
  Future<void> logout() async {
    await fakeLatency(200);
    _phone = null;
  }

  @override
  Future<void> requestAccountDeletion() async {
    await fakeLatency();
    if (_phone != null) _profiles.remove(_phone);
    _phone = null;
  }

  @override
  Future<Session?> restore() async => null;
}
