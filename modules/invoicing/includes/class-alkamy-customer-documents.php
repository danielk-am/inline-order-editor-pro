<?php
/** Authenticated customer access to immutable issued invoices. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Customer_Documents {
 public static function register() {
  add_filter('woocommerce_my_account_my_orders_actions',array(__CLASS__,'actions'),20,2);
  add_action('admin_post_alkamy_customer_invoice',array(__CLASS__,'download'));
  add_action('admin_post_nopriv_alkamy_customer_invoice',array(__CLASS__,'deny'));
 }
 /** Never authorize by billing email or a guest order key. */
 public static function allowed($order) {
  if(!is_user_logged_in() || !$order instanceof WC_Order || $order instanceof WC_Order_Refund){return false;}
  return ((int)$order->get_customer_id()>0 && (int)$order->get_customer_id()===get_current_user_id()) || (current_user_can('manage_woocommerce') && current_user_can('edit_shop_order',$order->get_id()));
 }
 public static function actions($actions,$order) {
  if(!self::allowed($order) || !Alkamy_Invoice_Delivery::get_issued($order->get_id())){return $actions;}
  unset($actions['wpcpi_invoice']);
  $actions['alkamy_invoice']=array('url'=>wp_nonce_url(add_query_arg(array('action'=>'alkamy_customer_invoice','order_id'=>$order->get_id()),admin_url('admin-post.php')),'alkamy_customer_invoice_'.$order->get_id()),'name'=>__('Invoice','inline-order-editor-pro'));
  return $actions;
 }
 public static function deny() {wp_die(esc_html__('You do not have permission to download this document.','inline-order-editor-pro'),'',array('response'=>403));}
 public static function download() {
  $id=isset($_GET['order_id']) && is_scalar($_GET['order_id']) ? absint($_GET['order_id']) : 0;
  $nonce=isset($_GET['_wpnonce']) && is_string($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
  $order=wc_get_order($id);
  if(!self::allowed($order) || !wp_verify_nonce($nonce,'alkamy_customer_invoice_'.$id)){self::deny();return;}
  try{$bytes=Alkamy_Invoice_Delivery::bytes($id);}catch(Throwable $error){wp_die(esc_html__('This invoice is not available. Please contact the store.','inline-order-editor-pro'),'',array('response'=>404));return;}
  self::stream($bytes,'invoice-'.$id.'.pdf');
 }
 public static function stream($bytes,$filename,$inline=false) {
  nocache_headers();header('Cache-Control: private, no-store, max-age=0');header('X-Content-Type-Options: nosniff');header('Content-Type: application/pdf');header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.sanitize_file_name($filename).'"');header('Content-Length: '.strlen($bytes));
  echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated PDF bytes; HTML escaping corrupts a binary download.
  exit;
 }
}
