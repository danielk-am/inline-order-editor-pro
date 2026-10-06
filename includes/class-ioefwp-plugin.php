<?php
/**
 * Starts the add-on and loads its script on the order screen.
 *
 * @package Inline_Order_Editor_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add-on bootstrap.
 */
final class IOEFWP_Plugin {

	/**
	 * Hooks. Runs when Inline Order Editor has loaded.
	 */
	public static function init() {
		if ( ! class_exists( 'IOEFW_Orders' ) || ! class_exists( 'IOEFW_Terms' ) || ! class_exists( 'IOEFW_Ajax' ) ) {
			return;
		}

		IOEFWP_Saved_Terms::init();
		IOEFWP_Discount::init();
		IOEFWP_Order_Actions::init();

		add_action( 'add_meta_boxes', array( __CLASS__, 'setup_order_screen' ), 30, 2 );
	}

	/**
	 * Loads the script and styles on the screen where one order is edited.
	 *
	 * WordPress passes a post type here, and WooCommerce's own orders screen passes its screen ID.
	 *
	 * @param string $screen_or_post_type The screen or post type the boxes are for.
	 * @param mixed  $post_or_order       The order being edited.
	 */
	public static function setup_order_screen( $screen_or_post_type, $post_or_order = null ) {
		$order_screens = array( 'shop_order', function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order' );

		if ( ! in_array( $screen_or_post_type, $order_screens, true ) ) {
			return;
		}

		$order = $post_or_order instanceof WC_Order ? $post_or_order : ( $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : false );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$settings = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( IOEFWP_Discount::NONCE ),
			'orderId' => $order->get_id(),
		);

		wp_enqueue_style( 'ioefwp-order', plugins_url( 'assets/css/pro.css', IOEFWP_PLUGIN_FILE ), array(), IOEFWP_VERSION );
		wp_enqueue_script( 'ioefwp-order', plugins_url( 'assets/js/pro-order.js', IOEFWP_PLUGIN_FILE ), array( 'jquery', 'wp-i18n', 'ioefw-order-editor' ), IOEFWP_VERSION, true );
		wp_set_script_translations( 'ioefwp-order', 'inline-order-editor-pro' );
		wp_add_inline_script( 'ioefwp-order', 'window.ioefwpSettings = ' . wp_json_encode( $settings ) . ';', 'before' );
	}
}
