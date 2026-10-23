# Free and open-source distribution

As of Pro 1.3.6, Pro means additional features, not a paid tier. The plugin is free under GPL-2.0-or-later. No purchase, subscription, activation key or license server is required for its features. The commercial activation client and its update hooks have been removed.

## Updating

Back up the store and upload a newer official Pro ZIP through Plugins > Add Plugin > Upload Plugin, confirming replacement. Keep Alkamy Order Editor for WooCommerce installed; the current companion package is Free 1.0.10-alpha. No automatic update channel is configured.

## Existing installations

Legacy saved license options are left untouched. This version neither reads them nor makes licensing requests. It does not deactivate remote installations, cancel subscriptions, issue refunds or alter existing commercial agreements. Database backups may still contain old credentials and require appropriate access controls.

## Distribution variants

Both the direct and Marketplace-shaped packages omit commercial licensing. The Marketplace-shaped package additionally omits the existing direct-distribution promotional panel. No Marketplace approval or listing is implied. Optional legacy hosted-service behavior and all other plugin features are unchanged.

## Verification

`php tests/licensing.php` verifies bootstrap without a key, absence of the retired client and licensing hooks, and no licensing option or network access during bootstrap. Existing module tests exercise the feature loaders.
