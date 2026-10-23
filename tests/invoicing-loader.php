<?php
/** CLI regression tests for module isolation and activation order. */
if ( 'cli' !== PHP_SAPI ) { exit; }
// protects=one invoicing hook owner; fails_when=bundled and standalone initialise twice or missing base loads module; why_new=existing store tests do not exercise plugin load order.
$case = $argv[1] ?? 'normal';
define( 'ABSPATH', __DIR__ );
$GLOBALS['loader_hooks'] = array();
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['loader_hooks'][$hook][$priority][] = $callback; }
function remove_action($hook,$callback,$priority=10){if(isset($GLOBALS['loader_hooks'][$hook][$priority])){$GLOBALS['loader_hooks'][$hook][$priority]=array_values(array_filter($GLOBALS['loader_hooks'][$hook][$priority],static function($item)use($callback){return $item!==$callback;}));}}
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { add_action( $hook, $callback, $priority, $args ); }
function do_loader_action( $hook ) { $groups = $GLOBALS['loader_hooks'][$hook] ?? array();ksort($groups);foreach($groups as $callbacks){foreach($callbacks as $callback){call_user_func($callback);}} }
function hook_count( $hook ) { return array_sum(array_map('count', $GLOBALS['loader_hooks'][$hook] ?? array())); }
function loader_assert( $condition, $label ) { if(!$condition){fwrite(STDERR,'FAIL '.$label."\n");exit(1);}echo 'PASS '.$label."\n"; }
class WooCommerce {}
if ( 'missing-base' !== $case ) { class IOEFW_Orders {} class IOEFW_Terms {} class IOEFW_Ajax {} }
require dirname(__DIR__) . '/inline-order-editor-pro.php';
// Simulate the standalone plugin being included after Pro, before plugins_loaded.
if ( 'standalone' === $case ) { class Alkamy_Admin {} }
do_loader_action('plugins_loaded');
if ( 'normal' === $case ) {
 loader_assert(class_exists('Alkamy_Admin', false),'Bundled invoicing loads after all plugins are included.');
 loader_assert(1 === hook_count('admin_post_alkamy_action'),'Document action registers once.');
 do_loader_action('plugins_loaded');
 loader_assert(1 === hook_count('admin_post_alkamy_action'),'Repeated bootstrap does not duplicate document action.');
 loader_assert(is_file(ALKAMY_INVOICING_FILE) && is_file(dirname(ALKAMY_INVOICING_FILE).'/assets/admin.js'),'Module anchor locates bundled assets.');
} elseif ( 'standalone' === $case ) {
 loader_assert(!class_exists('Alkamy_Documents',false),'Late standalone class prevents bundled classes loading.');
 loader_assert(0 === hook_count('admin_post_alkamy_action'),'Collision does not initialise a second document action.');
 loader_assert(1 === hook_count('admin_notices'),'Collision has one explanatory notice.');
} else {
 loader_assert(!class_exists('Alkamy_Admin',false),'Missing base leaves invoicing module unloaded.');
 loader_assert(0 === hook_count('admin_post_alkamy_action'),'Missing base registers no document mutations.');
 loader_assert(1 === hook_count('admin_notices'),'Missing base explains how to restore Pro features.');
 function current_user_can( $capability ) { return ! empty( $GLOBALS['loader_can_activate'] ) && 'activate_plugins' === $capability; }
 function esc_html__( $text, $domain ) { return htmlspecialchars( $text, ENT_QUOTES ); }
 function esc_url( $url ) { return $url; }
 function admin_url( $path ) { return 'https://example.invalid/wp-admin/' . $path; }
 ob_start(); IOEFWP_Plugin::dependency_notice(); $hidden = ob_get_clean();
 loader_assert('' === $hidden,'Dependency notice is hidden from users who cannot manage plugins.');
 $GLOBALS['loader_can_activate'] = true;
 ob_start(); IOEFWP_Plugin::dependency_notice(); $notice = ob_get_clean();
 loader_assert(false !== strpos($notice,'Install and activate Free') && false !== strpos($notice,'https://example.invalid/wp-admin/plugins.php'),'Administrators receive the required dependency and plugin-management link.');

}
