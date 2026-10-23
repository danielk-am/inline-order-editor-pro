<?php
/** Free/open-source bootstrap regression. Run: php tests/licensing.php */
define( 'ABSPATH', __DIR__ );
$GLOBALS['hooks'] = array();
$GLOBALS['options'] = array( 'ioefwp_direct_license' => array( 'key' => 'legacy-private-key', 'status' => 'active' ) );
$before_options = $GLOBALS['options'];
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { add_action( $hook, $callback, $priority, $args ); }
function get_option( $key, $default = false ) { throw new RuntimeException( 'Bootstrap must not read license state.' ); }
function update_option() { throw new RuntimeException( 'Bootstrap must not mutate saved options.' ); }
function delete_option() { throw new RuntimeException( 'Bootstrap must not delete saved options.' ); }
function wp_safe_remote_post() { throw new RuntimeException( 'Bootstrap must not contact a license server.' ); }
function check( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } echo 'PASS ' . $message . "\n"; }
require dirname( __DIR__ ) . '/inline-order-editor-pro.php';
check( ! class_exists( 'IOEFWP_Licensing', false ), 'Commercial licensing client is not loaded.' );
check( ! is_file( dirname( __DIR__ ) . '/includes/class-ioefwp-licensing.php' ), 'Commercial licensing client is absent from source distribution.' );
foreach ( array( 'admin_post_ioefwp_license', 'pre_set_site_transient_update_plugins', 'plugins_api', 'admin_menu' ) as $hook ) {
 check( empty( $GLOBALS['hooks'][$hook] ), 'No licensing hook: ' . $hook );
}
check( in_array( array( 'IOEFWP_Plugin', 'init' ), $GLOBALS['hooks']['ioefw_loaded'], true ), 'Pro order features bootstrap without a key.' );
check( in_array( array( 'IOEFWP_Invoicing', 'init' ), $GLOBALS['hooks']['plugins_loaded'], true ), 'Invoicing bootstrap remains available without a key.' );
check( $before_options === $GLOBALS['options'], 'Legacy saved licensing data is left untouched.' );
