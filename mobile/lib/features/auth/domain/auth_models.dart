import 'package:freezed_annotation/freezed_annotation.dart';

part 'auth_models.freezed.dart';

@freezed
abstract class DentistProfile with _$DentistProfile {
  const factory DentistProfile({required String name, required String specialty, required String city, required String facility, String? licenseNo}) =
      _DentistProfile;

  const DentistProfile._();

  String get initials {
    final parts = name.replaceFirst(RegExp(r'^د\.\s*'), '').split(' ').where((p) => p.isNotEmpty).toList();
    return parts.take(2).map((p) => p.replaceFirst(RegExp('^ال'), '').substring(0, 1)).join();
  }

  String get shortName => name.split(' ').take(2).join(' ');
}

@freezed
abstract class Session with _$Session {
  const factory Session({required String phone, DentistProfile? profile}) = _Session;

  const Session._();

  bool get needsProfile => profile == null;
}

class OtpRequest {
  const OtpRequest({required this.phone, required this.resendAfter, required this.expiresIn});
  final String phone;
  final Duration resendAfter;
  final Duration expiresIn;
}
