#!/usr/bin/env bash
# Release build of the dentist app (S9.5) with the right settings baked in, so a store build can never ship fake
# data or the emulator's API address by mistake.
#
#   API_BASE_URL=https://api.<domain> tool/build_release.sh <staging|prod> [appbundle|apk|ipa]
#
# Optional: ERROR_TRACKING_DSN (Sentry/GlitchTip, §8.6). Android signing: android/key.properties or ANDROID_KEYSTORE_*
# (mobile/README.md). The iOS build needs macOS with Xcode (D15).
set -euo pipefail
cd "$(dirname "$0")/.."

FLAVOR="${1:?usage: build_release.sh <staging|prod> [appbundle|apk|ipa]}"
TARGET="${2:-appbundle}"
[[ "$FLAVOR" == staging || "$FLAVOR" == prod ]] || { echo "flavor must be staging or prod" >&2; exit 2; }
: "${API_BASE_URL:?set API_BASE_URL, e.g. https://api.example.com}"
[[ "$API_BASE_URL" == https://* ]] || { echo "API_BASE_URL must be https" >&2; exit 2; }

# pubspec "1.2.0+14" -> "1.2.0 (14)", the X-App-Version the API checks against its minimum.
pub_version="$(grep -E '^version:' pubspec.yaml | awk '{print $2}')"
app_version="${pub_version%%+*} (${pub_version##*+})"
env_name="$([[ "$FLAVOR" == prod ]] && echo production || echo staging)"

defines=(
  --dart-define=MOCK_DATA=false
  --dart-define=API_BASE_URL="$API_BASE_URL"
  --dart-define=APP_VERSION="$app_version"
  --dart-define=APP_ENV="$env_name"
  --dart-define=ERROR_TRACKING_DSN="${ERROR_TRACKING_DSN:-}"
)
flavor_args=(--flavor "$FLAVOR")
[[ "$TARGET" == ipa ]] && flavor_args=() # iOS flavors come with the Xcode schemes on the Mac build (D15)

echo "building $TARGET · $FLAVOR · $app_version · $API_BASE_URL"
flutter build "$TARGET" --release "${flavor_args[@]}" "${defines[@]}" --obfuscate --split-debug-info=build/symbols/"$FLAVOR"
