<?php
/** WooCommerce snapshots and confirmed payment application. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Documents {
 const TYPES = array( 'invoice', 'quotation', 'packing_slip', 'delivery_order', 'credit_note' );
 public static function minor( $value ) {
  $value = (string) $value;
  if ( ! preg_match( '/^(\d+)(?:\.(\d{1,2}))?$/D', $value, $matches ) ) { throw new InvalidArgumentException( __( 'An amount has unsupported precision or is negative. Review the order before issuing.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  $result = (int) $matches[1] * 100 + (int) str_pad( isset( $matches[2] ) ? $matches[2] : '', 2, '0' );
  if ( $result > 99999999 ) { throw new InvalidArgumentException( 'Amount exceeds supported limit.' ); }
  return $result;
 }
 public static function clean_sections( $rows ) {
  $clean = array();
  foreach ( array_slice( is_array( $rows ) ? $rows : array(), 0, 31 ) as $row ) {
   if ( ! is_array( $row ) ) { continue; }
   $heading = sanitize_text_field( is_scalar( $row['heading'] ?? '' ) ? (string) ( $row['heading'] ?? '' ) : '' );
   $body = sanitize_textarea_field( is_scalar( $row['body'] ?? '' ) ? (string) ( $row['body'] ?? '' ) : '' );
   if ( 'order_terms' !== ( $row['kind'] ?? '' ) && '' === $heading && '' === $body ) { continue; }
   $clean[] = array( 'kind'=>'order_terms' === ( $row['kind'] ?? '' ) ? 'order_terms' : 'text', 'position'=>in_array( $row['position'] ?? '', array( 'before_items', 'after_items', 'footer' ), true ) ? $row['position'] : 'after_items', 'heading'=>substr( $heading, 0, 120 ), 'body'=>substr( $body, 0, 10000 ), 'documents'=>array_values( array_intersect( self::TYPES, is_array( $row['documents'] ?? null ) ? $row['documents'] : array() ) ) );
  }
  return $clean;
 }
 public static function sections( $order ) {
  if ( $order->meta_exists( '_alkamy_sections' ) ) { return self::clean_sections( $order->get_meta( '_alkamy_sections' ) ); }
  $terms = $order->get_meta( '_ioefw_order_terms' );
  return $terms ? array( array( 'heading'=>__( 'Terms', 'inline-order-editor-pro' ), 'body'=>$terms, 'documents'=>self::TYPES ) ) : array();
 }
 /** Editable terms are identified explicitly; headings remain merchant-editable text. */
 public static function editor_sections( $order ) {
  $rows = self::sections( $order );$terms = (string) $order->get_meta( '_ioefw_order_terms' );$found = false;
  if ( '' === $terms && ! $order->meta_exists( '_alkamy_sections' ) && 'auto-draft' === $order->get_status() && class_exists( 'IOEFW_Terms' ) ) { $terms = IOEFW_Terms::default_terms(); }
  foreach ( $rows as &$row ) {
   if ( 'order_terms' === ( $row['kind'] ?? '' ) || ( ! $found && '' !== $terms && $row['body'] === $terms && in_array( $row['heading'], array( 'Terms', __( 'Terms', 'inline-order-editor-pro' ), 'Order terms' ), true ) ) ) { $row['kind'] = 'order_terms';$found = true; }
  }
  unset( $row );
  if ( ! $found ) { array_unshift( $rows, array( 'kind'=>'order_terms', 'heading'=>__( 'Order terms', 'inline-order-editor-pro' ), 'body'=>$terms, 'position'=>'after_items', 'documents'=>$order->meta_exists( '_alkamy_sections' ) && ! $rows ? array() : self::TYPES ) ); }
  foreach ( $rows as &$row ) { $row['position'] = 'footer'; }unset( $row );
  if ( count( $rows ) < 2 ) { $rows[] = array( 'heading'=>'', 'body'=>'', 'position'=>'footer', 'documents'=>self::TYPES ); }
  return $rows;
 }
 /** Share deliberate terms-edit behavior between draft preview and persistence. */
 public static function normalize_order_sections( $order, $rows ) {
  $clean = self::clean_sections( $rows );
  $canonical = substr( sanitize_textarea_field( (string) $order->get_meta( '_ioefw_order_terms' ) ), 0, 10000 );
  foreach ( $clean as &$row ) {
   $row['position'] = 'footer';
   if ( 'order_terms' === $row['kind'] && ! $row['documents'] && '' !== $row['body'] && $row['body'] !== $canonical ) { $row['documents'] = self::TYPES; }
  }
  unset( $row );return $clean;
 }
 public static function save_sections( $order, $rows ) {
  $clean = self::normalize_order_sections( $order, $rows );
  foreach ( $clean as $row ) {
   if ( 'order_terms' === $row['kind'] ) { $order->update_meta_data( '_ioefw_order_terms', $row['body'] );break; }
  }
  $order->update_meta_data( '_alkamy_sections', $clean );
 }
 /** Keep an established document terms section current when edited in native order mode. */
 public static function native_terms_saved( $order, $terms ) {
  if ( ! $order->meta_exists( '_alkamy_sections' ) ) { return; }
  $rows = self::clean_sections( $order->get_meta( '_alkamy_sections' ) );$changed = false;
  foreach ( $rows as &$row ) { if ( 'order_terms' === ( $row['kind'] ?? '' ) ) { $row['body'] = $terms;$changed = true; } }unset( $row );
  if ( $changed ) { $order->update_meta_data( '_alkamy_sections', self::clean_sections( $rows ) );$order->save(); }
 }
 public static function snapshot( $order, $type ) {
  if ( ! in_array( $type, self::TYPES, true ) ) { throw new InvalidArgumentException( 'Unknown document type.' ); }
  if ( 2 !== wc_get_price_decimals() ) { throw new InvalidArgumentException( __( 'This release requires two-decimal currency precision.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  $settings = get_option( 'alkamy_identity', array() ); $lines = array();
  foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $item ) {
   $net = self::minor( wc_format_decimal( $item->get_total(), 2 ) );
   $tax = self::minor( wc_format_decimal( $item->get_total_tax(), 2 ) );
   $rates = array(); foreach ( array_keys( $item->get_taxes()['total'] ?? array() ) as $rate_id ) { $rates[] = WC_Tax::get_rate_percent_value( $rate_id ); }
   $rate = count( $rates ) === 1 ? (string) round( (float) $rates[0], 4 ) : '';
   $lines[] = array( 'name'=>$item->get_name(), 'quantity'=>$item instanceof WC_Order_Item_Product ? (float) $item->get_quantity() : 1, 'net'=>$net, 'tax'=>$tax, 'unit'=>'EA', 'taxRate'=>$rate );
  }
  $total = self::minor( wc_format_decimal( $order->get_total(), 2 ) );
  if ( array_sum( array_map( static function ( $line ) { return $line['net'] + $line['tax']; }, $lines ) ) !== $total ) { throw new RuntimeException( __( 'Line totals and tax do not match the order total. Review rounding and adjustments.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  return array( 'type'=>$type, 'orderId'=>(string) $order->get_id(), 'currency'=>$order->get_currency(), 'dueDays'=>14, 'seller'=>array( 'name'=>$settings['name'] ?? get_bloginfo( 'name' ), 'address'=>$settings['address'] ?? '', 'uen'=>$settings['uen'] ?? '', 'gst'=>$settings['gst'] ?? '', 'peppolId'=>$settings['peppolId'] ?? '' ), 'customer'=>array( 'name'=>$order->get_billing_company() ?: trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ), 'email'=>$order->get_billing_email(), 'address'=>implode( ', ', array_filter( array( $order->get_billing_address_1(), $order->get_billing_address_2(), $order->get_billing_city(), $order->get_billing_postcode(), $order->get_billing_country() ) ) ), 'uen'=>$order->get_meta( '_alkamy_buyer_uen' ), 'peppolId'=>$order->get_meta( '_alkamy_buyer_peppol' ) ), 'shipping'=>array( 'name'=>trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . ' ' . $order->get_shipping_company() ), 'address'=>implode( ', ', array_filter( array( $order->get_shipping_address_1(), $order->get_shipping_address_2(), $order->get_shipping_city(), $order->get_shipping_postcode(), $order->get_shipping_country() ) ) ) ), 'lines'=>$lines, 'total'=>$total, 'customFields'=>Alkamy_Order_Enhancements::clean_fields( $order->get_meta( '_alkamy_custom_fields' ) ), 'sections'=>self::sections( $order ) );
 }
 public static function preview( $order, $type, $sections, $uen = '', $peppol = '' ) {
  if ( 'credit_note' === $type ) { throw new RuntimeException( 'Preview an issued credit note from its document actions.' ); }
  $snapshot = self::snapshot( $order, $type );
  $snapshot['sections'] = self::normalize_order_sections( $order, $sections );
  $snapshot['customer']['uen'] = sanitize_text_field( $uen );
  $snapshot['customer']['peppolId'] = sanitize_text_field( $peppol );
  return array( 'id'=>'preview', 'number'=>'DRAFT PREVIEW', 'created'=>gmdate( 'c' ), 'status'=>'draft', 'snapshot'=>$snapshot );
 }
 public static function apply_payment( $order, $record ) {
  $s = $record['snapshot'] ?? array();
  if ( (string) $order->get_id() !== ( $s['orderId'] ?? '' ) || $order->get_currency() !== ( $s['currency'] ?? '' ) || self::minor( wc_format_decimal( $order->get_total(), 2 ) ) !== ( $s['total'] ?? null ) ) { throw new RuntimeException( __( 'The order differs from the issued invoice. Reconcile it before applying payment.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  if ( 'invoice' === ( $s['type'] ?? '' ) && 'paid' === ( $record['status'] ?? '' ) && $record['amountPaid'] === $s['total'] && 0 === $record['amountRemaining'] && ! $order->is_paid() && ! $order->has_status( 'refunded' ) ) {
   $order->set_payment_method( 'alkamy_stripe_invoice' ); $order->set_payment_method_title( 'Stripe invoice payment' ); $order->payment_complete( $record['stripeId'] );
   if ( ! $order->is_paid() ) { throw new RuntimeException( 'WooCommerce payment application did not complete. Retry synchronization.' ); }
  }
  if ( 'credit_note' === ( $s['type'] ?? '' ) && 'succeeded' === ( $record['refundStatus'] ?? '' ) && ! empty( $record['refundId'] ) ) {
   // Store the provider identity in the refund's initial persisted reason, so a crash
   // after creation cannot produce a second WooCommerce refund on retry.
   $reason = 'Alkamy Stripe refund ' . $record['refundId'];
   foreach ( $order->get_refunds() as $refund ) { if ( $reason === $refund->get_reason() ) {
    if ( self::minor( wc_format_decimal( $refund->get_amount(), 2 ) ) !== $s['total'] || $refund->get_currency() !== $s['currency'] ) { throw new RuntimeException( 'Saved WooCommerce refund differs from the provider refund.' ); }
    if ( $order->get_remaining_refund_amount() <= 0 && ! $order->has_status( 'refunded' ) ) { $order->update_status( 'refunded', 'Reconciled confirmed Stripe refund.' ); }
    return;
   } }
   if ( $order->get_total_refunded() > 0 ) { throw new RuntimeException( 'Existing WooCommerce refunds require manual reconciliation.' ); }
   $items = array();
   foreach ( $order->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $item_id=>$item ) {
    $items[ $item_id ] = array( 'qty'=>$item instanceof WC_Order_Item_Product ? $item->get_quantity() : 0, 'refund_total'=>$item->get_total(), 'refund_tax'=>$item->get_taxes()['total'] ?? array() );
   }
   $refund = wc_create_refund( array( 'order_id'=>$order->get_id(), 'amount'=>$s['total'] / 100, 'reason'=>$reason, 'line_items'=>$items, 'refund_payment'=>false, 'restock_items'=>false ) );
   if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  }

 }
 public static function records( $order ) { $records = $order->get_meta( '_alkamy_documents' ); return is_array( $records ) ? $records : array(); }
 public static function remember( $order, $record ) { $records = self::records( $order ); $records[ $record['id'] ] = $record; $order->update_meta_data( '_alkamy_documents', $records ); $order->save(); }
 public static function issue( $order, $type ) {
  if ( 'credit_note' === $type ) { throw new RuntimeException( 'Choose an issued invoice to credit.' ); }
  foreach ( self::records( $order ) as $old ) { if ( 'invoice' === $type && 'invoice' === $old['snapshot']['type'] && 'void' !== $old['status'] ) { return $old; } }
  if ( 'invoice' === $type && ( $order->is_paid() || $order->get_total_refunded() > 0 || $order->get_total() <= 0 ) ) { throw new RuntimeException( __( 'Payment invoices require an unpaid, positive order with no refunds.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  // Retain the operation snapshot before network IO. Retries must never pick up a changed order.
  $pending = $order->get_meta( '_alkamy_pending_' . $type );
  if ( ! is_array( $pending ) ) { $pending = array( 'key'=>wp_generate_uuid4(), 'document'=>self::snapshot( $order, $type ) ); $order->update_meta_data( '_alkamy_pending_' . $type, $pending ); $order->save(); }
  $record = Alkamy_Service::request( 'POST', '/v1/documents', $pending );
  self::remember( $order, $record ); $order->delete_meta_data( '_alkamy_pending_' . $type ); $order->save();
  return $record;
 }
}
