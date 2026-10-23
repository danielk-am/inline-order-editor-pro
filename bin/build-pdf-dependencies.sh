#!/usr/bin/env bash
# Rebuild the pinned, isolated runtime; pass the path to an official Composer PHAR.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
COMPOSER_PHAR="${1:?Pass a Composer PHAR path}"
BUILD="$(mktemp -d "${TMPDIR:-/tmp}/alkamy-pdf-build.XXXXXXXX")"
trap 'rm -rf "$BUILD"' EXIT
mkdir -p "$BUILD/tools" "$BUILD/runtime"
cp "$ROOT/pdf-dependencies/composer.json" "$ROOT/pdf-dependencies/composer.lock" "$BUILD/tools/"
cp "$ROOT/pdf-dependencies/composer.json" "$ROOT/pdf-dependencies/composer.lock" "$BUILD/runtime/"
(cd "$BUILD/tools" && php "$COMPOSER_PHAR" install --no-interaction --no-progress)
(cd "$BUILD/runtime" && php "$COMPOSER_PHAR" install --no-dev --no-interaction --no-progress && php "$COMPOSER_PHAR" audit --no-dev)
export ALKAMY_PDF_SOURCE="$BUILD/runtime/vendor"
"$BUILD/tools/vendor/bin/php-scoper" add-prefix --config="$ROOT/pdf-dependencies/scoper.inc.php" --output-dir="$BUILD/scoped" --no-interaction
cp "$ROOT/pdf-dependencies/autoload.php" "$BUILD/scoped/autoload.php"
php "$ROOT/bin/notice-pdf-dependencies.php" "$BUILD/scoped"
mkdir -p "$ROOT/modules/invoicing/vendor"
rsync -a --delete "$BUILD/scoped/" "$ROOT/modules/invoicing/vendor/"
cp "$ROOT/pdf-dependencies/PROVENANCE.md" "$ROOT/modules/invoicing/vendor/PROVENANCE.md"
php "$ROOT/tests/local-pdf.php"
