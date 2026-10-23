<?php
/** QIT regression boundaries: stored settings, block rendering and local attachment reads. */
define('ABSPATH', __DIR__);
define('ALKAMY_INVOICING_FILE', dirname(__DIR__) . '/modules/invoicing/invoicing.php');
define('IOEFWP_VERSION', 'test');
$root = sys_get_temp_dir() . '/alkamy-security-' . bin2hex(random_bytes(6));
mkdir($root . '/uploads', 0700, true);
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jT0YAAAAASUVORK5CYII=');
file_put_contents($root . '/uploads/logo.png', $png);
file_put_contents($root . '/outside.png', $png);
function wp_get_upload_dir(){return array('basedir'=>$GLOBALS['root'].'/uploads','error'=>false);}
function get_attached_file($id){return $GLOBALS['attachment'];}
function wp_getimagesize($file){return getimagesize($file);}
function wp_normalize_path($path){return str_replace('\\','/',$path);}
function absint($n){return abs((int)$n);}
function __($s,$domain=''){return $s;}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return esc_html($s);}
function esc_html__($s,$domain=''){return esc_html($s);}
function esc_url($s){return esc_attr($s);}
function sanitize_text_field($s){return trim(strip_tags((string)$s));}
function sanitize_textarea_field($s){return sanitize_text_field($s);}
function wp_strip_all_tags($s){return strip_tags((string)$s);}
function get_option($key,$default=array()){return $GLOBALS['options'][$key] ?? $default;}
function current_user_can($cap){return $GLOBALS['authorized'];}
function get_block_wrapper_attributes($extra){return 'class="'.esc_attr($extra['class']).'"';}
function wp_enqueue_style(...$args){}
function wp_enqueue_script(...$args){}
function plugins_url($path,$file){return 'https://example.test/'.$path;}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function wp_add_inline_script($handle,$script,$where){$GLOBALS['inline']=$script;}
function admin_url($path){return 'https://example.test/wp-admin/'.$path;}
function wp_nonce_field($action){}
function checked($a,$b,$echo=true){return $a===$b?'checked="checked"':'';}
class Alkamy_Documents { const TYPES=array('invoice','quotation','packing_slip','delivery_order','credit_note'); }
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-layout.php';
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-layout-editor.php';
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-invoice-settings.php';
$failures=0;
function security_check($ok,$message){echo ($ok?'PASS ':'FAIL ').$message."\n";if(!$ok){$GLOBALS['failures']++;}}
// Detect stat calls before validation without needing an actual malicious PHAR.
class Alkamy_Test_Stream {
 public $context;
 public function url_stat($path,$flags){$GLOBALS['stream_touches']++;return false;}
}
stream_wrapper_register('alkamytest','Alkamy_Test_Stream');
try {
 $attachment=$root.'/uploads/logo.png';
 security_check(Alkamy_Layout::raster(1)==='data:image/png;base64,'.base64_encode($png),'Valid local PNG bytes retained');
 $attachment=$root.'/outside.png';
 security_check(Alkamy_Layout::raster(1)==='data:image/png;base64,'.base64_encode($png),'Filtered local attachment outside uploads retained');
 symlink($root.'/outside.png',$root.'/uploads/escape.png');$attachment=$root.'/uploads/escape.png';
 security_check(Alkamy_Layout::raster(1)==='data:image/png;base64,'.base64_encode($png),'Local symlink attachment retained');
 $stream_touches=0;$attachment='alkamytest://payload.png';
 security_check(Alkamy_Layout::raster(1)===''&&$stream_touches===0,'Stream wrapper refused before first stat operation');
 foreach(array('phar://archive.phar/logo.png','PHAR://archive.phar/logo.png','file://'.$root.'/uploads/logo.png','https://example.test/logo.png','data:image/png;base64,'.base64_encode($png),"/tmp/logo\0.png",array('bad')) as $attachment){
  try{$safe=Alkamy_Layout::raster(1)==='';}catch(Throwable $e){$safe=false;}
  security_check($safe,'Non-local or malformed attachment refused');
 }
 $attachment=$root.'/uploads/logo.png';$authorized=true;$options=array();
 $attack='</script><script>alert("x")</script><img src=x onerror=alert(1)>';
 $options['alkamy_invoice_delivery']=array('enabled'=>true,'email_ids'=>array('customer_invoice'),'numbering_prefix'=>$attack,'number_padding'=>'" onfocus="alert(1)');
 ob_start();Alkamy_Invoice_Settings::render();$html=ob_get_clean();
 security_check(strpos($html,'<script>')===false&&strpos($html,'onfocus="alert')===false,'Stored settings cannot create executable HTML');
 security_check(stripos($inline,'</script')===false,'Inline settings cannot terminate the script element');
 $options['alkamy_invoice_delivery']=Alkamy_Invoice_Settings::clean(array('enabled'=>'1','email_ids'=>array('customer_invoice'),'numbering_prefix'=>'INV-{year}{month}-','number_padding'=>4));
 ob_start();Alkamy_Invoice_Settings::render();$html=ob_get_clean();
 security_check(strpos($html,'value="INV-{year}{month}-"')!==false&&strpos($html,'value="4"')!==false,'Valid saved invoice settings retained');
 $attrs=array('part'=>'paragraph','text'=>'Merchant text '.$attack,'style'=>array('color'=>array('text'=>'red; background:url(javascript:alert(1))')),'className'=>'" onmouseover="alert(1)','textAlign'=>'right');
 $html=Alkamy_Layout_Editor::render($attrs);
 security_check(strpos($html,'<script>')===false&&strpos($html,'<img src=x')===false&&strpos($html,'onmouseover=')===false&&strpos($html,'url(javascript:')===false,'Block attributes cannot inject markup or CSS');
 security_check(strpos($html,'Merchant text ')!==false&&strpos($html,'text-align:right')!==false,'Document text and supported alignment retained');
 $authorized=false;security_check(Alkamy_Layout_Editor::render($attrs)==='','Unauthorized block preview refused');
} finally {
 stream_wrapper_unregister('alkamytest');
 foreach(glob($root.'/uploads/*') as $file){unlink($file);}unlink($root.'/outside.png');rmdir($root.'/uploads');rmdir($root);
}
exit($failures?1:0);
