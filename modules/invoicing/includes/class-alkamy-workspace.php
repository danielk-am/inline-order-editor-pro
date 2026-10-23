<?php
/** Shared document workspace for the Invoicing page and both Woo order editors. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Workspace {
 public static function mode_enabled() { return '1' === get_user_meta( get_current_user_id(), '_alkamy_invoice_mode', true ); }
 public static function init() {
  add_filter( 'ioefw_order_terms', static function ( $terms ) { require_once __DIR__ . '/class-alkamy-markdown.php';return Alkamy_Markdown::plain( $terms ); } );
  add_action( 'ioefw_terms_box_actions', array( __CLASS__, 'terms_presets' ) );
  add_action( 'wp_ajax_alkamy_invoice_mode', static function () {
   check_ajax_referer( 'alkamy_invoice_mode', 'nonce' );
   if ( ! current_user_can( 'edit_shop_orders' ) ) { wp_send_json_error( array( 'message'=>'You cannot edit orders.' ), 403 ); }
   $enabled = '1' === sanitize_key( wp_unslash( $_POST['enabled'] ?? '' ) );
   update_user_meta( get_current_user_id(), '_alkamy_invoice_mode', $enabled ? '1' : '0' );
   wp_send_json_success( array( 'enabled'=>$enabled ) );
  } );
  add_action( 'admin_notices', static function () { if ( self::is_order_screen() ) { $key = 'alkamy_notice_' . get_current_user_id();$notice = get_transient( $key );if ( $notice ) { delete_transient( $key );echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>'; } } } );
  add_action( 'add_meta_boxes', array( __CLASS__, 'register' ), 100 );
  add_action( 'ioefw_terms_saved', array( 'Alkamy_Documents', 'native_terms_saved' ), 10, 2 );
  add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save' ), 30 );
  add_action( 'admin_enqueue_scripts', static function () {
   if ( self::is_order_screen() ) { self::assets(); }
  } );
  add_filter( 'admin_body_class', static function ( $classes ) { return self::is_order_screen() && self::mode_enabled() ? $classes . ' alkamy-order-screen' : $classes; } );
 }
 public static function terms_presets() {
  echo '<div class="alkamy-terms-presets"><label for="alkamy-terms-preset">' . esc_html__( 'Order terms preset', 'inline-order-editor-pro' ) . '</label> <select id="alkamy-terms-preset"><option value="">' . esc_html__( 'Choose a preset…', 'inline-order-editor-pro' ) . '</option>';
  foreach ( Alkamy_Documents::clean_sections( get_option( 'alkamy_presets', array() ) ) as $row ) {
   echo '<option value="' . esc_attr( $row['body'] ) . '">' . esc_html( $row['heading'] ) . '</option>';
  }
  echo '</select> <a href="' . esc_url( Alkamy_Admin::url() . '#alkamy-presets' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Edit preset terms ↗', 'inline-order-editor-pro' ) . '</a><p class="description">' . esc_html__( 'Choose reusable text, then edit this order’s copy below. Changing a preset does not change saved orders.', 'inline-order-editor-pro' ) . '</p></div>';
 }
 private static function is_order_screen() {
  $screen = get_current_screen();
  return $screen && in_array( $screen->id, array( 'shop_order', wc_get_page_screen_id( 'shop-order' ) ), true ) && ! in_array( $screen->base, array( 'edit' ), true );
 }
 public static function assets() {
  wp_enqueue_style('wp-components');
  wp_enqueue_style( 'alkamy-admin', plugins_url( 'assets/admin.css', ALKAMY_INVOICING_FILE ), array(), '0.1.30' );
  wp_enqueue_script( 'alkamy-admin', plugins_url( 'assets/admin.js', ALKAMY_INVOICING_FILE ), array('wp-element','wp-components','wp-i18n'), '0.1.30', true );
 }
 public static function register() {
  if ( ! self::mode_enabled() ) { return; }
  foreach ( array( 'shop_order', wc_get_page_screen_id( 'shop-order' ) ) as $screen ) {
   add_meta_box( 'alkamy-sections', __( 'Document sections', 'inline-order-editor-pro' ), array( __CLASS__, 'editor_box' ), $screen, 'normal', 'high' );
   remove_meta_box( 'ioefw-order-terms', $screen, 'normal' );
   add_meta_box( 'alkamy-export', __( 'Documents and exports', 'inline-order-editor-pro' ), array( __CLASS__, 'actions_box' ), $screen, 'side', 'high' );
  }
 }
 private static function order( $object ) { return $object instanceof WC_Order ? $object : wc_get_order( $object->ID ?? 0 ); }
 public static function editor_box( $object ) { $order = self::order( $object );if ( $order ) { echo '<div class="alkamy-section-workspace">';self::editor( $order, true );self::preview( $order );echo '</div>'; } }
 public static function preview_box( $object ) { $order = self::order( $object );if ( $order ) { self::preview( $order ); } }
 public static function actions_box( $object ) { $order = self::order( $object );if ( $order ) { self::actions( $order ); } }
 public static function page( $order ) {
  self::assets();
  /* translators: %s: WooCommerce order number. */
  echo '<h2>' . esc_html( sprintf( __( 'Order %s', 'inline-order-editor-pro' ), $order->get_order_number() ) ) . '</h2><p><a href="' . esc_url( $order->get_edit_order_url() ) . '">' . esc_html__( 'Edit order details and items', 'inline-order-editor-pro' ) . '</a></p><div class="alkamy-columns"><section class="alkamy-card"><h2>' . esc_html__( 'Document sections', 'inline-order-editor-pro' ) . '</h2>';
  Alkamy_Order_Enhancements::fields( $order );self::editor( $order, false );
  echo '</section><aside class="alkamy-right"><section class="alkamy-card"><h2>' . esc_html__( 'Document preview', 'inline-order-editor-pro' ) . '</h2>';
  self::preview( $order );
  echo '</section><section class="alkamy-card"><h2>' . esc_html__( 'Documents and exports', 'inline-order-editor-pro' ) . '</h2>';
  self::actions( $order );echo '</section></aside></div>';
 }
 public static function singapore_store() {
  $location = wc_get_base_location();
  return 'SG' === ( $location['country'] ?? '' );
 }
 public static function identifiers( $order ) {
  if ( ! self::singapore_store() ) { return; }
  echo '<details class="alkamy-invoicenow-details"><summary>' . esc_html__( 'InvoiceNow customer identifiers (optional)', 'inline-order-editor-pro' ) . '</summary><p>' . esc_html__( 'UEN identifies the customer’s registered business. Peppol ID is their electronic invoicing address. These fields do not connect or send invoices to InvoiceNow.', 'inline-order-editor-pro' ) . '</p>';
  foreach ( array( 'uen'=>'Buyer UEN', 'peppol'=>'Buyer Peppol ID' ) as $key=>$label ) { echo '<label>' . esc_html( $label ) . '<input name="buyer_' . esc_attr( $key ) . '" value="' . esc_attr( $order->get_meta( '_alkamy_buyer_' . $key ) ) . '"></label>'; }
  echo '</details>';
 }
 public static function editor( $order, $embedded ) {
  echo '<div class="alkamy alkamy-editor" data-embedded="' . ( $embedded ? '1' : '0' ) . '" data-order="' . esc_attr( $order->get_id() ) . '">';
  wp_nonce_field( 'alkamy_order_sections', '_alkamy_sections_nonce', false );
  $numbers=Alkamy_Document_Numbers::read($order);
  echo '<fieldset class="alkamy-document-numbers"><legend>' . esc_html__('Assigned document numbers','inline-order-editor-pro') . '</legend>';
  foreach(Alkamy_Document_Numbers::labels() as $type=>$label){
   /* translators: %s: document type, such as Invoice or Quotation. */
   echo '<label>' . esc_html(sprintf(__('%s number','inline-order-editor-pro'),$label)) . '<input type="text" maxlength="100" name="alkamy_document_numbers[' . esc_attr($type) . ']" value="' . esc_attr($numbers[$type]) . '" placeholder="' . esc_attr__('Assigned on Update order','inline-order-editor-pro') . '"></label>';
  }
  echo '<p class="description">' . esc_html__('Missing numbers are assigned automatically with Update order. Existing numbers are preserved. Invoice numbering follows Invoice emails settings; quotation, delivery order and packing slip have separate monthly sequences. The WooCommerce order number remains a separate reference.','inline-order-editor-pro') . '</p></fieldset>';
  if(Alkamy_Invoice_Delivery::get_issued($order->get_id())){echo '<p class="notice notice-info">' . esc_html__('This order has an archived invoice. Its number and PDF are fixed; invoice downloads use the archived copy. Other document previews reflect current order details.','inline-order-editor-pro') . '</p>';}
  if($order->get_meta('_alkamy_invoice_delivery_error')){echo '<p class="notice notice-error">' . esc_html__('The last invoice attachment failed. Review WooCommerce logs for alkamy-invoice-delivery, correct the invoice issue and resend the customer email.','inline-order-editor-pro') . '</p>';}


  echo '<p>' . esc_html__( 'The default layout places sections after totals and payment. Drag their handles to reorder, and open a section to edit its heading and content. Custom templates control the section area’s position.', 'inline-order-editor-pro' ) . '</p>';
  // Prefix fields on the core order form to avoid collisions with other plugins.
  ob_start();Alkamy_Admin::section_editor( Alkamy_Documents::editor_sections( $order ) );$editor = ob_get_clean();echo str_replace( 'name="sections[', 'name="alkamy_sections[', $editor ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered section_editor escapes all text/attribute values; replacement only prefixes fixed input names.
  echo '<p>' . esc_html__( 'The preview reflects unsaved section changes. Order items must be saved before they appear in the preview. PDF exports use the selected layout. Previously issued service documents keep their original content.', 'inline-order-editor-pro' ) . '</p>';
  if ( $embedded ) { echo '<p>' . esc_html__( 'Use Update order to save these sections with the order.', 'inline-order-editor-pro' ) . '</p>'; }
  else { echo '<button type="button" class="button button-primary" data-alkamy-action="sections">' . esc_html__( 'Save sections', 'inline-order-editor-pro' ) . '</button>'; }
  echo '</div>';
 }
 public static function save( $id ) {
  if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $id ) || ! isset( $_POST['_alkamy_sections_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_alkamy_sections_nonce'] ) ), 'alkamy_order_sections' ) ) { return; }
  $order = wc_get_order( $id );if ( ! $order ) { return; }
  try{if(isset($_POST['alkamy_document_numbers'])){Alkamy_Document_Numbers::apply($order,map_deep(wp_unslash($_POST['alkamy_document_numbers']),'sanitize_text_field'));}}catch(InvalidArgumentException $e){set_transient('alkamy_notice_' . get_current_user_id(),$e->getMessage(),60);return;}
  Alkamy_Documents::save_sections( $order, map_deep( wp_unslash( $_POST['alkamy_sections'] ?? array() ), 'sanitize_textarea_field' ) );
  foreach ( array( 'uen', 'peppol' ) as $key ) { if ( isset( $_POST['buyer_' . $key ] ) ) { $order->update_meta_data( '_alkamy_buyer_' . $key, sanitize_text_field( wp_unslash( $_POST['buyer_' . $key ] ) ) ); } }
  Alkamy_Order_Enhancements::save_fields( $order );$order->save();
  try { Alkamy_Document_Numbers::auto_assign( $order ); } catch ( RuntimeException $error ) { set_transient( 'alkamy_notice_' . get_current_user_id(), $error->getMessage(), 60 ); }
 }
 public static function labels() { return array( 'invoice'=>__( 'Invoice', 'inline-order-editor-pro' ), 'quotation'=>__( 'Quotation', 'inline-order-editor-pro' ), 'packing_slip'=>__( 'Packing slip', 'inline-order-editor-pro' ), 'delivery_order'=>__( 'Delivery order', 'inline-order-editor-pro' ) ); }
 public static function preview( $order ) {
  $service_ready = Alkamy_Local_Pdf::available();
  $brand = function_exists( 'get_option' ) ? get_option( 'alkamy_document_brand', array() ) : array();$paper = 'letter' === ( $brand['paper'] ?? '' ) ? 'letter' : 'a4';
  $name = 'alkamy_preview_' . $order->get_id();
  echo '<div class="alkamy alkamy-preview" data-endpoint="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'alkamy_action' ) ) . '" data-order="' . esc_attr( $order->get_id() ) . '"><div class="alkamy-preview-heading">' . esc_html__('Preview options','inline-order-editor-pro') . '</div><div class="alkamy-preview-controls"><label>' . esc_html__( 'Preview document', 'inline-order-editor-pro' ) . '<select class="alkamy-preview-type">';
  foreach ( self::labels() as $type=>$label ) { echo '<option value="' . esc_attr( $type ) . '">' . esc_html( $label ) . '</option>'; }
  echo '</select></label>';
  if ( function_exists( 'get_posts' ) ) {
   echo '<label>' . esc_html__( 'PDF layout', 'inline-order-editor-pro' ) . '<select class="alkamy-preview-layout"><option value="0">' . esc_html__( 'Default for this document', 'inline-order-editor-pro' ) . '</option>';
   foreach ( get_posts( array( 'post_type'=>'alkamy_pdf_layout', 'post_status'=>'publish', 'numberposts'=>100, 'orderby'=>'title', 'order'=>'ASC' ) ) as $template ) { echo '<option value="' . esc_attr( $template->ID ) . '">' . esc_html( $template->post_title ) . '</option>'; }
   echo '</select></label>';
  }
  echo '<label>' . esc_html__( 'Preview format', 'inline-order-editor-pro' ) . '<select class="alkamy-preview-format"><option value="layout">Layout</option><option value="pdf"' . ( $service_ready ? '' : ' disabled' ) . '>PDF</option></select></label><label class="alkamy-paper-control">Layout paper<select class="alkamy-preview-paper"><option value="a4"' . ( 'a4' === $paper ? ' selected' : '' ) . '>A4</option><option value="letter"' . ( 'letter' === $paper ? ' selected' : '' ) . '>US Letter</option></select></label></div><button type="button" class="button" data-alkamy-action="preview">' . esc_html__( 'Refresh preview', 'inline-order-editor-pro' ) . '</button><p class="alkamy-preview-status" role="status">' . esc_html__( 'Draft preview. Nothing is issued or sent.', 'inline-order-editor-pro' ) . '</p><iframe name="' . esc_attr( $name ) . '" title="' . esc_attr__( 'Document preview', 'inline-order-editor-pro' ) . '" class="alkamy-preview-frame" src="about:blank"></iframe><noscript>' . esc_html__( 'Enable JavaScript to use document previews and export controls.', 'inline-order-editor-pro' ) . '</noscript></div>';
 }
 private static function export_row($type,$label,$ready,$refund=0) {
  echo '<div class="alkamy-document-export-row"><strong>' . esc_html($label) . '</strong><div class="alkamy-document-export-buttons">';
  foreach(array('local_view_pdf'=>__('Preview','inline-order-editor-pro'),'local_pdf'=>__('Download','inline-order-editor-pro')) as $action=>$text){
   /* translators: 1: preview or download, 2: document type. */
   $name=sprintf(__('%1$s %2$s PDF','inline-order-editor-pro'),$text,$label);
   echo '<button type="button" class="button" data-alkamy-action="' . esc_attr($action) . '" data-type="' . esc_attr($type) . '" data-refund="' . esc_attr($refund) . '" aria-label="' . esc_attr($name) . '"' . ($ready ? '' : ' disabled') . '>' . esc_html($text) . '</button>';
  }
  echo '</div></div>';
 }
 public static function actions( $order ) {
  $service_ready = Alkamy_Service::configured();
  $pdf_ready = Alkamy_Local_Pdf::available();
  if ( ! $pdf_ready ) { echo '<p class="description">' . esc_html__( 'Local PDF renderer is unavailable. Please reinstall the complete Pro package.', 'inline-order-editor-pro' ) . '</p>'; }
  echo '<div class="alkamy alkamy-exports"><p>' . esc_html__( 'Download PDFs from saved order items and current address/section edits. Save item changes before downloading.', 'inline-order-editor-pro' ) . '</p><div class="alkamy-actions">';
  /* translators: %s: Document type, such as Invoice or Packing slip. */
  foreach ( self::labels() as $type=>$label ) { self::export_row($type,$label,$pdf_ready); }
  echo '</div>';
  $refunds = method_exists( $order, 'get_refunds' ) ? $order->get_refunds() : array();
  if ( $refunds ) {
   echo '<h3>' . esc_html__( 'Refund credit notes', 'inline-order-editor-pro' ) . '</h3><p>' . esc_html__( 'Each credit note represents an existing WooCommerce refund.', 'inline-order-editor-pro' ) . '</p><div class="alkamy-actions">';
   foreach ( $refunds as $refund ) {
    /* translators: %s: Existing WooCommerce refund ID. */
    self::export_row('credit_note',sprintf(__('Credit note for refund #%s','inline-order-editor-pro'),$refund->get_id()),$pdf_ready,$refund->get_id());
   }
   echo '</div>';
  }
  $records = Alkamy_Documents::records( $order );
  if ( $records ) { echo '<h3>' . esc_html__( 'Previously issued service documents', 'inline-order-editor-pro' ) . '</h3>'; }
  foreach ( $records as $doc ) {
   echo '<section class="alkamy-issued"><h3>' . esc_html( $doc['number'] ?? __( 'Issuing document', 'inline-order-editor-pro' ) ) . '</h3><p>' . esc_html( ucfirst( $doc['status'] ) . ' · ' . $doc['created'] ) . '</p><div class="alkamy-actions">';
   $actions = array( 'preview_pdf'=>'View PDF', 'pdf'=>'Download PDF' );
   if ( ! empty( $doc['stripeId'] ) ) { $actions['sync'] = 'Refresh status'; }
   if ( 'invoice' === $doc['snapshot']['type'] && empty( $doc['creditId'] ) && in_array( $doc['status'], array( 'open', 'paid' ), true ) ) { $actions['credit'] = 'Create full credit note'; }
   foreach ( $actions as $op=>$label ) { echo '<button type="button" class="button" data-alkamy-action="' . esc_attr( $op ) . '" data-document="' . esc_attr( $doc['id'] ) . '"' . ( $service_ready ? '' : ' disabled' ) . '>' . esc_html( $label ) . '</button>'; }
   echo '</div>';
   if ( ! empty( $doc['paymentUrl'] ) ) { echo '<p><a target="_blank" rel="noopener" href="' . esc_url( $doc['paymentUrl'] ) . '">' . esc_html__( 'View payment page', 'inline-order-editor-pro' ) . '</a></p>'; }
   if ( isset( $doc['refundStatus'] ) ) { echo '<p>' . esc_html( 'Refund: ' . $doc['refundStatus'] ) . '</p>'; }
   echo '</section>';
  }
  echo '</div>';
 }
}
