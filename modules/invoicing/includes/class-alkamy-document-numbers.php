<?php
/** Assigned merchant document references, separate from WooCommerce order IDs. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Document_Numbers {
 public static function labels() {
  return array('invoice'=>__('Invoice','inline-order-editor-pro'),'quotation'=>__('Quotation','inline-order-editor-pro'),'delivery_order'=>__('Delivery order','inline-order-editor-pro'),'packing_slip'=>__('Packing slip','inline-order-editor-pro'));
 }
 public static function read($order) {
  $numbers=array();foreach(self::labels() as $type=>$label){$value=$order->get_meta('_alkamy_number_' . $type);$numbers[$type]=is_scalar($value) ? sanitize_text_field($value) : '';}
  return $numbers;
 }
 /** Fill missing references only on an authorized save, never while rendering a preview. */
 public static function auto_assign( $order ) {
  $id = (int) $order->get_id();
  if ( $id < 1 ) { throw new RuntimeException( 'Save the order before assigning document numbers.' ); }
  $lock = 'alkamy_document_lock_' . $id;
  if ( ! Alkamy_Invoice_Delivery::atomic_add( $lock, time() ) ) { throw new RuntimeException( 'Document numbers are being assigned. Retry after the other request finishes.' ); }
  try {
   $order->read_meta_data( true );
   $numbers = self::read( $order );
   if ( '' === $numbers['invoice'] ) { Alkamy_Invoice_Delivery::reserve_invoice_number( $order ); }
   foreach ( array( 'quotation'=>'QUOT', 'delivery_order'=>'DO', 'packing_slip'=>'PS' ) as $type=>$prefix ) {
    if ( '' !== $numbers[$type] ) { continue; }
    $reservation = 'alkamy_document_reservation_' . $type . '_' . $id;
    $number = get_option( $reservation, '' );
    if ( ! $number ) {
     $period = wp_date( 'Ym' );
     $counter_key = 'alkamy_document_counter_' . $type . '_' . $period;
     $counter = max( 0, (int) get_option( $counter_key, 0 ) );
     $allocated = false;
     for ( $attempt = 0; $attempt < 10000; $attempt++ ) {
      $number = $prefix . '-' . $period . '-' . str_pad( (string) ++$counter, 6, '0', STR_PAD_LEFT );
      $claim = 'alkamy_document_claim_' . $type . '_' . hash( 'sha256', $number );
      // Validate imported and manually assigned numbers in the active WooCommerce store too.
      try { self::apply( $order, array( $type=>$number ) ); } catch ( InvalidArgumentException $error ) { continue; }
      if ( Alkamy_Invoice_Delivery::atomic_add( $claim, $id ) ) {
       if ( ! Alkamy_Invoice_Delivery::atomic_add( $reservation, $number ) ) { throw new RuntimeException( 'Document reservation failed. Retry after administrator review.' ); }
       $allocated = true;
       update_option( $counter_key, $counter, false );
       break;
      }
      $number = '';
     }
     if ( ! $allocated ) { throw new RuntimeException( 'Document numbering needs administrator review.' ); }
    }
    $order->update_meta_data( '_alkamy_number_' . $type, $number );
    $order->save_meta_data();
   }
   return self::read( $order );
  } finally { delete_option( $lock ); }
 }
 /** Validate all supplied fields before changing the in-memory order. Saving belongs to the caller. */
 public static function apply($order,$input) {
  if(!is_array($input)){throw new InvalidArgumentException('Document numbers must be a list of fields.');}
  $values=array();$existing=self::read($order);
  foreach(self::labels() as $type=>$label){
   if(!array_key_exists($type,$input)){continue;}
   if(!is_scalar($input[$type]) || strlen((string)$input[$type])>100){throw new InvalidArgumentException('Document numbers must contain at most 100 characters.');}
   $value=sanitize_text_field($input[$type]);
   if('invoice' === $type && class_exists('Alkamy_Invoice_Delivery')){
    $issued=Alkamy_Invoice_Delivery::get_issued($order->get_id());
    $reserved=get_option('alkamy_invoice_reservation_' . (int)$order->get_id(),'');
    if(is_string($reserved) && '' !== $reserved && $value !== $reserved){throw new InvalidArgumentException('This invoice number has already been reserved and cannot be changed.');}
    if($issued && $value !== $issued['number']){throw new InvalidArgumentException('An issued invoice number cannot be changed or cleared.');}
    if('' !== $value){try{Alkamy_Invoice_Delivery::assert_number_available($value,$order->get_id());}catch(RuntimeException $error){throw new InvalidArgumentException($error->getMessage());}} // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caller escapes validation errors at the UI boundary.
   }

   if ( '' !== $value && 'invoice' !== $type ) {
    $owner = get_option( 'alkamy_document_claim_' . $type . '_' . hash( 'sha256', $value ), null );
    if ( null !== $owner && (int) $owner !== (int) $order->get_id() ) { throw new InvalidArgumentException( 'Document number has already been reserved for another order.' ); }
   }
   if('' !== $value && $value !== $existing[$type]){
    // Read only the active order store; legacy WooCommerce does not support meta_query in wc_get_orders.
    $statuses=array_merge(array_keys(wc_get_order_statuses()),array('trash'));
    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded equality read only when an assigned number changes.
    $metadata=array(array('key'=>'_alkamy_number_' . $type,'value'=>$value,'compare'=>'='));
    if(class_exists('Automattic\\WooCommerce\\Utilities\\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()){
     $matches=wc_get_orders(array('type'=>'shop_order','status'=>$statuses,'limit'=>2,'return'=>'ids','meta_query'=>$metadata)); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded changed-number lookup in HPOS.
    }else{
     $matches=get_posts(array('post_type'=>'shop_order','post_status'=>$statuses,'posts_per_page'=>2,'fields'=>'ids','meta_query'=>$metadata)); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded changed-number lookup in legacy order storage.
    }
    $matches=array_diff(array_map('intval',$matches),array((int)$order->get_id()));
    if($matches){
     /* translators: %s: document type, such as Invoice or Quotation. */
     throw new InvalidArgumentException(sprintf(__('%s number is already assigned to another order.','inline-order-editor-pro'),$label)); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Caller displays the plain exception through escaped notice or wp_die.
    }
   }
   $values[$type]=$value;
  }
  foreach($values as $type=>$value){$order->update_meta_data('_alkamy_number_' . $type,$value);}
 }
}
