<?php
/** Explicitly opted-in document fields forwarded to official payment gateways. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Payment_Metadata {
 public static function init() {
  add_filter( 'wcpay_metadata_from_order', array( __CLASS__, 'forward' ), 10, 2 );
  add_filter( 'wc_stripe_intent_metadata', array( __CLASS__, 'forward' ), 10, 2 );
 }
 public static function forward( $metadata, $order ) {
  if ( ! is_array( $metadata ) || ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) { return $metadata; }
  foreach ( Alkamy_Order_Enhancements::clean_fields( $order->get_meta( '_alkamy_custom_fields' ) ) as $row ) {
   // Keep provider metadata intact. Never truncate document values to fit payment records.
   if ( ! $row['send_to_payment_provider'] || mb_strlen( $row['value'] ) > 500 || count( $metadata ) >= 50 ) { continue; }
   $slug = substr( sanitize_key( $row['label'] ), 0, 23 );
   $base = 'alkamy_' . $slug . '_' . substr( hash( 'sha256', $row['label'] ), 0, 8 );
   $key = $base;$suffix = 1;
   while ( array_key_exists( $key, $metadata ) ) {
    if ( $metadata[$key] === $row['value'] ) { continue 2; }
    $tail = '_' . ++$suffix;$key = substr( $base, 0, 40 - strlen( $tail ) ) . $tail;
   }
   $metadata[$key] = $row['value'];
  }
  return $metadata;
 }
}
