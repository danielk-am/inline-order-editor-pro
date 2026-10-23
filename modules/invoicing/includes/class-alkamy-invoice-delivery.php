<?php
/** Immutable issued invoices and private, request-lifetime email attachments. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Invoice_Delivery {
 private static $temporary = array();
 public static function init() {
  add_filter( 'woocommerce_email_attachments', array( __CLASS__, 'attachments' ), 30, 4 );
  add_action( 'shutdown', array( __CLASS__, 'cleanup' ), PHP_INT_MAX );
 }
 public static function settings() {
  $value = get_option( 'alkamy_invoice_delivery', array() );
  if ( ! is_array( $value ) ) { return array(); }
  $allowed = array( 'customer_processing_order', 'customer_completed_order', 'customer_invoice' );
  return array(
   'enabled'=>! empty( $value['enabled'] ) && in_array( $value['enabled'], array( true, 1, '1', 'yes' ), true ),
   'email_ids'=>array_key_exists( 'email_ids', $value ) ? ( is_array( $value['email_ids'] ) ? array_values( array_intersect( $allowed, array_filter( $value['email_ids'], 'is_string' ) ) ) : array() ) : array('customer_processing_order','customer_completed_order'),
   'numbering_prefix'=>isset( $value['numbering_prefix'] ) && is_string( $value['numbering_prefix'] ) ? $value['numbering_prefix'] : '{year}{month}-',
   'number_padding'=>isset( $value['number_padding'] ) && is_scalar( $value['number_padding'] ) ? min( 10, max( 2, (int) $value['number_padding'] ) ) : 6,
  );
 }
 /** Insert-only semantics: core add_option can UPSERT and overwrite a competing claim. */
 public static function atomic_add( $key, $value, $unused = '', $autoload = false ) {
  if ( ! is_string( $key ) || 0 !== strpos( $key, 'alkamy_' ) || strlen( $key ) > 190 ) { throw new InvalidArgumentException( 'Invalid private archive key.' ); }
  global $wpdb;
  $previous = $wpdb->suppress_errors( true );
  try {
   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Atomic INSERT without UPSERT is essential for immutable claims and archives; option_name has a unique index.
   $inserted = $wpdb->insert( $wpdb->options, array( 'option_name'=>$key, 'option_value'=>maybe_serialize( $value ), 'autoload'=>'off' ), array( '%s', '%s', '%s' ) );
  } finally { $wpdb->suppress_errors( $previous ); }
  wp_cache_delete( $key, 'options' );wp_cache_delete( 'notoptions', 'options' );
  return 1 === $inserted;
 }
 private static function key( $id ) { return 'alkamy_issued_invoice_' . abs( (int) $id ); }
 /** Archives are database-only, explicitly excluded from autoload. */
 public static function get_issued( $id ) {
  $value = get_option( self::key( $id ), null );
  return is_array( $value ) ? $value : null;
 }
 public static function bytes( $id ) {
  $record = self::get_issued( $id );
  $bytes = $record ? base64_decode( $record['pdf'] ?? '', true ) : false;
  if ( ! is_string( $bytes ) || 0 !== strpos( $bytes, '%PDF-' ) || ! hash_equals( (string) ( $record['sha256'] ?? '' ), hash( 'sha256', $bytes ) ) ) {
   throw new RuntimeException( 'The issued invoice archive is missing or damaged. Restore it from a backup before resending.' );
  }
  return $bytes;
 }
 public static function assert_number_available( $number, $order_id ) {
  $owner = get_option( 'alkamy_invoice_claim_' . hash( 'sha256', $number ), null );
  if ( null !== $owner && (int) $owner !== (int) $order_id ) { throw new RuntimeException( 'Invoice number has already been issued or reserved for another order.' ); }
 }
 private static function claim( $number, $id ) {
  $key = 'alkamy_invoice_claim_' . hash( 'sha256', $number );
  self::assert_number_available( $number, $id );
  if ( self::assigned_elsewhere( $number, $id ) ) { throw new RuntimeException( 'Invoice number is assigned to another order.' ); }
  if ( ! self::atomic_add( $key, (int) $id, '', false ) && (int) get_option( $key ) !== (int) $id ) {
   throw new RuntimeException( 'Invoice number was reserved concurrently. Retry with a different number.' );
  }
 }
 private static function assigned_elsewhere( $number, $id ) {
  $statuses = array_merge( array_keys( wc_get_order_statuses() ), array( 'trash' ) );
  $metadata = array( array( 'key'=>'_alkamy_number_invoice', 'value'=>$number, 'compare'=>'=' ) );
  if ( class_exists( 'Automattic\\WooCommerce\\Utilities\\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
   $ids = wc_get_orders( array( 'type'=>'shop_order', 'status'=>$statuses, 'limit'=>2, 'return'=>'ids', 'meta_query'=>$metadata ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded exact-number lookup before issue.
  } else {
   $ids = get_posts( array( 'post_type'=>'shop_order', 'post_status'=>$statuses, 'posts_per_page'=>2, 'fields'=>'ids', 'meta_query'=>$metadata ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded exact-number lookup for legacy order storage.
  }
  return (bool) array_diff( array_map( 'intval', $ids ), array( (int) $id ) );
 }
 private static function reserve_number( $number, $id ) {
  $key = 'alkamy_invoice_reservation_' . (int) $id;
  if ( ! self::atomic_add( $key, $number ) && get_option( $key ) !== $number ) { throw new RuntimeException( 'This order already reserved another invoice number. Administrator review is required.' ); }
 }
 private static function number( $order ) {
  $reserved = get_option( 'alkamy_invoice_reservation_' . (int) $order->get_id(), '' );
  $assigned = $order->get_meta( '_alkamy_number_invoice' );
  if ( $reserved ) {
   if ( $assigned && $assigned !== $reserved ) { throw new RuntimeException( 'The saved invoice number differs from its reserved reference.' ); }
   self::claim( $reserved, $order->get_id() );return $reserved;
  }
  if ( is_scalar( $assigned ) && '' !== (string) $assigned ) { self::claim( (string) $assigned, $order->get_id() );self::reserve_number( (string) $assigned, $order->get_id() );return (string) $assigned; }
  $settings = self::settings();$period = wp_date( 'Ym' );
  $prefix = strtr( sanitize_text_field( $settings['numbering_prefix'] ?? '{year}{month}-' ), array( '{year}'=>wp_date( 'Y' ), '{month}'=>wp_date( 'm' ) ) );
  $padding = min( 10, max( 2, (int) ( $settings['number_padding'] ?? 6 ) ) );
  if ( strlen( $prefix ) > 70 || false === strpos( $settings['numbering_prefix'], '{year}' ) || false === strpos( $settings['numbering_prefix'], '{month}' ) ) { throw new RuntimeException( 'Invoice numbering prefix is too long.' ); }
  $counter_key = 'alkamy_invoice_counter_' . $period;
  $counter = max( 0, (int) get_option( $counter_key, 0 ) );
  // Counter is a hint only. Atomic unique option-name claims provide correctness even across concurrent workers.
  for ( $attempt = 0; $attempt < 10000; $attempt++ ) {
   $counter++;$number = $prefix . str_pad( (string) $counter, $padding, '0', STR_PAD_LEFT );
   $key = 'alkamy_invoice_claim_' . hash( 'sha256', $number );
   if ( ! self::assigned_elsewhere( $number, $order->get_id() ) && self::atomic_add( $key, (int) $order->get_id(), '', false ) ) { self::reserve_number( $number, $order->get_id() );update_option( $counter_key, $counter, false );return $number; }
  }
  throw new RuntimeException( 'Invoice sequence allocation needs administrator review.' );
 }
 /** Reserve an invoice identity without rendering, issuing or sending it. */
 public static function reserve_invoice_number( $order ) {
  $id = (int) $order->get_id();
  if ( $id < 1 ) { throw new RuntimeException( 'Save the order before assigning document numbers.' ); }
  $lock = 'alkamy_invoice_lock_' . $id;
  if ( ! self::atomic_add( $lock, time() ) ) { throw new RuntimeException( 'This invoice is being prepared. Retry after the other request finishes.' ); }
  try {
   $issued = self::get_issued( $id );
   $number = $issued ? $issued['number'] : self::number( $order );
   $order->update_meta_data( '_alkamy_number_invoice', $number );
   $order->save_meta_data();
   return $number;
  } finally { delete_option( $lock ); }
 }
 /** Render once, publish archive atomically, then save its reference through WooCommerce CRUD. */
 public static function issue( $order ) { return self::store( $order ); }
 public static function import_legacy( $order, $number, $bytes, $source = 'wpc', $created = '' ) {
  if ( ! is_string( $number ) || '' === $number || strlen( $number ) > 100 || sanitize_text_field( $number ) !== $number ) { throw new InvalidArgumentException( 'Invalid legacy invoice number.' ); }
  if ( ! is_string( $bytes ) || 0 !== strpos( $bytes, '%PDF-' ) || strlen( $bytes ) > 20000000 ) { throw new InvalidArgumentException( 'Legacy invoice requires a valid PDF smaller than 20 MB.' ); }
  return self::store( $order, array( 'number'=>$number, 'bytes'=>$bytes, 'source'=>$source, 'created'=>$created ) );
 }
 private static function store( $order, $import = null ) {
  $id = (int) $order->get_id();if ( $id < 1 ) { throw new RuntimeException( 'Save the order before issuing an invoice.' ); }
  $lock = 'alkamy_invoice_lock_' . $id;
  if ( ! self::atomic_add( $lock, time(), '', false ) ) { throw new RuntimeException( 'This invoice is already being prepared. If a prior process stopped, an administrator must inspect its lock before retrying.' ); }
  try {
   $persisted = wc_get_order( $id );
   if ( ! $persisted instanceof WC_Order ) { throw new RuntimeException( 'The saved order is unavailable.' ); }
   $order = $persisted;$order->read_meta_data( true );
   $existing = self::get_issued( $id );
   if ( $existing ) {
    self::bytes( $id );
    if ( $import && ( $existing['number'] !== $import['number'] || ! hash_equals( $existing['sha256'], hash( 'sha256', $import['bytes'] ) ) ) ) { throw new RuntimeException( 'An issued invoice cannot be replaced by different legacy content.' ); }
    $order->update_meta_data( '_alkamy_number_invoice', $existing['number'] );$order->save_meta_data();return $existing;
   }
   if ( $import ) {
    $number = $import['number'];$bytes = $import['bytes'];self::claim( $number, $id );self::reserve_number( $number, $id );
   } else {
    // Validate financial reconciliation before consuming an invoice reference.
    Alkamy_Local_Pdf::record( $order, 'invoice' );
    $number = self::number( $order );
    // Save the reserved number before render; retry must never silently consume a new number.
    $order->update_meta_data( '_alkamy_number_invoice', $number );$order->save_meta_data();
    $record = Alkamy_Local_Pdf::record( $order, 'invoice' );$record['created'] = gmdate( 'c' );
    $bytes = Alkamy_Local_Pdf::render( $record );
   }
   if ( ! is_string( $bytes ) || 0 !== strpos( $bytes, '%PDF-' ) || strlen( $bytes ) > 20000000 ) { throw new RuntimeException( 'Invoice archive requires a valid PDF smaller than 20 MB.' ); }
   $archive = array( 'number'=>$number, 'pdf'=>base64_encode( $bytes ), 'sha256'=>hash( 'sha256', $bytes ), 'created'=>$import && $import['created'] ? $import['created'] : gmdate( 'c' ), 'source'=>$import ? sanitize_text_field( $import['source'] ) : 'alkamy' );
   if ( ! self::atomic_add( self::key( $id ), $archive, '', false ) ) { throw new RuntimeException( 'Invoice archive could not be published. No replacement was written.' ); }
   $order->update_meta_data( '_alkamy_number_invoice', $number );$order->save_meta_data();
   return $archive;
  } finally { delete_option( $lock ); }
 }
 /** Woo runs this before wp_mail reads attachment files. Keep them until PHP shutdown. */
 public static function attachments( $attachments, $email_id, $object, $email = null ) {
  $settings = self::settings();
  $allowed = array( 'customer_processing_order', 'customer_completed_order', 'customer_invoice' );
  $selected = isset( $settings['email_ids'] ) && is_array( $settings['email_ids'] ) ? array_intersect( $allowed, $settings['email_ids'] ) : $allowed;
  if ( empty( $settings['enabled'] ) || ! in_array( $email_id, $selected, true ) || ! $object instanceof WC_Order ) { return $attachments; }
  $wpc = 'wpc-pdf-invoices-premium/wpc-pdf-invoices.php';
  if ( in_array( $wpc, (array) get_option( 'active_plugins', array() ), true ) || array_key_exists( $wpc, (array) get_site_option( 'active_sitewide_plugins', array() ) ) ) { return $attachments; }
  try {
   self::issue( $object );$id = (int) $object->get_id();
   if ( ! isset( self::$temporary[$id] ) ) {
    $directory = sys_get_temp_dir() . '/alkamy-invoice-' . bin2hex( random_bytes( 16 ) );
    if ( ! mkdir( $directory, 0700 ) ) { throw new RuntimeException( 'Private attachment workspace is unavailable.' ); } // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Owner-only volatile workspace, never web uploads.
    $path = $directory . '/invoice-' . $id . '.pdf';
    $bytes = self::bytes( $id );
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents,WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- Owner-only request-lifetime file; wp_mail requires a local path.
    if ( strlen( $bytes ) !== file_put_contents( $path, $bytes, LOCK_EX ) || ! chmod( $path, 0600 ) ) {
     if ( is_file( $path ) ) { wp_delete_file( $path ); }
     rmdir( $directory ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Removes only the random private directory created above.
     throw new RuntimeException( 'Private invoice attachment could not be written.' );
    }
    self::$temporary[$id] = $path;
   }
   if ( ! is_readable( self::$temporary[$id] ) || ! hash_equals( self::get_issued( $id )['sha256'], (string) hash_file( 'sha256', self::$temporary[$id] ) ) ) { throw new RuntimeException( 'The temporary invoice attachment is unavailable.' ); }
   $object->delete_meta_data( '_alkamy_invoice_delivery_error' );$object->save_meta_data();
   if ( ! in_array( self::$temporary[$id], $attachments, true ) ) { $attachments[] = self::$temporary[$id]; }
  } catch ( Throwable $error ) {
   // Avoid personal information, filesystem paths or renderer internals in logs.
   wc_get_logger()->error( 'Invoice attachment failed for order ' . (int) $object->get_id() . '. Review invoice numbering, archive integrity and PDF reconciliation.', array( 'source'=>'alkamy-invoice-delivery' ) );
   $object->update_meta_data( '_alkamy_invoice_delivery_error', gmdate( 'c' ) );$object->save_meta_data();
  }
  return $attachments;
 }
 public static function cleanup() {
  foreach ( self::$temporary as $path ) {
   if ( is_file( $path ) ) { wp_delete_file( $path ); }
   if ( is_dir( dirname( $path ) ) ) { rmdir( dirname( $path ) ); } // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Removes only own random private attachment workspace.
  }
  self::$temporary = array();
 }
}
