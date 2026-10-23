<?php
/** Integration tests must target a disposable, loopback-only WordPress installation. */
$root = getenv( 'IOEFW_TEST_WP_ROOT' );
if ( '1' !== getenv( 'IOEFW_TEST_ALLOW_MUTATIONS' ) || ! $root || ! is_file( $root . '/wp-load.php' ) ) {
    throw new RuntimeException( 'Set IOEFW_TEST_WP_ROOT and IOEFW_TEST_ALLOW_MUTATIONS=1 for a disposable store.' );
}
define( 'WP_HTTP_BLOCK_EXTERNAL', true );
// Cron HTTP is blocked by the same pre_http_request seal.
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
// Preinitialize filters before plugins load; no gateway/webhook HTTP or SMTP delivery.
$GLOBALS['wp_filter']['pre_http_request'][PHP_INT_MAX][] = array( 'function' => static function () { return new WP_Error( 'test_network_blocked', 'Test network sealed.' ); }, 'accepted_args' => 3 );
$GLOBALS['wp_filter']['pre_wp_mail'][PHP_INT_MAX][] = array( 'function' => static function () { return true; }, 'accepted_args' => 2 );
require $root . '/wp-load.php';
if ( ! in_array( wp_parse_url( get_option( 'siteurl' ), PHP_URL_HOST ), array( 'localhost', '127.0.0.1', '::1' ), true ) ) {
    throw new RuntimeException( 'Refusing test mutations on a non-loopback site.' );
}
if ( ! class_exists( 'WC_Order' ) || ! class_exists( 'IOEFW_Orders' ) ) {
    throw new RuntimeException( 'Activate WooCommerce and Inline Order Editor in the disposable store.' );
}
$expected = getenv( 'IOEFW_TEST_EXPECT_HPOS' );
if ( $expected && ( 'yes' === $expected ) !== \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
    throw new RuntimeException( 'Order storage does not match IOEFW_TEST_EXPECT_HPOS.' );
}

require_once WC_ABSPATH . 'includes/admin/wc-admin-functions.php';
