import 'package:dentlegal/core/router/app_router.dart';
import 'package:dentlegal/features/auth/domain/auth_models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const noProfile = Session(phone: '551234417');
  const complete = Session(
    phone: '551234417',
    profile: DentistProfile(name: 'د. أحمد', specialty: 'جراحة الفم', city: 'جدة', facility: 'عيادة تجريبية'),
  );

  test('signed-out users only reach the public screens', () {
    expect(redirectFor(null, '/onboarding'), isNull);
    expect(redirectFor(null, '/otp'), isNull);
    expect(redirectFor(null, '/home'), '/onboarding');
    expect(redirectFor(null, '/cases/c-0152/draft'), '/onboarding');
  });

  test('a session without a profile is held on profile completion', () {
    expect(redirectFor(noProfile, '/home'), '/profile/complete');
    expect(redirectFor(noProfile, '/profile/complete'), isNull);
  });

  test('a complete session skips the public screens', () {
    expect(redirectFor(complete, '/login'), '/home');
    expect(redirectFor(complete, '/profile/complete'), '/home');
    expect(redirectFor(complete, '/cases/c-0152'), isNull);
  });
}
