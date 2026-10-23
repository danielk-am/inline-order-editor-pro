#!/usr/bin/env bash
# Build a separate WooCommerce Marketplace candidate without direct-distribution promotions.
# Same plugin folder/IDs; this script never replaces the normal distribution ZIP.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SLUG=inline-order-editor-pro
DEST="$ROOT/build/marketplace"
bash "$ROOT/bin/build.sh"
mkdir -p "$DEST/$SLUG"
rsync -a --delete --delete-excluded \
  --exclude='/modules/invoicing/includes/class-alkamy-direct-distribution.php' \
  "$ROOT/build/$SLUG/" "$DEST/$SLUG/"
php "$ROOT/tests/package.php" "$DEST/$SLUG"
php "$ROOT/tests/marketplace-package.php" "$DEST/$SLUG" "$ROOT/build/$SLUG"
rm -f "$DEST/$SLUG.zip"
(cd "$DEST" && zip -rqX "$SLUG.zip" "$SLUG" -x '*.DS_Store')
php "$ROOT/bin/ci-archive.php" "$DEST/$SLUG.zip" "$SLUG"
echo "$DEST/$SLUG.zip"
