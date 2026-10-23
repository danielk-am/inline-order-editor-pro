<?php
/** Local read-only document exports using the isolated PDF renderer. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Local_Pdf {
 /** Report runtime support without connecting to a service. */
 public static function available() {
  return PHP_VERSION_ID >= 70400 && extension_loaded( 'dom' ) && extension_loaded( 'mbstring' ) && is_file( dirname( __DIR__ ) . '/vendor/dompdf/dompdf/src/Dompdf.php' );
 }
 /** Read-only current-order export; it never issues an invoice or creates a refund. */
 public static function record( $order, $type, $refund_id = 0 ) {
  if ( ! in_array( $type, Alkamy_Documents::TYPES, true ) ) { throw new InvalidArgumentException( 'Unknown document type.' ); }
  if ( 2 !== wc_get_price_decimals() || in_array( strtoupper( $order->get_currency() ), array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF', 'BHD', 'JOD', 'KWD', 'OMR', 'TND' ), true ) ) { throw new InvalidArgumentException( 'Local PDF exports currently require a two-decimal currency.' ); }
  $source = $order;$refund = null;
  if ( 'credit_note' === $type ) {
   foreach ( $order->get_refunds() as $candidate ) { if ( (int) $candidate->get_id() === (int) $refund_id && $refund_id > 0 ) { $refund = $candidate;break; } }
   if ( ! $refund ) { throw new InvalidArgumentException( 'Choose an existing refund belonging to this order.' ); }
   $source = $refund;
  }
  $lines = array();
  foreach ( $source->get_items( array( 'line_item', 'fee', 'shipping' ) ) as $item ) {
   $rates = array();foreach ( array_keys( $item->get_taxes()['total'] ?? array() ) as $rate_id ) { $rates[] = WC_Tax::get_rate_percent_value( $rate_id ); }
   $sign = $refund ? -1 : 1;
   $tax = $item->get_total_tax();
   // WooCommerce leaves an untaxed fee's tax unset until totals are calculated.
   $tax = '' === $tax || null === $tax ? '0' : $tax;
   $lines[] = array( 'name'=>$item->get_name(), 'quantity'=>$item instanceof WC_Order_Item_Product ? abs( (float) $item->get_quantity() ) : 1, 'net'=>$sign * self::minor( $item->get_total() ), 'tax'=>$sign * self::minor( $tax ), 'unit'=>'EA', 'taxRate'=>1 === count( $rates ) ? (string) round( (float) $rates[0], 4 ) : '' );
  }
  $total = self::minor( $refund ? $refund->get_amount() : $order->get_total() );
  $line_total = array_sum( array_map( static function ( $line ) { return $line['net'] + $line['tax']; }, $lines ) );
  if ( $refund && $line_total !== $total ) { $lines[] = array( 'name'=>__( 'Refund adjustment', 'inline-order-editor-pro' ), 'quantity'=>1, 'net'=>$total - $line_total, 'tax'=>0, 'unit'=>'EA', 'taxRate'=>'' ); }
  elseif ( $line_total !== $total ) { throw new RuntimeException( 'Line totals and tax do not match the order total. Review rounding and adjustments.' ); }
  $settings = Alkamy_Layout::identity();
  $numbers=Alkamy_Document_Numbers::read($order);
  $number=$numbers[$type] ?? '';if('' === $number){$number=__('Unnumbered draft','inline-order-editor-pro');}
  $invoice_number=$numbers['invoice'] ?: __('Unnumbered invoice','inline-order-editor-pro');
  $snapshot = array( 'layout'=>Alkamy_Layout::preset( $type ), 'type'=>$type, 'orderId'=>(string) $order->get_id(), 'currency'=>$order->get_currency(), 'dueDays'=>14,
   'paid'=>$order->is_paid(), 'orderStatus'=>$order->get_status(), 'totalRefunded'=>self::minor( $order->get_total_refunded() ),
   'seller'=>array( 'name'=>$settings['name'] ?? get_bloginfo( 'name' ), 'address'=>$settings['address'] ?? '', 'uen'=>$settings['uen'] ?? '', 'gst'=>$settings['gst'] ?? '', 'peppolId'=>$settings['peppolId'] ?? '' ),
   'customer'=>array( 'phone'=>$order->get_billing_phone(), 'firstName'=>$order->get_billing_first_name(), 'lastName'=>$order->get_billing_last_name(), 'company'=>$order->get_billing_company(), 'name'=>$order->get_billing_company() ?: trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ), 'email'=>$order->get_billing_email(), 'address'=>self::address( $order, 'billing' ), 'uen'=>$order->get_meta( '_alkamy_buyer_uen' ), 'peppolId'=>$order->get_meta( '_alkamy_buyer_peppol' ) ),
   'shipping'=>array( 'name'=>trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() . ' ' . $order->get_shipping_company() ), 'address'=>self::address( $order, 'shipping' ) ),
   'lines'=>$lines, 'total'=>$total, 'customFields'=>Alkamy_Order_Enhancements::clean_fields( $order->get_meta( '_alkamy_custom_fields' ) ), 'sections'=>Alkamy_Documents::sections( $order ) );
  if(class_exists('Alkamy_Invoice_Legacy')){$snapshot['legacy']=Alkamy_Invoice_Legacy::snapshot_values($order,$type);}
  if ( $refund ) { $snapshot['refundId'] = (string) $refund->get_id();$snapshot['refundReason'] = $refund->get_reason(); }
  $date = $source->get_date_created();
  return array( 'id'=>'local-' . $source->get_id(), 'number'=>$refund ? 'REFUND-' . $refund->get_id() : $number, 'originalNumber'=>$refund ? $invoice_number : '', 'orderReference'=>(string)$order->get_order_number(), 'created'=>$date ? $date->date( 'c' ) : gmdate( 'c' ), 'status'=>$order->get_status(), 'paymentUrl'=>'invoice' === $type && method_exists( $order, 'needs_payment' ) && $order->needs_payment() && ! $order->is_paid() && 0 === self::minor( $order->get_total_refunded() ) ? $order->get_checkout_payment_url() : '', 'localExport'=>true, 'snapshot'=>$snapshot );
 }
 private static function address( $order, $kind ) {
  $values = array();foreach ( array( 'address_1', 'address_2', 'city', 'postcode', 'country' ) as $key ) { $values[] = $order->{'get_' . $kind . '_' . $key}(); }
  return implode( ', ', array_filter( $values ) );
 }
 private static function minor( $value ) {
  $value = (string) $value;
  if ( ! preg_match( '/^-?\d{1,9}(?:\.\d{1,12})?$/D', $value ) ) { throw new InvalidArgumentException( 'An amount is invalid or exceeds the local PDF limit.' ); }
  $value = wc_format_decimal( $value, 2 );
  if ( ! preg_match( '/^(-?)(\d{1,9})(?:\.(\d{1,2})0*)?$/D', $value, $match ) ) { throw new InvalidArgumentException( 'An amount has unsupported precision or exceeds the local PDF limit.' ); }
  return ( '-' === $match[1] ? -1 : 1 ) * ( (int) $match[2] * 100 + (int) str_pad( $match[3] ?? '', 2, '0' ) );
 }
 /** Produce PDF bytes; templates cannot fetch remote or arbitrary local resources. */
 public static function render( $record ) {
  if ( ! in_array( $record['snapshot']['type'] ?? '', array( 'invoice', 'quotation', 'packing_slip', 'delivery_order', 'credit_note' ), true ) ) { throw new InvalidArgumentException( 'Unknown document type.' ); }
  if ( ! self::available() ) { throw new RuntimeException( 'The local PDF renderer requires DOM and mbstring PHP extensions.' ); }
  require_once dirname( __DIR__ ) . '/vendor/autoload.php';
  $html = self::safe_html( Alkamy_Layout::html( $record ) );
  $temporary = sys_get_temp_dir() . '/alkamy-pdf-' . bin2hex( random_bytes( 16 ) );
  if ( ! mkdir( $temporary, 0700 ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Owner-only volatile system-temp directory; WP_Filesystem credentials cannot provide local renderer isolation.
   throw new RuntimeException( 'A private PDF workspace could not be created.' );
  }
  try {
   $options = new \AlkamyPdfVendor\Dompdf\Options();
   $options->setIsRemoteEnabled( false );
   $options->setIsPhpEnabled( false );
   $options->setIsJavascriptEnabled( false );
   $options->setAllowedProtocols( array( 'data://' ) );
   $options->setChroot( array( $temporary ) );
   $options->setTempDir( $temporary );
   $options->setFontCache( $temporary );
   $options->setLogOutputFile( '' );
   $options->setDefaultMediaType( 'print' );
   $options->setDefaultFont( 'DejaVu Sans' );
   $pdf = new \AlkamyPdfVendor\Dompdf\Dompdf( $options );
   $pdf->setPaper( 'letter' === ( $record['snapshot']['layout']['brand']['paper'] ?? '' ) ? 'letter' : 'A4' );
   $pdf->loadHtml( $html, 'UTF-8' );
   $pdf->render();
   $bytes = $pdf->output();
   if ( ! is_string( $bytes ) || 0 !== strpos( $bytes, '%PDF-' ) ) { throw new RuntimeException( 'PDF generation did not produce a valid document.' ); }
   return $bytes;
  } finally {
   self::remove_workspace( $temporary );
  }
 }
 /** Remove resource-bearing markup even if a future renderer becomes too permissive. */
 private static function safe_html( $html ) {
  if ( ! is_string( $html ) || strlen( $html ) > 5000000 ) { throw new InvalidArgumentException( 'The document exceeds the supported layout size.' ); }
  $dom = new DOMDocument();
  $previous = libxml_use_internal_errors( true );
  try { $dom->loadHTML( $html, LIBXML_NONET ); } finally { libxml_clear_errors();libxml_use_internal_errors( $previous ); }
  $xpath = new DOMXPath( $dom );
  foreach ( $xpath->query( '//script|//iframe|//object|//embed|//link|//base|//svg|//video|//audio' ) as $node ) { $node->parentNode->removeChild( $node ); }
  foreach ( $xpath->query( '//@*' ) as $attribute ) {
   $name = strtolower( $attribute->nodeName );
   if ( 0 === strpos( $name, 'on' ) || in_array( $name, array( 'srcset', 'background', 'poster' ), true ) ) { $attribute->ownerElement->removeAttribute( $attribute->nodeName ); }
  }
  foreach ( $xpath->query( '//img' ) as $node ) {
   $source = $node->getAttribute( 'src' );$valid = false;
   if ( strlen( $source ) <= 1400000 && preg_match( '#^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$#D', $source, $match ) ) {
    $bytes = base64_decode( $match[2], true );$size = $bytes ? @getimagesizefromstring( $bytes ) : false;
    $valid = $size && $size[0] <= 3000 && $size[1] <= 3000 && $size['mime'] === 'image/' . $match[1];
   }
   if ( ! $valid ) { $node->parentNode->removeChild( $node ); }
  }
  // No CSS resource URLs or imports are needed: approved images are embedded img elements.
  foreach ( $xpath->query( '//style|//*[@style]' ) as $node ) {
   $css = 'style' === strtolower( $node->nodeName ) ? $node->textContent : $node->getAttribute( 'style' );
   if ( preg_match( '/url\s*\(|@import|\\\\/i', $css ) ) {
    if ( 'style' === strtolower( $node->nodeName ) ) { $node->textContent = ''; } else { $node->removeAttribute( 'style' ); }
   }
  }
  return $dom->saveHTML();
 }
 private static function remove_workspace( $directory ) {
  if ( ! is_dir( $directory ) ) { return; }
  foreach ( new DirectoryIterator( $directory ) as $entry ) {
   if ( $entry->isDot() ) { continue; }
   if ( $entry->isDir() && ! $entry->isLink() ) { self::remove_workspace( $entry->getPathname() ); } else { wp_delete_file( $entry->getPathname() ); }
  }
  rmdir( $directory ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Removes only this renderer's private random system-temp workspace in finally.
 }
}
