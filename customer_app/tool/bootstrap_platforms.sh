#!/usr/bin/env bash
# Generate android/ios folders if missing (requires Flutter SDK).
set -euo pipefail
cd "$(dirname "$0")/.."
if ! command -v flutter >/dev/null 2>&1; then
  echo "Flutter SDK not found. Install from https://docs.flutter.dev/get-started/install then re-run."
  exit 1
fi
flutter create . --org ps.madd --project-name madd_customer --platforms=android,ios
flutter pub get
echo "Done. Run: flutter run --dart-define=API_BASE=https://my.madd.ps/api"
