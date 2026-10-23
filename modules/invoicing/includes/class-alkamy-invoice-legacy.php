<?php
/** Explicit, non-destructive WPC PDF migration and authenticated link compatibility. */
defined('ABSPATH') || exit;
final class Alkamy_Invoice_Legacy {
 public static function register() {
  add_action('init',array(__CLASS__,'old_wpc_download'),1);
  add_action('admin_post_ampdf_download',array(__CLASS__,'old_custom_download'),1);
 }
 public static function has_document($id,$type){return 'invoice'===$type ? (bool)Alkamy_Invoice_Delivery::get_issued($id) : is_array(get_option(self::key($id,$type),null));}
 private static function key($id,$type){return 'alkamy_legacy_document_'.absint($id).'_'.$type;}
 public static function bytes($id,$type){
  if('invoice'===$type){return Alkamy_Invoice_Delivery::bytes($id);}
  $record=get_option(self::key($id,$type),array());$bytes=base64_decode($record['pdf']??'',true);
  if(!is_string($bytes)||0!==strpos($bytes,'%PDF-')||!hash_equals($record['sha256']??'',hash('sha256',$bytes))){throw new RuntimeException('The historical document is unavailable.');}
  return $bytes;
 }
 /** Dry run is default. Aborts on absent/corrupt originals; never regenerates historical PDFs. */
 public static function migrate($apply=false){
  if(!class_exists('WPCPI_Numbering')||!class_exists('WPCPI_PDF_Generator')){throw new RuntimeException('Keep WPC PDF active until migration is verified.');}
  global $wpdb;$table=$wpdb->prefix.'wpc_pi_invoice_numbers';
  // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Read-only import of the vendor ledger, which has no WordPress data-store abstraction.
  $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM %i ORDER BY id",$table),ARRAY_A);
  if(!is_array($rows) || !empty($wpdb->last_error)){throw new RuntimeException('The legacy invoice ledger could not be read.');}
  $plan=array();
  foreach($rows as $row){
   if('invoice'!==$row['doc_type']){throw new RuntimeException('Unknown legacy ledger document type.');}
   $order=wc_get_order($row['order_id']);if(!$order){throw new RuntimeException('A legacy invoice order is missing.');}
   $number=WPCPI_Numbering::instance()->format_number((int)$row['number'],$order->get_id(),'invoice');
   foreach(array('invoice','packing_slip') as $type){
    $doc='invoice'===$type?new WPCPI_Invoice($order):new WPCPI_Packing_Slip($order);$path=WPCPI_PDF_Generator::get_file_path($doc);
    if(!is_file($path)){if('invoice'===$type){throw new RuntimeException('A numbered legacy invoice has no archived PDF.');}continue;}
    $bytes=file_get_contents($path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents -- Trusted vendor-local archived PDF; never a URL or request path.
    if(!is_string($bytes)||0!==strpos($bytes,'%PDF-')||strlen($bytes)>10485760){throw new RuntimeException('An archived PDF is invalid or too large.');}
    $plan[]=array('order'=>$order,'number'=>$number,'type'=>$type,'bytes'=>$bytes,'sha256'=>hash('sha256',$bytes),'created'=>$row['created_at']);
   }
  }
  // Preflight every file before the first write. Records are immutable and reruns verify them.
  $result=array();foreach($plan as $entry){
   if($apply){
    if('invoice'===$entry['type']){Alkamy_Invoice_Delivery::import_legacy($entry['order'],$entry['number'],$entry['bytes'],'wpc',$entry['created']);}
    else{$key=self::key($entry['order']->get_id(),$entry['type']);$saved=get_option($key,null);if(null!==$saved && ($saved['sha256']??'')!==$entry['sha256']){throw new RuntimeException('Historical PDF archive conflict.');}if(null===$saved){Alkamy_Invoice_Delivery::atomic_add($key,array('pdf'=>base64_encode($entry['bytes']),'sha256'=>$entry['sha256'],'created'=>$entry['created'],'source'=>'wpc'));}}
    if(!hash_equals($entry['sha256'],hash('sha256',self::bytes($entry['order']->get_id(),$entry['type'])))){throw new RuntimeException('Historical PDF verification failed.');}
   }
   $result[]=array('order_id'=>$entry['order']->get_id(),'type'=>$entry['type'],'sha256'=>$entry['sha256'],'bytes'=>strlen($entry['bytes']),'verified'=>(bool)$apply);
  }
  return $result;
 }
 public static function old_wpc_download(){
  if(!isset($_GET['wpcpi_action'])){return;}
  $action=is_string($_GET['wpcpi_action'])?sanitize_key(wp_unslash($_GET['wpcpi_action'])):'';
  if(!in_array($action,array('download_invoice','print_invoice','download_packing_slip','print_packing_slip'),true)){return;}
  $id=isset($_GET['wpcpi_order_id'])&&is_scalar($_GET['wpcpi_order_id'])?absint($_GET['wpcpi_order_id']):0;$order=wc_get_order($id);
  $nonce=isset($_GET['wpcpi_nonce'])&&is_string($_GET['wpcpi_nonce'])?sanitize_text_field(wp_unslash($_GET['wpcpi_nonce'])):'';
  $template=isset($_GET['template'])&&is_string($_GET['template'])?sanitize_text_field(wp_unslash($_GET['template'])):'';
  $nonce_action='wpcpi_'.$action.'_'.$id.($template?'_'.$template:'');$type=false!==strpos($action,'packing_slip')?'packing_slip':'invoice';
  if(!Alkamy_Customer_Documents::allowed($order)||!wp_verify_nonce($nonce,$nonce_action)||('packing_slip'===$type && !(current_user_can('manage_woocommerce')&&current_user_can('edit_shop_order',$id)))){Alkamy_Customer_Documents::deny();return;}
  $vendor='wpc-pdf-invoices-premium/wpc-pdf-invoices.php';
  if(!self::has_document($id,$type) && (in_array($vendor,(array)get_option('active_plugins',array()),true)||array_key_exists($vendor,(array)get_site_option('active_sitewide_plugins',array())))){return;}
  try{$bytes=self::bytes($id,$type);}catch(Throwable $error){wp_die(esc_html__('Historical document unavailable. Please contact the store.','inline-order-editor-pro'),'',array('response'=>404));return;}
  Alkamy_Customer_Documents::stream($bytes,$type.'-'.$id.'.pdf',0===strpos($action,'print_'));
 }
 public static function old_custom_download(){
  $id=isset($_GET['order_id'])&&is_scalar($_GET['order_id'])?absint($_GET['order_id']):0;$type=isset($_GET['document'])&&is_string($_GET['document'])?sanitize_key(wp_unslash($_GET['document'])):'';$refund=isset($_GET['refund_id'])&&is_scalar($_GET['refund_id'])?absint($_GET['refund_id']):0;$mode=isset($_GET['mode'])&&is_string($_GET['mode'])?sanitize_key(wp_unslash($_GET['mode'])):'download';
  $nonce=isset($_GET['_wpnonce'])&&is_string($_GET['_wpnonce'])?sanitize_text_field(wp_unslash($_GET['_wpnonce'])):'';$order=wc_get_order($id);
  if(!$order||!is_user_logged_in()||!current_user_can('manage_woocommerce')||!current_user_can('edit_shop_order',$id)||!in_array($type,array('quotation','delivery_order','credit_note'),true)||!in_array($mode,array('download','view'),true)||!wp_verify_nonce($nonce,'ampdf_download_'.$type.'_'.$id.'_'.$refund.('view'===$mode?'_view':''))){Alkamy_Customer_Documents::deny();return;}
  try{$bytes=Alkamy_Local_Pdf::render(Alkamy_Local_Pdf::record($order,$type,$refund));}catch(Throwable $error){wp_die(esc_html__('This document is not available. Review the order before downloading.','inline-order-editor-pro'),'',array('response'=>400));return;}
  Alkamy_Customer_Documents::stream($bytes,$type.'-'.$id.'.pdf','view'===$mode);
 }
 /** Snapshot compatibility data; default native footer settings remain authoritative. */
 public static function snapshot_values($order,$type){
  $tags=array('section'=>array('class'=>true),'div'=>array('class'=>true),'h2'=>array(),'h3'=>array(),'h4'=>array(),'p'=>array('class'=>true),'strong'=>array(),'em'=>array(),'b'=>array(),'i'=>array(),'br'=>array(),'ul'=>array(),'ol'=>array(),'li'=>array());
  $mode=$order->get_meta('_ampdf_footer_mode',true);
  return array(
   'sectionsHtml'=>wp_kses(self::sections_html($order,$type),$tags),
   'productTermsHtml'=>wp_kses(self::product_terms_html($order,$type),$tags),
   'footerOverride'=>in_array($mode,array('custom','hidden'),true),
   'footerHtml'=>'custom'===$mode?wp_kses(self::footer_html($order,$type),$tags):'',
  );
 }
 /** Preserve original custom section HTML via the existing sanitized compatibility reader. */
 public static function sections_html($order,$type){return function_exists('ampdf_sections_html')?ampdf_sections_html($order,$type):'';}
 public static function product_terms_html($order,$type){return function_exists('ampdf_product_terms_html')?ampdf_product_terms_html($order->get_items(),$type):'';}
 public static function footer_html($order,$type){return function_exists('ampdf_footer_html')?ampdf_footer_html($order,$type):'';}
}
