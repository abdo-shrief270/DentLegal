import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;

import '../../../core/api/api_client.dart';
import '../../../core/api/api_config.dart';
import '../../../core/api/api_error.dart';
import '../../../core/api/token_store.dart';
import '../domain/auth_models.dart';
import '../domain/auth_repository.dart';

/// Invite-only OTP sign-in against the Laravel API (PROJECT1_PLAN.md §4.2, S5.1–S5.3).
class HttpAuthRepository implements AuthRepository {
  HttpAuthRepository(this._api, this._tokens, {String? platform}) : _platform = platform ?? (!kIsWeb && Platform.isIOS ? 'ios' : 'android');

  final ApiClient _api;
  final TokenStore _tokens;
  final String _platform;

  @override
  Future<OtpRequest> requestOtp(String phone) => _auth(() async {
    final data = await _api.post<Json>('/auth/otp/request', body: {'phone': phone});
    return OtpRequest(
      phone: phone,
      resendAfter: Duration(seconds: (data['resend_after_s'] as num?)?.toInt() ?? 60),
      expiresIn: Duration(seconds: (data['expires_in_s'] as num?)?.toInt() ?? 300),
    );
  });

  @override
  Future<Session> verifyOtp(String phone, String code) => _auth(() async {
    final data = await _api.post<Json>(
      '/auth/otp/verify',
      body: {
        'phone': phone,
        'code': code,
        'device': {'platform': _platform, 'app_version': ApiConfig.appVersion},
      },
    );
    await _tokens.write(data['token'] as String);
    return sessionFromMe(data['user'] as Json);
  });

  @override
  Future<Session> completeProfile(DentistProfile profile, {required bool acceptedTerms, required bool acceptedAiProcessing}) => _auth(() async {
    await _api.put<Json>(
      '/me/profile',
      body: {
        'name': profile.name,
        'specialty': profile.specialty,
        'city': profile.city,
        'facility_name': profile.facility,
        'scfhs_license_no': (profile.licenseNo?.trim().isEmpty ?? true) ? null : profile.licenseNo!.trim(),
      },
    );
    // Consents are versioned: record the versions the server says are current.
    final me = await _api.get<Json>('/me');
    final current = (me['consents'] as Map).cast<String, dynamic>();
    String version(String type) => (current[type] as Map)['current_version'] as String;
    final consents = [
      if (acceptedTerms) ...['terms', 'privacy'],
      if (acceptedAiProcessing) 'ai_processing',
    ];
    if (consents.isNotEmpty) {
      await _api.post<Object?>(
        '/me/consents',
        body: {
          'consents': [
            for (final t in consents) {'type': t, 'version': version(t)},
          ],
        },
      );
    }
    return sessionFromMe(await _api.get<Json>('/me'));
  });

  @override
  Future<void> logout() async {
    try {
      await _api.post<Object?>('/auth/logout');
    } on ApiError {
      // Signing out locally must work offline too.
    } finally {
      await _tokens.clear();
    }
  }

  @override
  Future<void> requestAccountDeletion() => _auth(() async {
    await _api.delete('/me');
    await _tokens.clear();
  });

  @override
  Future<Session?> restore() async {
    if (await _tokens.read() == null) return null;
    try {
      return sessionFromMe(await _api.get<Json>('/me'));
    } on ApiError catch (e) {
      if (e.status == 401 || e.status == 403) await _tokens.clear();
      return null; // offline at start: the sign-in screen, with the token kept for the next start
    }
  }

  /// `GET /me` (MeResource) → the app's session. A dentist without a completed profile gets `profile: null`.
  static Session sessionFromMe(Json me) {
    final profile = me['profile'] as Json?;
    final needsProfile = me['needs_profile'] as bool? ?? profile == null;
    return Session(
      phone: (me['phone'] as String? ?? '').replaceFirst('+966', ''),
      profile: needsProfile || profile == null
          ? null
          : DentistProfile(
              name: me['name'] as String? ?? '',
              specialty: profile['specialty'] as String? ?? '',
              city: profile['city'] as String? ?? '',
              facility: profile['facility_name'] as String? ?? '',
              licenseNo: profile['scfhs_license_no'] as String?,
            ),
    );
  }

  /// The auth screens show [AuthException] messages; API errors carry the server's Arabic message.
  Future<T> _auth<T>(Future<T> Function() call) async {
    try {
      return await call();
    } on ApiError catch (e) {
      throw AuthException(e.code, e.message);
    }
  }
}
