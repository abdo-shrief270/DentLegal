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

Every feature is split into `domain/` (models, repository interfaces), `data/` (fake repositories for now) and `presentation/` (pages, widgets, providers). Screens depend only on the repository interfaces, so the real Laravel API clients replace the fakes in the providers without UI changes.

## Fake data mode

The API is not built yet. All data is synthetic and lives in memory (`features/*/data/fake_*.dart`). Every tab shows a «بيانات تجريبية» badge while `MOCK_DATA` is on (the default). No real patient data is used anywhere.

Demo sign-in:

| Phone | Result |
|---|---|
| `551234417` | Dr Ahmed, profile complete, three sample cases |
| any other number starting with 5 | new account → profile completion |
| `500000000` | "not invited" error |

The OTP is always `123456`. The analysis pipeline runs in about seven seconds.

Native features are UI-only for now and arrive with the backend: secure token storage, biometric unlock, screenshot protection, the document scanner, push notifications.

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
