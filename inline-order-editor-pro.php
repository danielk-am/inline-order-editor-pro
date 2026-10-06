<?php
/**
 * Plugin Name: Inline Order Editor Pro
 * Plugin URI: https://danielk.am
 * Description: Adds saved terms, order discounts, repeat orders, one-step "mark as paid" and draft products from typed items to Inline Order Editor for WooCommerce.
 * Version: 1.0.2
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce, inline-order-editor-for-woocommerce
 * Author: Daniel Kam
 * Author URI: https://danielk.am
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: inline-order-editor-pro
 * WC requires at least: 10.0
 * WC tested up to: 11.1
 *
 * @package Inline_Order_Editor_Pro
 */

defined( 'ABSPATH' ) || exit;

define( 'IOEFWP_VERSION', '1.0.2' );
define( 'IOEFWP_PLUGIN_FILE', __FILE__ );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

require_once __DIR__ . '/includes/class-ioefwp-saved-terms.php';
require_once __DIR__ . '/includes/class-ioefwp-discount.php';
require_once __DIR__ . '/includes/class-ioefwp-order-actions.php';
require_once __DIR__ . '/includes/class-ioefwp-plugin.php';

// The add-on starts when Inline Order Editor says it has loaded, and not before.
add_action( 'ioefw_loaded', array( 'IOEFWP_Plugin', 'init' ) );
