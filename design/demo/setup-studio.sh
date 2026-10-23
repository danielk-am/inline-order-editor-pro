#!/usr/bin/env bash
# Recreate a synthetic local Studio demo from supplied release artifacts.
set -euo pipefail
: "${ALKAMY_DEMO_SITE:?Set ALKAMY_DEMO_SITE to an existing fresh local Studio site directory}"
: "${ALKAMY_DEMO_PRIVATE:?Set ALKAMY_DEMO_PRIVATE to a private directory outside the site root}"
: "${ALKAMY_FREE_ZIP:?Set ALKAMY_FREE_ZIP to the built free plugin zip}"
: "${ALKAMY_PRO_ZIP:?Set ALKAMY_PRO_ZIP to the built Marketplace Pro zip}"
: "${ALKAMY_WOO_SOURCE:?Set ALKAMY_WOO_SOURCE to a WooCommerce plugin directory or zip}"
command -v studio >/dev/null
studio --version >/dev/null
wp_demo() { studio wp --path "$ALKAMY_DEMO_SITE" "$@"; }
support="$(cd "$(dirname "$0")" && pwd)"
cd "$ALKAMY_DEMO_SITE"
[ -f wp-config.php ] || { echo 'Create a fresh local site in WordPress Studio first.' >&2; exit 1; }
demo_home_url="$(wp_demo option get home --quiet | sed -n '/^http[s]*:\/\//p' | tail -n 1)"
case "$demo_home_url" in http://localhost:*|http://127.0.0.1:*) ;; *) echo 'Refusing a site whose home is not localhost.' >&2; exit 1;; esac
mkdir -p wp-content/mu-plugins wp-content/plugins wp-content/alkamy-demo-setup
cp "$support/alkamy-demo-guard.php" wp-content/mu-plugins/alkamy-demo-guard.php
cp "$support/seed.php" "$support/petal-stem-logo.png" wp-content/alkamy-demo-setup/
if [ -d "$ALKAMY_WOO_SOURCE" ]; then
 [ -f "$ALKAMY_WOO_SOURCE/woocommerce.php" ] || { echo 'WooCommerce directory missing woocommerce.php.' >&2; exit 1; }
 [ ! -e wp-content/plugins/woocommerce ] || { echo 'WooCommerce already exists; use a fresh site.' >&2; exit 1; }
 cp -R "$ALKAMY_WOO_SOURCE" wp-content/plugins/woocommerce
else
 wp_demo plugin install "$ALKAMY_WOO_SOURCE"
fi
wp_demo plugin install "$ALKAMY_FREE_ZIP"
wp_demo plugin install "$ALKAMY_PRO_ZIP"
wp_demo plugin activate woocommerce inline-order-editor-for-woocommerce inline-order-editor-pro
# This environment variable must reach the local CLI process; seed fails closed if absent.
export ALKAMY_DEMO_PRIVATE
wp_demo eval-file wp-content/alkamy-demo-setup/seed.php
rm -rf wp-content/alkamy-demo-setup
printf '%s\n' 'Demo prepared. Open the local Studio site and consult the private manifest for the order/layout IDs.'
