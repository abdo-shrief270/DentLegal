import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:local_auth/local_auth.dart';

/// Face ID / fingerprint, behind an interface so the lock logic is testable.
abstract interface class Biometrics {
  Future<bool> available();
  Future<bool> authenticate(String reason);
}

class DeviceBiometrics implements Biometrics {
  final _auth = LocalAuthentication();

  @override
  Future<bool> available() async {
    if (kIsWeb) return false;
    try {
      return await _auth.isDeviceSupported() && await _auth.canCheckBiometrics;
    } on Object {
      return false;
    }
  }

  /// The device passcode is allowed as a fallback inside the system prompt (biometricOnly: false).
  @override
  Future<bool> authenticate(String reason) async {
    try {
      return await _auth.authenticate(localizedReason: reason, persistAcrossBackgrounding: true);
    } on Object {
      return false;
    }
  }
}

/// No biometrics (web preview, fake mode, tests).
class NoBiometrics implements Biometrics {
  const NoBiometrics();

  @override
  Future<bool> available() async => false;

  @override
  Future<bool> authenticate(String reason) async => false;
}

/// The «الدخول بالبصمة» preference, on by default after the first sign-in (PROJECT1_PLAN.md §6.6).
abstract interface class LockSettings {
  Future<bool> enabled();
  Future<void> setEnabled(bool value);
}

class SecureLockSettings implements LockSettings {
  const SecureLockSettings([this._storage = const FlutterSecureStorage()]);

  static const _key = 'dentlegal.biometric_lock';
  final FlutterSecureStorage _storage;

  @override
  Future<bool> enabled() async => (await _storage.read(key: _key)) != 'off';

  @override
  Future<void> setEnabled(bool value) => _storage.write(key: _key, value: value ? 'on' : 'off');
}

class MemoryLockSettings implements LockSettings {
  MemoryLockSettings([this._value = true]);

  bool _value;

  @override
  Future<bool> enabled() async => _value;

  @override
  Future<void> setEnabled(bool value) async => _value = value;
}
