#!/usr/bin/env bash
# Read-only repository checks, followed by a fresh distributable.
set -euo pipefail
cd "$(dirname "$0")/.."
SLUG=inline-order-editor-pro
mkdir -p build/quality
php tests/plugin-check-gate.php
while IFS= read -r -d '' file; do php -l "$file"; done < <(find . -type f -name '*.php' ! -path './build/*' ! -path './.git/*' ! -path './vendor/*' ! -path './modules/invoicing/vendor/*' -print0)
# Seed stale excluded content to prevent incremental-build distribution leaks.
mkdir -p "build/$SLUG/tests"
printf stale > "build/$SLUG/tests/ci-stale-sentinel.php"
printf stale > "build/$SLUG/phpunit.xml.dist"
bash bin/build.sh --zip
php tests/package.php "build/$SLUG"
# Inspect the ZIP itself, not just the source build directory.
php bin/ci-archive.php "build/$SLUG.zip" "$SLUG"
