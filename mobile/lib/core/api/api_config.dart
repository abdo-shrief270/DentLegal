import '../widgets/mock_badge.dart';

/// Build-time API settings (`--dart-define`). Example for a device on the same network as a dev machine:
/// `flutter run --dart-define=MOCK_DATA=false --dart-define=API_BASE_URL=http://192.168.1.20:8000`.
abstract final class ApiConfig {
  /// The Laravel API host, without `/api/v1`. Defaults to the Android emulator's alias for the host machine.
  static const baseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: 'http://10.0.2.2:8000');

  /// Sent as `X-App-Version`; the API answers 426 to builds below its minimum. Keep it equal to pubspec `version`.
  static const appVersion = String.fromEnvironment('APP_VERSION', defaultValue: '1.0.0 (1)');

  /// Fake repositories (no network) unless the build turns MOCK_DATA off.
  static bool get useRealApi => !MockBadge.enabled;
}
