#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHPUNIT_BIN="${PHPUNIT_BIN:-phpunit}"
cd "$ROOT"
# Coverage must include the installed ZIP's code, which may differ from this checkout.
phpunit_args=(--configuration phpunit.xml.dist)
if [[ -n "${IOEFW_TEST_WP_ROOT:-}" ]]; then
  for directory in includes modules/invoicing/includes; do
    installed="$IOEFW_TEST_WP_ROOT/wp-content/plugins/inline-order-editor-pro/$directory"
    if [[ -d "$installed" ]]; then phpunit_args+=(--coverage-filter "$installed"); fi
  done
fi
if [[ "$PHPUNIT_BIN" == *.phar ]]; then
  exec php "$PHPUNIT_BIN" "${phpunit_args[@]}" "$@"
fi
exec "$PHPUNIT_BIN" "${phpunit_args[@]}" "$@"
