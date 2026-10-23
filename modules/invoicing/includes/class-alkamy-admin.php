<?php
/** Merchant document workspace, guarded actions, and scheduled reconciliation. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Admin {
 public static function init() {
  add_filter('ioefw_can_edit_order',static function($can,$order){return $can && !Alkamy_Invoice_Delivery::get_issued($order->get_id());},20,2);
  add_action( 'admin_menu', static function () { add_submenu_page( 'woocommerce', __( 'Alkamy Invoicing', 'inline-order-editor-pro' ), __( 'Invoicing', 'inline-order-editor-pro' ), 'manage_woocommerce', 'alkamy-invoicing', array( __CLASS__, 'page' ) ); } );
  add_action( 'admin_post_alkamy_connect_return', array( __CLASS__, 'connect_return' ) );
  add_action( 'admin_post_alkamy_action', array( __CLASS__, 'handle' ) );

  add_action( 'alkamy_sync_order', array( __CLASS__, 'sync_order' ) );
  add_filter( 'ioefw_can_edit_order', static function ( $can, $order ) { foreach ( Alkamy_Documents::records( $order ) as $record ) { if ( 'invoice' === $record['snapshot']['type'] && ! in_array( $record['status'], array( 'void', 'uncollectible' ), true ) ) { return false; } } return $can && ! $order->get_meta( '_alkamy_pending_invoice' ); }, 10, 2 );
 }
 public static function url( $id = 0 ) { return add_query_arg( array( 'page'=>'alkamy-invoicing', 'order_id'=>$id ), admin_url( 'admin.php' ) ); }
 private static function form( $action, $order_id = 0, $doc = '' ) {
  echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="alkamy_action"><input type="hidden" name="operation" value="' . esc_attr( $action ) . '"><input type="hidden" name="order_id" value="' . esc_attr( $order_id ) . '"><input type="hidden" name="document_id" value="' . esc_attr( $doc ) . '">';
  wp_nonce_field( 'alkamy_action' );
 }
 public static function page() {
  if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Access denied.', 'inline-order-editor-pro' ) ); }
  // Read-only navigation: capability and order permissions are checked below; no data is changed.
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A nonce is not required to view an authorised order.
  $id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
  $order = $id ? wc_get_order( $id ) : false;
  wp_enqueue_style( 'alkamy-admin', plugins_url( 'assets/admin.css', ALKAMY_INVOICING_FILE ), array(), '0.1.26' );
  wp_enqueue_script( 'alkamy-admin', plugins_url( 'assets/admin.js', ALKAMY_INVOICING_FILE ), array(), '0.1.26', true );
  echo '<div class="wrap alkamy"><h1>' . esc_html__( 'Alkamy Invoicing', 'inline-order-editor-pro' ) . '</h1><p>' . esc_html__( 'Invoices, quotations, packing slips and credit notes.', 'inline-order-editor-pro' ) . '</p>';
  $notice = get_transient( 'alkamy_notice_' . get_current_user_id() );
  if ( $notice ) { delete_transient( 'alkamy_notice_' . get_current_user_id() ); echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>'; }
  echo '<p><a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Connection and presets', 'inline-order-editor-pro' ) . '</a></p>';
  if ( $order && current_user_can( 'edit_shop_order', $id ) ) { self::order_page( $order ); }
  elseif ( $id ) { echo '<p>' . esc_html__( 'Order not found or inaccessible.', 'inline-order-editor-pro' ) . '</p>'; }
  else { self::settings_page(); }
  echo '</div>';
 }
 private static function settings_page() {
  Alkamy_Layout_Editor::settings();
  Alkamy_Invoice_Settings::render();
  echo '<section class="alkamy-card" id="alkamy-presets"><h2>' . esc_html__( 'Section and terms presets', 'inline-order-editor-pro' ) . '</h2>';self::form( 'presets' );self::section_editor( get_option( 'alkamy_presets', array() ), false );echo '<button class="button button-primary">' . esc_html__( 'Save presets', 'inline-order-editor-pro' ) . '</button></form></section>';
  if ( class_exists( 'Alkamy_Direct_Distribution', false ) ) { Alkamy_Direct_Distribution::render(); }
  if ( Alkamy_Workspace::singapore_store() ) { echo '<section class="alkamy-card"><h2>InvoiceNow</h2><p>' . esc_html__( 'Not connected. Access Point selection and onboarding are required before electronic invoice transmission.', 'inline-order-editor-pro' ) . '</p></section>'; }
  echo '<p>' . esc_html__( 'To create documents, open an order and enable Invoicing mode.', 'inline-order-editor-pro' ) . '</p>';
 }
 public static function section_editor( $rows, $allow_presets = true ) {
  echo '<p class="screen-reader-text alkamy-section-status" aria-live="polite"></p><div class="alkamy-sections">'; foreach ( $rows as $i=>$row ) { self::section_row( $i, $row, $allow_presets ); } echo '</div><template class="alkamy-row-template">';self::section_row( '__INDEX__', array( 'heading'=>'', 'body'=>'', 'documents'=>Alkamy_Documents::TYPES ), $allow_presets );echo '</template><p><button type="button" class="button alkamy-add">' . esc_html__( 'Add section', 'inline-order-editor-pro' ) . '</button></p>';

 }
 private static function section_row( $i, $row, $allow_presets = true ) {
  $is_terms = 'order_terms' === ( $row['kind'] ?? '' );
  if ( $allow_presets ) {
   $title = $row['heading'] ?: ( $is_terms ? __( 'Order terms', 'inline-order-editor-pro' ) : __( 'Additional information', 'inline-order-editor-pro' ) );
   echo '<details class="alkamy-section alkamy-section-accordion"' . ( 0 === $i ? ' open' : '' ) . '><summary><button type="button" class="alkamy-section-drag" aria-label="Reorder section. Drag, or use Up and Down arrow keys." title="Drag to reorder; keyboard: Up or Down">⠿</button><span class="alkamy-section-title">' . esc_html( $title ) . '</span></summary><div class="alkamy-section-content">';
   if ( $is_terms ) { echo '<input type="hidden" name="sections[' . esc_attr( $i ) . '][kind]" value="order_terms">'; }
   echo '<input type="hidden" name="sections[' . esc_attr( $i ) . '][position]" value="footer">';
   foreach ( $row['documents'] as $type ) { echo '<input type="hidden" name="sections[' . esc_attr( $i ) . '][documents][]" value="' . esc_attr( $type ) . '">'; }
   echo '<label>' . esc_html__( 'Heading', 'inline-order-editor-pro' ) . '<input data-field="heading" name="sections[' . esc_attr( $i ) . '][heading]" maxlength="120" placeholder="' . esc_attr( $title ) . '" value="' . esc_attr( $row['heading'] ) . '"></label><label>' . esc_html__( 'Content', 'inline-order-editor-pro' ) . '<textarea data-field="body" name="sections[' . esc_attr( $i ) . '][body]" maxlength="10000" rows="8">' . esc_textarea( $row['body'] ) . '</textarea></label><p class="description">' . esc_html__( 'Markdown: **bold**, *italic*, headings, lists and [links](https://example.com). Leave content blank to omit this section.', 'inline-order-editor-pro' ) . '</p>';
   if ( ! $is_terms ) { echo '<button type="button" class="button alkamy-remove">' . esc_html__( 'Remove section', 'inline-order-editor-pro' ) . '</button>'; }
   echo '</div></details>';
   return;
  }

  echo '<fieldset class="alkamy-section"><legend>' . esc_html( $is_terms ? __( 'Order terms', 'inline-order-editor-pro' ) : __( 'Document section', 'inline-order-editor-pro' ) ) . '</legend><button type="button" class="alkamy-section-drag" aria-label="Reorder section. Drag, or use Up and Down arrow keys." title="Drag to reorder; keyboard: Up or Down">⠿</button>';
  if ( $is_terms ) { echo '<input type="hidden" name="sections[' . esc_attr( $i ) . '][kind]" value="order_terms">'; }
  if ( $allow_presets ) {
   echo '<label>' . esc_html__( 'Preset', 'inline-order-editor-pro' ) . '<select class="alkamy-section-preset"><option value="">' . esc_html__( 'Choose a preset…', 'inline-order-editor-pro' ) . '</option>';
   foreach ( Alkamy_Documents::clean_sections( get_option( 'alkamy_presets', array() ) ) as $preset ) { echo '<option value="' . esc_attr( wp_json_encode( $preset ) ) . '">' . esc_html( $preset['heading'] ) . '</option>'; }
   echo '</select></label><a target="_blank" rel="noopener" href="' . esc_url( self::url() . '#alkamy-presets' ) . '">' . esc_html__( 'Edit presets ↗', 'inline-order-editor-pro' ) . '</a>';
  }
  echo '<label>' . esc_html__( 'Position in document', 'inline-order-editor-pro' ) . '<select data-field="position" name="sections[' . esc_attr( $i ) . '][position]">';
  foreach ( array( 'before_items'=>'Before item table', 'after_items'=>'After item table', 'footer'=>'Footer (end of document)' ) as $position=>$label ) { echo '<option value="' . esc_attr( $position ) . '" ' . selected( $row['position'] ?? 'after_items', $position, false ) . '>' . esc_html( $label ) . '</option>'; }
  echo '</select></label><div class="alkamy-position-map" aria-label="Section position preview"><span data-position="before_items">Before items</span><span class="alkamy-map-items">Item table</span><span data-position="after_items">After items</span><span class="alkamy-map-totals">Totals / payment</span><span data-position="footer">Footer</span></div><label>' . esc_html__( 'Heading' , 'inline-order-editor-pro' ) . '<input data-field="heading" name="sections[' . esc_attr( $i ) . '][heading]" maxlength="120" value="' . esc_attr( $row['heading'] ) . '"></label><label>' . esc_html__( 'Content', 'inline-order-editor-pro' ) . '<textarea data-field="body" name="sections[' . esc_attr( $i ) . '][body]" maxlength="10000" rows="4">' . esc_textarea( $row['body'] ) . '</textarea></label><div class="alkamy-visibility">';
  foreach ( Alkamy_Documents::TYPES as $type ) { echo '<label><input type="checkbox" name="sections[' . esc_attr( $i ) . '][documents][]" value="' . esc_attr( $type ) . '" ' . checked( in_array( $type, $row['documents'], true ), true, false ) . '> ' . esc_html( ucwords( str_replace( '_', ' ', $type ) ) ) . '</label>'; }
  echo '</div>';
  if ( ! $is_terms ) { echo ' <button type="button" class="button alkamy-remove">' . esc_html__( 'Remove section', 'inline-order-editor-pro' ) . '</button>'; } else { echo '<p class="description">' . esc_html__( 'Clear the content to omit order terms. Saved terms also appear in enabled WooCommerce emails and on the customer’s order page.', 'inline-order-editor-pro' ) . '</p>'; }
  echo '</fieldset>';
 }
 private static function order_page( $order ) { Alkamy_Workspace::page( $order ); }
 public static function connect_return() {
  if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied', '', array( 'response'=>403 ) ); }
  check_admin_referer( 'alkamy_connect_return' );
  try { Alkamy_Service::finish_connection( sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) ), sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) ) );$message = __( 'Stripe connected. Store credentials were saved automatically.', 'inline-order-editor-pro' ); }
  catch ( Throwable $error ) { nocache_headers();header( 'Referrer-Policy: no-referrer' );wp_die( esc_html( $error->getMessage() . ' Reload this return page to retry. Do not start another connection if authorization already completed.' ), 'Connection incomplete', array( 'response'=>400 ) ); }
  set_transient( 'alkamy_notice_' . get_current_user_id(), $message, 60 );
  nocache_headers();header( 'Referrer-Policy: no-referrer' );wp_safe_redirect( self::url() );exit;
 }
 /** Redirect only to the provider hosts validated for Stripe authorization. */
 public static function stripe_redirect( $url ) {
  $parts = is_string( $url ) ? wp_parse_url( $url ) : false;
  if ( ! is_array( $parts ) || preg_match( '/[\r\n]/', $url ) || 'https' !== ( $parts['scheme'] ?? '' ) || ! in_array( $parts['host'] ?? '', array( 'connect.stripe.com', 'connect-js.stripe.com' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ) { throw new RuntimeException( 'Invalid Stripe authorization URL.' ); }
  $host = $parts['host'];
  $allow = static function ( $hosts ) use ( $host ) { $hosts[] = $host;return array_unique( $hosts ); };
  add_filter( 'allowed_redirect_hosts', $allow );
  try { return wp_safe_redirect( $url ); }
  finally { remove_filter( 'allowed_redirect_hosts', $allow ); }
 }
 /** Prepare a read-only current-order document; never issue payments or persist draft edits. */
 public static function local_record( $order, $input ) {
  $order = clone $order;
  Alkamy_Order_Enhancements::preview_address( $order, map_deep( $input['alkamy_address'] ?? array(), 'sanitize_textarea_field' ) );
  if ( isset( $input['alkamy_shipping_same_billing'] ) ) { Alkamy_Order_Enhancements::shipping_choice( $order, '1' === sanitize_key( $input['alkamy_shipping_same_billing'] ) ); }
  if ( isset( $input['alkamy_fields_present'] ) ) { $order->update_meta_data( '_alkamy_custom_fields', Alkamy_Order_Enhancements::clean_fields( map_deep( $input['alkamy_custom_fields'] ?? array(), 'sanitize_text_field' ) ) ); }
  if(isset($input['alkamy_document_numbers'])){Alkamy_Document_Numbers::apply($order,$input['alkamy_document_numbers']);}
  $type = sanitize_key( $input['preview_type'] ?? ( $input['type'] ?? 'invoice' ) );
  $record = Alkamy_Local_Pdf::record( $order, $type, absint( $input['refund_id'] ?? 0 ) );
  if ( isset( $input['sections'] ) ) { $record['snapshot']['sections'] = Alkamy_Documents::normalize_order_sections( $order, map_deep( $input['sections'], 'sanitize_textarea_field' ) ); }
  foreach ( array( 'uen', 'peppol' ) as $key ) { if ( isset( $input[ 'buyer_' . $key ] ) ) { $record['snapshot']['customer'][ 'uen' === $key ? 'uen' : 'peppolId' ] = sanitize_text_field( $input[ 'buyer_' . $key ] ); } }
  if ( ! empty( $input['layout_id'] ) ) { $record['snapshot']['layout'] = Alkamy_Layout::preset( $type, absint( $input['layout_id'] ) ); }
  if ( in_array( $input['paper'] ?? '', array( 'a4', 'letter' ), true ) ) { $record['snapshot']['layout']['brand']['paper'] = $input['paper']; }
  if ( method_exists( 'Alkamy_Layout', 'validation_errors' ) ) { $missing = Alkamy_Layout::validation_errors( $type, $record['snapshot']['layout'] );if ( $missing ) { throw new RuntimeException( 'The selected PDF layout is missing required document areas: ' . implode( ', ', $missing ) . '.' ); } } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain exception is escaped once by the guarded preview/export catch at wp_die.
  return $record;
 }
 public static function handle() {
  if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Access denied', '', array( 'response'=>403 ) ); }
  check_admin_referer( 'alkamy_action' );
  $op = sanitize_key( $_POST['operation'] ?? '' ); $id = absint( $_POST['order_id'] ?? 0 ); $order = $id ? wc_get_order( $id ) : false;
  if ( $id && ( ! $order || ! current_user_can( 'edit_shop_order', $id ) ) ) { wp_die( 'Order access denied', '', array( 'response'=>403 ) ); }
  if ( $order && in_array($op,array('local_pdf','local_view_pdf'),true) ) {
   try {
    $posted=map_deep(wp_unslash($_POST['alkamy_document_numbers'] ?? array()),'sanitize_text_field');$saved=Alkamy_Document_Numbers::read($order);$number_type=sanitize_key(wp_unslash($_POST['type'] ?? 'invoice'));if('credit_note' === $number_type){$number_type='invoice';}
    if(isset($posted[$number_type]) && $posted[$number_type] !== ($saved[$number_type] ?? '')){throw new RuntimeException('Save assigned document numbers with Update order before downloading PDFs.');}
    $type=sanitize_key(wp_unslash($_POST['type'] ?? 'invoice'));
    $issued='invoice' === $type ? Alkamy_Invoice_Delivery::get_issued($id) : null;
    if($issued){$record=array('number'=>$issued['number']);$pdf=Alkamy_Invoice_Delivery::bytes($id);}
    elseif('packing_slip' === $type && Alkamy_Invoice_Legacy::has_document($id,$type)){$record=array('number'=>'packing-slip-' . $id);$pdf=Alkamy_Invoice_Legacy::bytes($id,$type);}
    else{$record = self::local_record( $order, wp_unslash( $_POST ) );$pdf = Alkamy_Local_Pdf::render( $record );}
    nocache_headers();header( 'Referrer-Policy: no-referrer' );header( 'X-Content-Type-Options: nosniff' );header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: ' . ('local_view_pdf' === $op ? 'inline' : 'attachment') . '; filename="' . sanitize_file_name( $record['number'] ) . '.pdf"' );
    echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Local renderer validates %PDF- binary bytes; escaping corrupts the document.
    exit;
   } catch ( Throwable $error ) { wp_die( esc_html( $error->getMessage() ), esc_html__( 'PDF unavailable', 'inline-order-editor-pro' ), array( 'response'=>400 ) ); }
  }
  if ( $order && ( isset( $_POST['preview'] ) || in_array( $op, array( 'preview_pdf', 'preview_email' ), true ) ) ) {
   try {
    $draft = isset( $_POST['preview'] );
    $format = $draft ? sanitize_key( $_POST['preview'] ) : ( 'preview_pdf' === $op ? 'pdf' : 'email' );
    if ( ! in_array( $format, array( 'layout', 'pdf', 'email' ), true ) ) { throw new RuntimeException( 'Unknown preview format.' ); }
    if ( $draft ) { $record = self::local_record( $order, wp_unslash( $_POST ) ); }
    else { $records = Alkamy_Documents::records( $order );$record = $records[ sanitize_text_field( wp_unslash( $_POST['document_id'] ?? '' ) ) ] ?? null;if ( ! $record ) { throw new RuntimeException( 'Document does not belong to this order.' ); } }
    nocache_headers();header( 'Referrer-Policy: no-referrer' );header( 'X-Content-Type-Options: nosniff' );
    if ( 'pdf' === $format ) {
     $pdf = $draft ? Alkamy_Local_Pdf::render( $record ) : Alkamy_Service::request( 'GET', '/v1/documents/' . rawurlencode( $record['id'] ) . '/pdf', null, true );
     header( 'Content-Type: application/pdf' );header( 'Content-Disposition: inline; filename="alkamy-preview.pdf"' );echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated %PDF- bytes from authenticated service; HTML escaping corrupts the PDF.
    } elseif ( 'layout' === $format ) {
     header( 'Content-Type: text/html; charset=UTF-8' );header( "Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'" );
     echo self::layout_preview( $record, sanitize_key( $_POST['paper'] ?? 'a4' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes every snapshot field and Markdown link; output is an HTML document with fixed CSS.
    } else {
     header( 'Content-Type: text/html; charset=UTF-8' );header( "Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'self'" );
     echo '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Email preview</title><style>body{font:16px/1.6 system-ui;max-width:760px;margin:12px auto;padding:12px;color:#153847}aside{background:#edf3ff;padding:20px}article{padding:16px;border:1px solid #ddd}</style></head><body><aside><strong>Email preview. Nothing has been sent.</strong><p>To: ' . esc_html( $record['snapshot']['customer']['email'] ) . '<br>Subject: ' . esc_html( $record['number'] ) . '<br>Attachment: PDF document</p>' . ( $draft ? '<p>The final document number and invoice payment link appear after issuance.</p>' : '' ) . '</aside><article>' . self::email_body( $record ) . '</article></body></html>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- email_body escapes snapshot text and validates Markdown URLs before returning HTML.
    }
    exit;
   } catch ( Throwable $error ) { wp_die( esc_html( $error->getMessage() ), 'Preview unavailable', array( 'response'=>400 ) ); }
  }
  $locked = false; $lock = 'alkamy_order_lock_' . $id;
  try {
   if ( $id ) { $locked = add_option( $lock, time(), '', false ); if ( ! $locked ) { throw new RuntimeException( __( 'An operation is already running for this order. Retry when it finishes. A stale lock needs administrator review.', 'inline-order-editor-pro' ) ); } }
   if ( 'start_connection' === $op ) { $redirect = Alkamy_Service::start_connection();self::stripe_redirect( $redirect );exit; }
   elseif ( 'activate_service' === $op ) { if ( ! current_user_can( 'manage_options' ) ) { throw new RuntimeException( 'Only a store administrator can connect the service.' ); }Alkamy_Service::activate( esc_url_raw( wp_unslash( $_POST['service_url'] ?? '' ) ), trim( sanitize_text_field( wp_unslash( $_POST['connection_code'] ?? '' ) ) ) ); }
   elseif ( 'connect' === $op ) { $result = Alkamy_Service::request( 'POST', '/v1/connect/onboard', array( 'email'=>sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ) ) );$host = wp_parse_url( $result['url'] ?? '', PHP_URL_HOST );if ( ! in_array( $host, array( 'connect.stripe.com', 'connect-js.stripe.com' ), true ) ) { throw new RuntimeException( 'Invalid onboarding URL.' ); }self::stripe_redirect( $result['url'] ?? '' );exit; }
   elseif ( 'identity' === $op ) { $values = array();foreach ( array( 'name', 'address', 'uen', 'gst', 'peppolId' ) as $key ) { $values[ $key ] = sanitize_text_field( wp_unslash( $_POST['identity'][ $key ] ?? '' ) ); }update_option( 'alkamy_identity', $values, false ); }
   elseif ( 'presets' === $op ) { update_option( 'alkamy_presets', Alkamy_Documents::clean_sections( map_deep( wp_unslash( $_POST['sections'] ?? array() ), 'sanitize_textarea_field' ) ), false ); }
   elseif ( $order && 'sections' === $op ) { if(isset($_POST['alkamy_document_numbers'])){Alkamy_Document_Numbers::apply($order,map_deep(wp_unslash($_POST['alkamy_document_numbers']),'sanitize_text_field'));}Alkamy_Documents::save_sections( $order, map_deep( wp_unslash( $_POST['sections'] ?? array() ), 'sanitize_textarea_field' ) );foreach ( array( 'uen', 'peppol' ) as $key ) { if ( isset( $_POST['buyer_' . $key ] ) ) { $order->update_meta_data( '_alkamy_buyer_' . $key, sanitize_text_field( wp_unslash( $_POST['buyer_' . $key ] ) ) ); } }Alkamy_Order_Enhancements::save_fields( $order );$order->save();Alkamy_Document_Numbers::auto_assign( $order ); }
   elseif ( $order && 'issue' === $op ) { if ( isset( $_POST['workspace'] ) ) { Alkamy_Order_Enhancements::preview_address( $order, map_deep( wp_unslash( $_POST['alkamy_address'] ?? array() ), 'sanitize_textarea_field' ) );Alkamy_Documents::save_sections( $order, map_deep( wp_unslash( $_POST['sections'] ?? array() ), 'sanitize_textarea_field' ) );foreach ( array( 'uen', 'peppol' ) as $key ) { if ( isset( $_POST['buyer_' . $key ] ) ) { $order->update_meta_data( '_alkamy_buyer_' . $key, sanitize_text_field( wp_unslash( $_POST['buyer_' . $key ] ) ) ); } }Alkamy_Order_Enhancements::save_fields( $order );$order->save();Alkamy_Document_Numbers::auto_assign( $order ); }Alkamy_Documents::issue( $order, sanitize_key( $_POST['type'] ?? '' ) );self::schedule( $id ); }
   elseif ( $order && in_array( $op, array( 'pdf', 'email', 'sync', 'credit' ), true ) ) {
    $doc_id = sanitize_text_field( wp_unslash( $_POST['document_id'] ?? '' ) );$records = Alkamy_Documents::records( $order );
    if ( ! isset( $records[ $doc_id ] ) ) { throw new RuntimeException( 'Document does not belong to this order.' ); }
    $path = '/v1/documents/' . rawurlencode( $doc_id ); $record = $records[ $doc_id ];
    if ( 'sync' === $op ) { self::schedule( $id );$record = Alkamy_Service::request( 'POST', $path . '/sync', array() );Alkamy_Documents::remember( $order, $record );Alkamy_Documents::apply_payment( $order, $record ); }
    elseif ( 'credit' === $op ) { $note = Alkamy_Service::request( 'POST', $path . '/credit', array( 'key'=>'credit-' . $doc_id ) );Alkamy_Documents::remember( $order, $note );self::schedule( $id );Alkamy_Documents::apply_payment( $order, $note );$record = Alkamy_Service::request( 'GET', $path );Alkamy_Documents::remember( $order, $record );self::schedule( $id ); }
    else { $pdf = Alkamy_Service::request( 'GET', $path . '/pdf', null, true );if ( 'pdf' === $op ) { delete_option( $lock );$locked = false;nocache_headers();header( 'Content-Type: application/pdf' );header( 'Content-Disposition: attachment; filename="alkamy-' . $doc_id . '.pdf"' );echo $pdf;exit; }self::email( $record, $pdf ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated %PDF- bytes from authenticated service; HTML escaping corrupts the PDF.
   } else { throw new RuntimeException( 'Unknown action.' ); }
   set_transient( 'alkamy_notice_' . get_current_user_id(), __( 'Saved. Document and payment status are shown below.', 'inline-order-editor-pro' ), 60 );
  } catch ( Throwable $error ) { set_transient( 'alkamy_notice_' . get_current_user_id(), $error->getMessage(), 60 ); }
  finally { if ( $locked ) { delete_option( $lock ); } }
  wp_safe_redirect( $order && ! empty( $_POST['return_to_order'] ) ? $order->get_edit_order_url() : self::url( $id ) );exit;
 }
 public static function email_body( $record, $layout = false ) {
  require_once __DIR__ . '/class-alkamy-markdown.php';
  $s = $record['snapshot'];$packing = in_array( $s['type'], array( 'packing_slip', 'delivery_order' ), true );
  $money = static function ( $amount ) use ( $s ) { return esc_html( $s['currency'] . ' ' . number_format( $amount / 100, 2, '.', ',' ) ); };
  $sections = static function ( $position ) use ( $s, $layout ) {
   $html = '';foreach ( $s['sections'] as $section ) { if ( '' !== trim( $section['body'] ) && in_array( $s['type'], $section['documents'], true ) && ( $section['position'] ?? 'after_items' ) === $position ) { $html .= ( $layout ? '<section class="section-box">' : '' ) . '<h3>' . esc_html( $section['heading'] ) . '</h3>' . Alkamy_Markdown::render( $section['body'] ) . ( $layout ? '</section>' : '' ); } }if ( '' === $html ) { return ''; }return $layout ? '<div class="section-zone"><small>' . esc_html( array( 'before_items'=>'Before item table', 'after_items'=>'After item table', 'footer'=>'After totals and payment' )[ $position ] ) . '</small>' . ( $html ?: '<p class="empty">No section here</p>' ) . '</div>' : $html;
  };
  $body = '<p>' . esc_html( $record['number'] ) . '</p>';
  foreach ( $s['customFields'] ?? array() as $field ) { $body .= '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $field['value'] ) . '</p>'; }
  $body .= $sections( 'before_items' ) . ( $layout ? '<section class="section-box items"><small>Items</small>' : '' ) . '<table style="width:100%;border-collapse:collapse;text-align:left"><thead><tr><th scope="col">' . esc_html__( 'Item', 'inline-order-editor-pro' ) . '</th><th scope="col">' . esc_html__( 'Quantity', 'inline-order-editor-pro' ) . '</th>';
  if ( ! $packing ) { foreach ( array( 'Net', 'Tax', 'Total' ) as $label ) { $body .= '<th scope="col">' . esc_html( $label ) . '</th>'; } }
  $body .= '</tr></thead><tbody>';
  foreach ( $s['lines'] as $line ) {
   $body .= '<tr><td>' . esc_html( $line['name'] ) . '</td><td>' . esc_html( $line['quantity'] ) . '</td>';
   if ( ! $packing ) { $body .= '<td>' . $money( $line['net'] ) . '</td><td>' . $money( $line['tax'] ) . '</td><td>' . $money( $line['net'] + $line['tax'] ) . '</td>'; }
   $body .= '</tr>';
  }
  $body .= '</tbody></table>' . ( $layout ? '</section>' : '' ) . $sections( 'after_items' );
  if ( ! $packing ) { $body .= ( $layout ? '<section class="section-box"><small>Totals</small>' : '' ) . '<p><strong>' . esc_html__( 'Total', 'inline-order-editor-pro' ) . ': ' . $money( $s['total'] ) . '</strong></p>' . ( $layout ? '</section>' : '' ); }
  if ( ! $packing && ! empty( $record['paymentUrl'] ) ) { $body .= '<p><a href="' . esc_url( $record['paymentUrl'] ) . '">' . esc_html__( 'Open secure payment page', 'inline-order-editor-pro' ) . '</a></p>'; }
  return $body . $sections( 'footer' );
 }
 public static function layout_preview( $record, $paper = 'a4' ) {
  if ( isset( $record['snapshot']['layout'] ) && class_exists( 'Alkamy_Layout' ) ) { return Alkamy_Layout::html( $record, $paper ); }
  $letter = 'letter' === $paper;$s = $record['snapshot'];$recipient = in_array( $s['type'], array( 'packing_slip', 'delivery_order' ), true ) && ! empty( $s['shipping']['address'] ) ? $s['shipping'] : $s['customer'];
  $header = '<section class="section-box"><small>Document header</small><h1>' . esc_html( ucwords( str_replace( '_', ' ', $s['type'] ) ) ) . '</h1><p>' . esc_html( $s['seller']['name'] ?? '' ) . '</p></section><section class="section-box"><small>Customer details</small><strong>' . esc_html( $recipient['name'] ?? '' ) . '</strong><p>' . esc_html( $recipient['address'] ?? '' ) . '</p></section>';
  return '<!doctype html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Document layout preview</title><style>*{box-sizing:border-box}body{margin:0;padding:14px;background:#e9edf2;color:#153847;font:12px/1.5 system-ui}.caption{font-size:11px;color:#526b78;margin:0 0 12px}.paper{background:white;width:794px;zoom:calc((100vw - 28px) / 794px);font-size:18px;aspect-ratio:' . ( $letter ? '8.5/11' : '210/297' ) . ';padding:48px;box-shadow:0 2px 10px #15384726;border:1px solid #d7dee6}h1{font-size:30px;margin:4px 0}h3{font-size:20px;margin:4px 0}p{margin:6px 0;overflow-wrap:anywhere}.section-box{border:1px solid #cbd5e1;border-radius:3px;padding:18px;margin:14px 0;overflow-wrap:anywhere}.section-zone{border:1px dashed #a9c3f7;padding:14px;margin:16px 0;background:#fafcff}.section-zone .section-box{background:white;margin-bottom:0}small{display:block;font-size:14px;text-transform:uppercase;letter-spacing:.06em;color:#526b78}.empty{color:#8995a1;font-size:15px}table{font-size:14px;table-layout:fixed}th,td{padding:5px 3px;border-bottom:1px solid #e5e7eb;overflow-wrap:anywhere}th:first-child{width:30%}a{color:#2563eb}</style></head><body><p class="caption">' . ( $letter ? 'US Letter' : 'A4' ) . ' layout guide · Boxes show section positions. PDF shows final pagination.</p><main class="paper">' . $header . self::email_body( $record, true ) . '</main></body></html>';
 }
 private static function email( $record, $pdf ) {
  $file = wp_tempnam( 'alkamy-' . $record['id'] . '.pdf' );
  if ( ! $file || false === file_put_contents( $file, $pdf ) ) { throw new RuntimeException( 'Could not prepare PDF attachment.' ); }
  try { $s = $record['snapshot'];$body = self::email_body( $record );if ( ! wp_mail( $s['customer']['email'], $record['number'], $body, array( 'Content-Type: text/html; charset=UTF-8' ), array( $file ) ) ) { throw new RuntimeException( 'Email was not accepted for sending. The document is still available.' ); } }
  finally { wp_delete_file( $file ); }
 }
 public static function schedule( $id ) { if ( function_exists( 'as_has_scheduled_action' ) && ! as_has_scheduled_action( 'alkamy_sync_order', array( $id ), 'alkamy' ) ) { as_schedule_single_action( time() + 60, 'alkamy_sync_order', array( $id ), 'alkamy' ); } }
 public static function sync_order( $id ) {
  $lock = 'alkamy_order_lock_' . $id;if ( ! add_option( $lock, time(), '', false ) ) { if ( function_exists( 'as_schedule_single_action' ) ) { as_schedule_single_action( time() + 300, 'alkamy_sync_order', array( $id ), 'alkamy' ); } return; }
  try { $order = wc_get_order( $id );if ( ! $order ) { return; }$again = false;
   foreach ( Alkamy_Documents::records( $order ) as $record ) {
    if ( empty( $record['stripeId'] ) || in_array( $record['status'], array( 'void', 'uncollectible' ), true ) ) { continue; }
    try { if ( 'invoice' === $record['snapshot']['type'] && 'paid' === $record['status'] ) { Alkamy_Documents::apply_payment( $order, $record ); continue; }
    if ( 'credit_note' === $record['snapshot']['type'] && in_array( $record['refundStatus'] ?? ( empty( $record['refundId'] ) ? 'none' : 'pending' ), array( 'none', 'succeeded', 'failed', 'canceled' ), true ) ) { Alkamy_Documents::apply_payment( $order, $record ); continue; }
    $record = Alkamy_Service::request( 'POST', '/v1/documents/' . rawurlencode( $record['id'] ) . '/sync', array() );Alkamy_Documents::remember( $order, $record );Alkamy_Documents::apply_payment( $order, $record );$again = $again || 'open' === $record['status'] || 'pending' === ( $record['refundStatus'] ?? '' ); }
    catch ( Throwable $error ) { $order->update_meta_data( '_alkamy_sync_error', $error->getMessage() );$order->save();$again = true; }
   }
   if ( $again && function_exists( 'as_schedule_single_action' ) ) { as_schedule_single_action( time() + 300, 'alkamy_sync_order', array( $id ), 'alkamy' ); }
  } finally { delete_option( $lock ); }
 }
}
