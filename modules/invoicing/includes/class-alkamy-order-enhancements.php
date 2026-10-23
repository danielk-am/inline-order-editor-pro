<?php
/** Inline document header and explicit draft catalogue creation. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Order_Enhancements {
 public static function init() {
  add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'header' ) );
  add_action( 'wp_ajax_ioefw_add_item', array( __CLASS__, 'ajax_product' ), 5 );
 }
 public static function clean_fields( $rows ) {
  $result = array();
  foreach ( array_slice( is_array( $rows ) ? $rows : array(), 0, 20 ) as $row ) {
   if ( ! is_array( $row ) ) { continue; }
   $label = is_scalar( $row['label'] ?? null ) ? sanitize_text_field( (string) $row['label'] ) : '';
   $value = is_scalar( $row['value'] ?? null ) ? sanitize_text_field( (string) $row['value'] ) : '';
   if ( '' !== $label && '' !== $value ) { $result[] = array( 'label'=>mb_substr( $label, 0, 120 ), 'value'=>mb_substr( $value, 0, 1000 ), 'send_to_payment_provider'=>in_array( $row['send_to_payment_provider'] ?? false, array( true, 1, '1' ), true ) ); }
  }
  return $result;
 }
 public static function header( $order ) {
  echo '<div class="alkamy-mode-controls"><div class="alkamy-mode-toggle" id="alkamy-invoice-mode-control" data-enabled="' . (Alkamy_Workspace::mode_enabled() ? '1' : '0') . '" data-label="' . esc_attr__('Invoicing mode','inline-order-editor-pro') . '" data-endpoint="' . esc_url(admin_url('admin-ajax.php')) . '" data-nonce="' . esc_attr(wp_create_nonce('alkamy_invoice_mode')) . '"><label><input type="checkbox" role="switch" id="alkamy-invoice-mode" ' . checked(Alkamy_Workspace::mode_enabled(),true,false) . '> ' . esc_html__('Invoicing mode','inline-order-editor-pro') . '</label></div><span class="alkamy-mode-status" role="status"></span><div class="alkamy-order-links"><a href="' . esc_url(Alkamy_Admin::url()) . '" target="_blank" rel="noopener" title="' . esc_attr__('Invoicing settings (opens in a new tab)','inline-order-editor-pro') . '">' . esc_html__('Settings','inline-order-editor-pro') . ' <span aria-hidden="true">↗</span></a></div></div>';

  if ( ! Alkamy_Workspace::mode_enabled() ) { return; }
  $same = $order->meta_exists( '_alkamy_shipping_same_billing' ) ? (bool) $order->get_meta( '_alkamy_shipping_same_billing' ) : ! $order->get_shipping_address_1();
  echo '<input type="hidden" name="alkamy_shipping_same_saved" value="' . ( $same ? '1' : '0' ) . '"><input type="hidden" name="alkamy_shipping_toggle_present" value="1">';
  self::fields( $order );
 }
 public static function shipping_choice( $order, $same ) {
  $order->update_meta_data( '_alkamy_shipping_same_billing', $same ? '1' : '0' );
  if ( $same ) { foreach ( array( 'first_name','last_name','company','address_1','address_2','city','state','postcode','country','phone' ) as $key ) { $get = 'get_billing_' . $key;$set = 'set_shipping_' . $key;$order->$set( $order->$get() ); } }
 }
 public static function fields( $order ) {
  Alkamy_Workspace::identifiers( $order );
  echo '<section class="alkamy-custom-fields"><h3>' . esc_html__( 'Document details', 'inline-order-editor-pro' ) . '</h3><p>' . esc_html__( 'Custom fields appear on documents. Save with Update order. Only checked fields are sent to WooPayments or Stripe payment records. Do not send sensitive information. Values over 500 characters are skipped; forwarding stops when the provider limit of 50 metadata entries is reached.', 'inline-order-editor-pro' ) . '</p><input type="hidden" name="alkamy_fields_present" value="1"><p class="alkamy-reorder-status screen-reader-text" role="status" aria-live="polite"></p><div class="alkamy-field-rows">';
  foreach ( self::clean_fields( $order->get_meta( '_alkamy_custom_fields' ) ) as $i=>$row ) { self::row( $i, $row ); }
  echo '</div><template class="alkamy-field-template">';self::row( '__INDEX__', array( 'label'=>'', 'value'=>'' ) );echo '</template><button type="button" class="button alkamy-field-add">' . esc_html__( 'Add custom field', 'inline-order-editor-pro' ) . '</button></section>';
 }
 private static function row( $i, $row ) {
  echo '<div class="alkamy-custom-field"><button type="button" class="alkamy-field-drag" aria-label="Reorder field. Drag, or use Up and Down arrow keys." title="Drag to reorder. Keyboard: Up or Down arrow."><span aria-hidden="true">⠿</span></button><input aria-label="' . esc_attr__( 'Field label', 'inline-order-editor-pro' ) . '" placeholder="' . esc_attr__( 'Label, e.g. Purchase order', 'inline-order-editor-pro' ) . '" maxlength="120" name="alkamy_custom_fields[' . esc_attr( $i ) . '][label]" value="' . esc_attr( $row['label'] ) . '"><input aria-label="' . esc_attr__( 'Field value', 'inline-order-editor-pro' ) . '" placeholder="' . esc_attr__( 'Value', 'inline-order-editor-pro' ) . '" maxlength="1000" name="alkamy_custom_fields[' . esc_attr( $i ) . '][value]" value="' . esc_attr( $row['value'] ) . '"><button type="button" class="button-link-delete alkamy-field-remove" aria-label="Remove field">×</button><label class="alkamy-field-provider"><input type="checkbox" name="alkamy_custom_fields[' . esc_attr( $i ) . '][send_to_payment_provider]" value="1" ' . checked( ! empty( $row['send_to_payment_provider'] ), true, false ) . '> ' . esc_html__( 'Send to payment provider', 'inline-order-editor-pro' ) . '</label></div>';
 }
 public static function preview_address( $order, $values ) {
  if ( ! is_array( $values ) ) { return; }
  foreach ( array( 'billing', 'shipping' ) as $kind ) {
   foreach ( array( 'first_name','last_name','company','address_1','address_2','city','state','postcode','country','email','phone' ) as $key ) {
    $name = '_' . $kind . '_' . $key;
    if ( 'shipping' === $kind && 'email' === $key ) { continue; }
    if ( isset( $values[$name] ) && is_scalar( $values[$name] ) ) { $method = 'set_' . $kind . '_' . $key;$order->$method( sanitize_text_field( (string) $values[$name] ) ); }
   }
  }
 }
 public static function save_fields( $order ) {
  if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $order->get_id() ) ) { return; }
  $sections_nonce = isset( $_POST['_alkamy_sections_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_alkamy_sections_nonce'] ) ), 'alkamy_order_sections' );
  $action_nonce = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'alkamy_action' );
  if ( ! $sections_nonce && ! $action_nonce ) { return; }
  if ( isset( $_POST['alkamy_shipping_toggle_present'] ) || isset( $_POST['alkamy_shipping_same_billing'] ) ) { self::shipping_choice( $order, '1' === sanitize_key( wp_unslash( $_POST['alkamy_shipping_same_billing'] ?? '0' ) ) ); }
  if ( isset( $_POST['alkamy_fields_present'] ) ) { $order->update_meta_data( '_alkamy_custom_fields', self::clean_fields( map_deep( wp_unslash( $_POST['alkamy_custom_fields'] ?? array() ), 'sanitize_text_field' ) ) ); }
 }
 public static function ajax_product() {
  check_ajax_referer( IOEFW_Ajax::NONCE, 'security' );
  if ( 'custom_product' !== sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) ) ) { return; }
  $id = absint( $_POST['order_id'] ?? 0 );
  if ( ! current_user_can( 'edit_products' ) || ! current_user_can( 'edit_shop_orders' ) || ! current_user_can( 'edit_shop_order', $id ) ) { wp_send_json_error( array( 'message'=>'You need permission to edit this order and create products.', 'code'=>'forbidden' ), 403 ); }
  $order = wc_get_order( $id );
  if ( ! $order ) { wp_send_json_error( array( 'message'=>'Order not found.' ) ); }
  $before = IOEFW_Orders::plain_price( $order->get_total(), $order );
  $args = array();foreach ( array( 'name','quantity','price','note','tax_class' ) as $key ) { $args[$key] = sanitize_text_field( wp_unslash( $_POST[$key] ?? '' ) ); }
  $tax = array();if ( isset( $_POST['country'] ) ) { foreach ( array( 'country','state','postcode','city' ) as $key ) { $tax[$key] = sanitize_text_field( wp_unslash( $_POST[$key] ?? '' ) ); } }
  $result = self::add_product( $order, $args, sanitize_text_field( wp_unslash( $_POST['hash'] ?? '' ) ), $tax );
  if ( is_wp_error( $result ) ) { wp_send_json_error( array_merge( IOEFW_Ajax::fragments( wc_get_order( $order->get_id() ) ), array( 'message'=>$result->get_error_message(), 'code'=>$result->get_error_code() ) ) ); }
  wp_send_json_success( array_merge( IOEFW_Ajax::fragments( $result ), array( 'totalBefore'=>$before, 'total'=>IOEFW_Orders::plain_price( $result->get_total(), $result ) ) ) );
 }
 public static function add_product( $order, $args, $hash, $tax = array() ) {
  if ( ! current_user_can( 'edit_products' ) || ! current_user_can( 'edit_shop_orders' ) || ! current_user_can( 'edit_shop_order', $order->get_id() ) ) { return new WP_Error( 'forbidden', 'You need permission to edit this order and create products.' ); }
  if ( $order->get_currency() !== get_woocommerce_currency() || IOEFW_Orders::amounts_include_tax( $order ) !== wc_prices_include_tax() ) { return new WP_Error( 'currency', 'Save products only from an order using the store currency and price tax setting.' ); }
  // One operation per order at a time. A hard process crash deliberately leaves the lock
  // for operator reconciliation instead of risking duplicate catalogue products on retry.
  $lock = 'alkamy_product_lock_' . $order->get_id();
  if ( ! add_option( $lock, time(), '', false ) ) { return new WP_Error( 'busy', 'Product creation is already running. If it was interrupted, an administrator must reconcile the order before clearing its product lock.' ); }
  $product = null;
  try {
   $order = wc_get_order( $order->get_id() );$can = IOEFW_Orders::can_edit( $order );
   if ( is_wp_error( $can ) ) { return $can; }
   if ( ! hash_equals( IOEFW_Orders::items_hash( $order ), $hash ) ) { return new WP_Error( 'stale', 'The order changed. Review the refreshed items before adding another product.' ); }
   wc_transaction_query( 'start' );
   $ids = array_keys( $order->get_items() );
   $result = IOEFW_Orders::add_custom_item( $order, $args, $tax );
   if ( is_wp_error( $result ) ) { wc_transaction_query( 'rollback' );return $result; }
   $added = array_diff_key( $result->get_items(), array_flip( $ids ) );
   if ( 1 !== count( $added ) ) { throw new RuntimeException( 'Could not identify the new order line.' ); }
   $item = reset( $added );
   $product = new WC_Product_Simple();$product->set_name( $item->get_name() );$product->set_status( 'draft' );$product->set_catalog_visibility( 'hidden' );
   $price = (float) $item->get_subtotal();if ( wc_prices_include_tax() ) { $price += (float) $item->get_subtotal_tax(); }
   $product->set_regular_price( wc_format_decimal( $price / $item->get_quantity() ) );$product->set_tax_class( $item->get_tax_class() );$product->save();
   $item->set_product_id( $product->get_id() );$item->save();
   $result->add_order_note( sprintf( 'Saved order item as draft product #%d.', $product->get_id() ) );
   wc_transaction_query( 'commit' );return wc_get_order( $order->get_id() );
  } catch ( Throwable $e ) { wc_transaction_query( 'rollback' );if ( $product && $product->get_id() ) { clean_post_cache( $product->get_id() ); }return new WP_Error( 'product_failed', 'Could not save the draft product. Refresh and review the order before retrying.' ); }
  finally { delete_option( $lock ); }
 }
}
