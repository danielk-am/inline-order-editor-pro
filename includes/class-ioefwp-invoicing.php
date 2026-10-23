<?php
/**
 * Loads the document module after every active plugin has been included.
 *
 * @package Inline_Order_Editor_Pro
 */
defined( 'ABSPATH' ) || exit;

/** Keep standalone and bundled document code from registering duplicate handlers. */
final class IOEFWP_Invoicing {
	/** @var bool Whether this bootstrap has already been considered. */
	private static $started = false;

	/** Register the bundled module only when its dependencies exist. */
	public static function init() {
		if ( self::$started ) {
			return;
		}
		self::$started = true;
		if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'IOEFW_Orders' ) || ! class_exists( 'IOEFW_Terms' ) || ! class_exists( 'IOEFW_Ajax' ) ) {
			return;
		}
		foreach ( array( 'Alkamy_Service', 'Alkamy_Documents', 'Alkamy_Admin', 'Alkamy_Workspace', 'Alkamy_Order_Enhancements', 'Alkamy_Payment_Metadata' ) as $class ) {
			if ( class_exists( $class, false ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'standalone_notice' ) );
				return;
			}
		}
		if ( defined( 'ALKAMY_INVOICING_FILE' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'standalone_notice' ) );
			return;
		}
		require_once dirname( __DIR__ ) . '/modules/invoicing/invoicing.php';
	}

	/** Explain the intentional ownership choice to administrators. */
	public static function standalone_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		echo '<div class="notice notice-info"><p>' . esc_html__( 'Alkamy Order Editor Pro for WooCommerce is using the active standalone Alkamy document plugin. Its bundled invoicing module is paused to prevent duplicate document handlers. Deactivate the standalone plugin to use Pro invoicing.', 'inline-order-editor-pro' ) . '</p></div>';
	}
}
