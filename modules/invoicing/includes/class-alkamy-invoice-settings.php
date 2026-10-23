<?php
/** Merchant controls for local archived invoice delivery. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Invoice_Settings {
 public static function init() {
  add_action( 'admin_post_alkamy_invoice_delivery_settings', array( __CLASS__, 'save' ) );
 }
 public static function defaults() {
  return array( 'enabled'=>false, 'email_ids'=>array('customer_processing_order','customer_completed_order'), 'numbering_prefix'=>'{year}{month}-', 'number_padding'=>6 );
 }
 public static function email_labels() {
  return array('customer_processing_order'=>__('Processing order','inline-order-editor-pro'),'customer_completed_order'=>__('Completed order','inline-order-editor-pro'),'customer_invoice'=>__('Order details / customer invoice','inline-order-editor-pro'));
 }
 // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Plain validation messages are escaped by the settings notice at its sole UI boundary.
 public static function clean( $input ) {
  if(!is_array($input)){throw new InvalidArgumentException('Invalid invoice delivery settings.');}
  $prefix=$input['numbering_prefix'] ?? '{year}{month}-';
  if(!is_string($prefix) || strlen($prefix)>60 || preg_match('/[^A-Za-z0-9_{}\/-]/',$prefix)){throw new InvalidArgumentException('Use letters, digits, slash, dash, underscore and {year}/{month} in the invoice prefix.');}
  $unknown=preg_replace('/\{(?:year|month)\}/','',$prefix);
  if(false!==strpos($unknown,'{') || false!==strpos($unknown,'}')){throw new InvalidArgumentException('Supported invoice prefix tokens are {year} and {month}.');}
  // Monthly reset needs an unambiguous month in the full invoice number.
  if(false===strpos($prefix,'{year}') || false===strpos($prefix,'{month}')){throw new InvalidArgumentException('Include both {year} and {month} because invoice numbering restarts each month.');}
  $ids=$input['email_ids'] ?? array();if(!is_array($ids)){throw new InvalidArgumentException('Choose valid WooCommerce customer emails.');}
  $padding=filter_var($input['number_padding'] ?? 6,FILTER_VALIDATE_INT);
  if(false===$padding || $padding<2 || $padding>10){throw new InvalidArgumentException('Invoice number padding must be between 2 and 10 digits.');}
  if(!in_array($input['enabled'] ?? '0',array('0','1',0,1,false,true),true)){throw new InvalidArgumentException('Choose a valid invoice delivery state.');}
  return array('enabled'=>in_array($input['enabled'] ?? '0',array('1',1,true),true),'email_ids'=>array_values(array_intersect(array_keys(self::email_labels()),array_filter($ids,'is_string'))),'numbering_prefix'=>$prefix,'number_padding'=>$padding);
 }
 // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
 public static function render() {
  $values=array_merge(self::defaults(),(array)get_option('alkamy_invoice_delivery',array()));
  wp_enqueue_style('wp-components');
  wp_enqueue_script('alkamy-invoice-settings',plugins_url('assets/invoice-settings.js',ALKAMY_INVOICING_FILE),array('wp-element','wp-components','wp-i18n'),IOEFWP_VERSION,true);
  $config=array('values'=>$values,'emails'=>self::email_labels(),'labels'=>array('enabled'=>__('Attach PDF invoices automatically','inline-order-editor-pro'),'prefix'=>__('Invoice number prefix','inline-order-editor-pro'),'padding'=>__('Number of digits','inline-order-editor-pro')));
  wp_add_inline_script('alkamy-invoice-settings','window.alkamyInvoiceSettings=' . wp_json_encode($config,JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';','before');
  echo '<section class="alkamy-card" id="invoice-delivery"><h2>' . esc_html__('Invoice emails and customer downloads','inline-order-editor-pro') . '</h2><p>' . esc_html__('Attach one archived invoice to selected WooCommerce emails. Customers can download their issued invoice from My Account. Resends keep the same number and PDF; later order or layout edits do not alter it.','inline-order-editor-pro') . '</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="alkamy_invoice_delivery_settings"><input type="hidden" name="delivery[enabled]" value="0">';
  wp_nonce_field('alkamy_invoice_delivery_settings');
  echo '<div id="alkamy-invoice-delivery-controls"><label><input type="checkbox" name="delivery[enabled]" value="1" ' . checked(!empty($values['enabled']),true,false) . '> ' . esc_html__('Attach PDF invoices automatically','inline-order-editor-pro') . '</label>';
  // checked(..., false) returns only WordPress's fixed checkbox attribute; labels/values are escaped here.
  // nosemgrep: audit.php.wp.security.xss.unescaped-stored-option
  foreach(self::email_labels() as $id=>$label){echo '<label><input type="checkbox" name="delivery[email_ids][]" value="' . esc_attr($id) . '" ' . checked(in_array($id,$values['email_ids'],true),true,false) . '> ' . esc_html($label) . '</label>';}
  // Both persisted input values use esc_attr() at this HTML attribute boundary.
  // nosemgrep: audit.php.wp.security.xss.unescaped-stored-option
  echo '<label>' . esc_html__('Invoice number prefix','inline-order-editor-pro') . '<input name="delivery[numbering_prefix]" value="' . esc_attr($values['numbering_prefix']) . '" maxlength="60"></label><label>' . esc_html__('Number of digits','inline-order-editor-pro') . '<input type="number" name="delivery[number_padding]" min="2" max="10" value="' . esc_attr($values['number_padding']) . '"></label></div><p class="description">' . esc_html__('New invoices use a monthly sequence, for example 202610-000001. Include {year} and {month} in the prefix. Existing assigned and imported invoice numbers are preserved. Number settings apply only to future invoices.','inline-order-editor-pro') . '</p><p class="description">' . esc_html__('While WP Clever PDF Invoices is active, Alkamy skips automatic attachments to avoid duplicates. Delivery errors appear in WooCommerce logs under alkamy-invoice-delivery.','inline-order-editor-pro') . '</p><button class="button button-primary">' . esc_html__('Save invoice delivery','inline-order-editor-pro') . '</button></form></section>';
 }
 public static function save() {
  if(!current_user_can('manage_woocommerce')){wp_die(esc_html__('Access denied.','inline-order-editor-pro'),'',array('response'=>403));}
  check_admin_referer('alkamy_invoice_delivery_settings');
  try {
   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- clean validates scalar types, allowlists and bounds all settings.
   $clean=self::clean(wp_unslash($_POST['delivery'] ?? array()));
   update_option('alkamy_invoice_delivery',$clean,false);
   set_transient('alkamy_notice_' . get_current_user_id(),__('Invoice delivery settings saved.','inline-order-editor-pro'),60);
  } catch(InvalidArgumentException $error){set_transient('alkamy_notice_' . get_current_user_id(),$error->getMessage(),60);}
  wp_safe_redirect(Alkamy_Admin::url() . '#invoice-delivery');exit;
 }
}
