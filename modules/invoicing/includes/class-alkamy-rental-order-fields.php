<?php
/**
 * Admin adapter for Asia Mannequin's existing quotation rental details.
 *
 * The source snippet remains responsible for checkout and email output. This
 * adapter changes its admin display only, preserving its persisted field schema.
 */
defined( 'ABSPATH' ) || exit;

final class Alkamy_Rental_Order_Fields {
 /** Register independently of when Code Snippets evaluates the source class. */
 public static function init() {
  add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display' ), 9 );
  add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'process' ), 40 );
 }

 /** Only adapt orders that the installed source component already understands. */
 private static function supported( $data ) {
  return class_exists( 'AM_WPCRQ_Rental_Fields', false ) && is_array( $data ) && in_array( $data['am_method'] ?? '', array( 'collection', 'delivery' ), true );
 }

 /** Replace the source's paragraphs with ordinary WooCommerce admin controls. */
 public static function display( $order ) {
  $data = $order->get_meta( '_am_rental_details' );
  if ( ! Alkamy_Workspace::mode_enabled() || ! self::supported( $data ) ) { return; }
  remove_action( 'woocommerce_admin_order_data_after_billing_address', array( 'AM_WPCRQ_Rental_Fields', 'display' ), 10 );
  echo '<div class="alkamy-rental-fields">';
  wp_nonce_field( 'alkamy_rental_order', '_alkamy_rental_nonce', false );
  echo '<input type="hidden" name="alkamy_rental_present" value="1">';
  woocommerce_wp_select( array( 'id'=>'alkamy_rental_method', 'name'=>'alkamy_rental[am_method]', 'label'=>__( 'Collection or delivery', 'inline-order-editor-pro' ), 'value'=>$data['am_method'], 'options'=>array( 'collection'=>__( 'Self-collection', 'inline-order-editor-pro' ), 'delivery'=>__( 'Delivery', 'inline-order-editor-pro' ) ) ) );
  woocommerce_wp_text_input( array( 'id'=>'alkamy_rental_location', 'name'=>'alkamy_rental[am_location]', 'label'=>__( 'Location', 'inline-order-editor-pro' ), 'value'=>$data['am_location'] ?? '', 'custom_attributes'=>array( 'maxlength'=>'500' ) ) );
  foreach ( array( 'start'=>__( 'Requested collection / delivery', 'inline-order-editor-pro' ), 'return'=>__( 'Requested return', 'inline-order-editor-pro' ) ) as $part=>$label ) {
   $value = ( $data['am_' . $part . '_date'] ?? '' ) . 'T' . ( $data['am_' . $part . '_time'] ?? '' );
   woocommerce_wp_text_input( array( 'id'=>'alkamy_rental_' . $part, 'name'=>'alkamy_rental[' . $part . ']', 'label'=>$label, 'type'=>'datetime-local', 'value'=>'T' === $value ? '' : $value, 'custom_attributes'=>array( 'step'=>'60' ) ) );
  }
  woocommerce_wp_textarea_input( array( 'id'=>'alkamy_rental_information', 'name'=>'alkamy_rental[additional_info]', 'label'=>__( 'Additional information', 'inline-order-editor-pro' ), 'value'=>$data['additional_info'] ?? '', 'rows'=>4, 'custom_attributes'=>array( 'maxlength'=>'5000' ) ) );
  echo '<p class="description">' . esc_html__( 'Save rental details with Update order. These requested times do not create or change a reservation.', 'inline-order-editor-pro' ) . '</p></div>';
 }

 // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Translated validation messages are escaped by save() at the only UI output boundary.
 /** Validate a complete edit before changing any of the source metadata. */
 public static function clean( $existing, $input ) {
  if ( ! is_array( $input ) ) { throw new InvalidArgumentException( __( 'Rental details must contain valid field values.', 'inline-order-editor-pro' ) ); }
  $values = array();
  foreach ( array( 'am_method'=>20, 'am_location'=>500, 'start'=>16, 'return'=>16, 'additional_info'=>5000 ) as $key=>$limit ) {
   if ( ! isset( $input[$key] ) || ! is_string( $input[$key] ) || strlen( $input[$key] ) > $limit ) { throw new InvalidArgumentException( __( 'Rental details contain a missing or oversized field. Previous rental details were preserved.', 'inline-order-editor-pro' ) ); }
   $values[$key] = 'additional_info' === $key ? sanitize_textarea_field( $input[$key] ) : sanitize_text_field( $input[$key] );
  }
  if ( ! in_array( $values['am_method'], array( 'collection', 'delivery' ), true ) || ( 'delivery' === $values['am_method'] && '' === $values['am_location'] ) ) { throw new InvalidArgumentException( __( 'Choose self-collection or delivery and enter a location for delivery. Previous rental details were preserved.', 'inline-order-editor-pro' ) ); }
  $timezone = wp_timezone();
  $values['timezone'] = $timezone->getName();
  $dates = array();
  foreach ( array( 'start', 'return' ) as $part ) {
   if ( '' === $values[$part] ) { $dates[$part] = null;continue; }
   $date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $values[$part], $timezone );
   if ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $values[$part] ) { throw new InvalidArgumentException( __( 'Enter valid rental dates and times. Previous rental details were preserved.', 'inline-order-editor-pro' ) ); }
   $dates[$part] = $date;
  }
  if ( (bool) $dates['start'] !== (bool) $dates['return'] || ( $dates['start'] && $dates['return'] <= $dates['start'] ) ) { throw new InvalidArgumentException( __( 'Enter both requested times, with return after collection or delivery. Previous rental details were preserved.', 'inline-order-editor-pro' ) ); }
  $result = $existing;
  foreach ( array( 'am_method', 'am_location', 'timezone', 'additional_info' ) as $key ) { $result[$key] = $values[$key]; }
  foreach ( array( 'start', 'return' ) as $part ) {
   $result['am_' . $part . '_date'] = $dates[$part] ? $dates[$part]->format( 'Y-m-d' ) : '';
   $result['am_' . $part . '_time'] = $dates[$part] ? $dates[$part]->format( 'H:i' ) : '';
  }
  return $result;
 }

 // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

 /** Save after the native order data and document workspace have been processed. */
 public static function process( $order_id ) {
  $order = wc_get_order( $order_id );
  if ( $order && self::save( $order ) ) { $order->save_meta_data(); }
 }

 /** Validate and stage the existing metadata without changing order status or totals. */
 public static function save( $order ) {
  if ( ! Alkamy_Workspace::mode_enabled() || ! isset( $_POST['alkamy_rental_present'] ) || ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $order->get_id() ) ) { return; }
  if ( ! isset( $_POST['_alkamy_rental_nonce'] ) || ! is_string( $_POST['_alkamy_rental_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_alkamy_rental_nonce'] ) ), 'alkamy_rental_order' ) ) { return; }
  $existing = $order->get_meta( '_am_rental_details' );
  if ( ! self::supported( $existing ) ) { return; }
  try {
   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- clean validates every field before updating the existing metadata.
   $data = self::clean( $existing, wp_unslash( $_POST['alkamy_rental'] ?? array() ) );
   $order->update_meta_data( '_am_rental_details', $data );
   return true;
  } catch ( InvalidArgumentException $error ) {
   WC_Admin_Meta_Boxes::add_error( esc_html( $error->getMessage() ) );
  }
 }
}
