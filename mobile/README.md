# DentLegal — dentist app

Flutter app for dentists (Android and iOS), Project 1. See `PROJECT1_PLAN.md` §6. Arabic only, right-to-left, using the UI from the approved prototype (`site/prototype/`, screens `m*`).

## Stack

| Concern | Choice |
|---|---|
| Flutter / Dart | 3.50 / 3.14 (Material from `package:material_ui`) |
| State | Riverpod 3 with code generation (`@riverpod`) |
| Models | freezed 4 |
| Navigation | go_router (`StatefulShellRoute` for the four tabs) |
| Dates | Hijri (Umm al-Qura) and Gregorian, Western digits (`lib/core/utils/dates.dart`) |
| Font / icons | IBM Plex Sans Arabic (OFL, bundled in `assets/fonts/`), Lucide |

## Structure

```
lib/
  core/          theme, router, shared widgets, date and bidi helpers
  features/
    auth/        onboarding, phone login, OTP, profile completion
    cases/       home, cases list, case details, processing, analysis, draft, approved reply
    case_wizard/ four-step new-case wizard and document picker
    consult/     AI legal assistant chat
    account/     account, feedback
```

Every feature is split into `domain/` (models, repository interfaces), `data/` (fake and HTTP repositories) and `presentation/` (pages, widgets, providers). Screens depend only on the repository interfaces; the providers pick the fake or the HTTP implementation.

## Real API mode

```bash
flutter run --dart-define=MOCK_DATA=false --dart-define=API_BASE_URL=http://10.0.2.2:8000
```

`API_BASE_URL` is the Laravel host without `/api/v1` (`10.0.2.2` is the Android emulator's alias for the dev machine; on a phone use the machine's LAN address, or the staging HTTPS host). `APP_VERSION` defaults to `1.0.0 (1)` and must stay equal to `version` in `pubspec.yaml`: the API refuses builds below `DENTLEGAL_MIN_APP_VERSION` with 426.

| Piece | Where |
|---|---|
| HTTP client: JSON, `X-App-Version`, bearer token, error envelope → `ApiError` (Arabic message), 401 → back to sign-in | `lib/core/api/api_client.dart` |
| Token: Keychain / Keystore via `flutter_secure_storage`; restored before the first frame | `lib/core/api/token_store.dart`, `lib/main.dart` |
| Auth: OTP, device registration, profile, versioned consents, logout, account deletion | `features/auth/data/http_auth_repository.dart` |
| Cases: list, detail, create + encrypted uploads, analysis start (idempotency key), status polling every 3 s, analysis, draft, send for review (records the lawyer-access consent), ratings, «نص المادة» | `features/cases/data/http_cases_repository.dart`, `api_mappers.dart` |
| Consultant: suggested questions, threads, polling the answer every 2 s | `features/consult/data/http_consult_repository.dart` |
| Feedback | `features/account/data/http_feedback_repository.dart` |
| Documents: PDF from Files (`file_picker`), photo from the camera (`image_picker`); ≤ 20 MB | `features/case_wizard/data/device_document_picker.dart` |

Tests: `test/api/` runs the HTTP layer against recorded API shapes with a fake Dio adapter (no network). `test_live/` is a smoke test against a running Laravel with the demo seeder (sign in by OTP, profile, create a case with a PDF, list, logout); see the command at the top of the file. It passed against the current backend.

Protections (real API mode on a device):

| | |
|---|---|
| Biometric lock (S5.4) | `local_auth`: locked on a signed-in cold start and after 5 minutes in the background; the switch in «حسابي» (on by default, turning it on asks for the biometric); «تسجيل الدخول برمز التحقق» signs out to the OTP flow. `lib/core/security/` |
| Document viewer (S3.12) | Tap a document on the case screen: the original comes through the 5-minute signed link into memory (PDF with `pdfrx`, images with zoom), never to disk, with screenshots blocked |
| Screenshot protection (S9.1) | `screen_protector` on the document viewer, analysis, draft and approved screens (Android FLAG_SECURE, iOS screenshot protection); iOS app-switcher snapshot blurred |

Still to add: push (`firebase_messaging`, once the Firebase project exists) and the native document scanner. Not yet run on a physical device: camera, file picker, secure storage, biometrics and screenshot blocking are checked by the analyzer and unit tests only.

## Fake data mode

All data is synthetic and lives in memory (`features/*/data/fake_*.dart`). Every tab shows a «بيانات تجريبية» badge while `MOCK_DATA` is on (the default, used by the web preview). No real patient data is used anywhere.

Demo sign-in:

| Phone | Result |
|---|---|
| `551234417` | Dr Ahmed, profile complete, three sample cases |
| any other number starting with 5 | new account → profile completion |
| `500000000` | "not invited" error |

The OTP is always `123456`. The analysis pipeline runs in about seven seconds.


## Release builds

Two flavors (Android): `staging` (`sa.dentlegal.dentlegal.staging`, «DentLegal تجريبي», installs next to production)
and `prod` (`sa.dentlegal.dentlegal`). Always build releases with the script; it turns fake data off and sets the API
host, the `X-App-Version` from `pubspec.yaml` and the environment:

```bash
API_BASE_URL=https://api.<domain> tool/build_release.sh prod appbundle
```

```bash
API_BASE_URL=https://api-staging.<domain> tool/build_release.sh staging apk
```

`ERROR_TRACKING_DSN=…` adds crash reporting (§8.6). Debug symbols go to `build/symbols/<flavor>`; keep them with each
release to read obfuscated stack traces.

**Android upload key** (once, kept by the client and Abdelrahman in the password manager, never in git):

```bash
keytool -genkey -v -keystore dentlegal-upload.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

Then `android/key.properties` (ignored by git):

```properties
storeFile=/path/to/dentlegal-upload.jks
storePassword=…
keyAlias=upload
keyPassword=…
```

In CI the same values come from `ANDROID_KEYSTORE_PATH`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS` and
`ANDROID_KEY_PASSWORD`. Without a key, release builds are signed with the debug key so they run locally; Google Play
rejects those, so an unsigned store build can't go out by mistake. Enrol in Play App Signing when creating the app.

**iOS:** the IPA is built on macOS with Xcode (Codemagic or a macOS runner, D15); the staging/prod schemes and the
signing profiles are set up there.

## Commands

```bash
flutter pub get
dart run build_runner build   # after changing @riverpod or @freezed code
flutter analyze               # must report no issues
flutter test
flutter run                   # device or emulator
flutter build web --no-web-resources-cdn   # browser preview used for screenshots
```

Formatting uses a 160-character line (`analysis_options.yaml`): `dart format lib test`.
