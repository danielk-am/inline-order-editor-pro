<?php
/** CLI boundary tests: malicious document strings, provider URLs, and notice messages. */
if ( 'cli' !== PHP_SAPI ) { exit; }
define('ABSPATH',__DIR__);define('ALKAMY_INVOICING_FILE',dirname(__DIR__).'/modules/invoicing/invoicing.php');
$hooks=array();$redirects=array();$transient='';
function __( $s,$domain='' ){return $s;}
function esc_html__($s,$domain=''){return esc_html($s);}
function esc_attr__($s,$domain=''){return esc_attr($s);}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return esc_html($s);}
function esc_url($s){return preg_match('/^https?:\/\//',$s)?htmlspecialchars($s,ENT_QUOTES,'UTF-8'):'';}
function wp_parse_url($s){return parse_url($s);}
function add_filter($hook,$cb,$p=10,$a=1){$GLOBALS['hooks'][$hook][]=$cb;}
function remove_filter($hook,$cb,$p=10){$GLOBALS['hooks'][$hook]=array_values(array_filter($GLOBALS['hooks'][$hook]??array(),function($v)use($cb){return $v!==$cb;}));}
function wp_safe_redirect($s){$hosts=array('store.test');foreach($GLOBALS['hooks']['allowed_redirect_hosts']??array() as $cb){$hosts=$cb($hosts,parse_url($s,PHP_URL_HOST));}if(!in_array(parse_url($s,PHP_URL_HOST),$hosts,true)){throw new RuntimeException('Unallowed host');}$GLOBALS['redirects'][]=$s;return true;}
function test_boundary($ok,$name){if(!$ok){fwrite(STDERR,'FAIL '.$name."\n");exit(1);}echo 'PASS '.$name."\n";}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-admin.php';
test_boundary(method_exists('Alkamy_Admin','stripe_redirect'),'Provider redirect validates through a dedicated boundary');
$unsafe=array('http://connect.stripe.com/path','https://evil.test/path','https://connect.stripe.com.evil.test/path','https://user@connect.stripe.com/path','https://connect.stripe.com:8443/path',"https://connect.stripe.com/path\r\nX-Test:yes");
foreach($unsafe as $url){$refused=false;try{Alkamy_Admin::stripe_redirect($url);}catch(RuntimeException $e){$refused=true;}test_boundary($refused,'Unsafe provider URL refused');}
foreach(array('https://connect.stripe.com/oauth/authorize?state=x','https://connect-js.stripe.com/path') as $url){Alkamy_Admin::stripe_redirect($url);test_boundary(end($redirects)===$url,'Validated HTTPS Stripe destination redirected');test_boundary(empty($hooks['allowed_redirect_hosts']),'Redirect allowlist removed after redirect');}
$attack='<script>alert(1)</script><img src=x onerror=alert(2)>';
$record=array('number'=>$attack,'snapshot'=>array('type'=>'invoice','currency'=>'SGD','seller'=>array('name'=>$attack),'customer'=>array('name'=>$attack,'address'=>$attack),'customFields'=>array(array('label'=>$attack,'value'=>$attack)),'lines'=>array(array('name'=>$attack,'quantity'=>1,'net'=>100,'tax'=>0)),'total'=>100,'sections'=>array(array('heading'=>$attack,'body'=>$attack.' [unsafe](javascript:alert(3))','documents'=>array('invoice'),'position'=>'footer'))));
$html=Alkamy_Admin::layout_preview($record);
test_boundary(false===strpos($html,'<script>')&&false===strpos($html,'<img src=x')&&false===strpos($html,'href="javascript:'),'Layout renderer escapes document strings and unsafe links');
test_boundary(false!==strpos($html,'&lt;script&gt;'),'Literal document text remains visible');
// Notice errors must remain plain data until their display boundary.
function current_user_can($cap){return true;}
function absint($s){return abs((int)$s);}
function wc_get_order($id){return false;}
function wp_enqueue_style(...$args){}
function wp_enqueue_script(...$args){}
function get_current_user_id(){return 1;}
function get_transient($key){return $GLOBALS['transient'];}
function delete_transient($key){}
function add_query_arg($values,$base){return $base.'?'.http_build_query($values);}
function admin_url($s){return 'https://store.test/wp-admin/'.$s;}
function plugins_url($s,$file){return 'https://store.test/plugins/'.$s;}
$_GET['order_id']=999;$transient=$attack;
ob_start();Alkamy_Admin::page();$notice=ob_get_clean();
test_boundary(false===strpos($notice,'<script>')&&false!==strpos($notice,'&lt;script&gt;'),'Stored exception notice escapes exactly at display');
// PDF transport must preserve validated binary bytes and reject an HTML service response.
function get_option($key,$default=array()){return array('url'=>'https://service.test','id'=>'shop_test','secret'=>str_repeat('x',32));}
function wp_json_encode($value){return json_encode($value);}
function wp_generate_uuid4(){return '00000000-0000-4000-8000-000000000000';}
function wp_remote_request($url,$args){return array('body'=>$GLOBALS['pdf_transport_body'],'status'=>200);}
function is_wp_error($value){return false;}
function wp_remote_retrieve_body($value){return $value['body'];}
function wp_remote_retrieve_response_code($value){return $value['status'];}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-service.php';
$pdf_transport_body="%PDF-1.7\n\0<&binary>";
test_boundary($pdf_transport_body===Alkamy_Service::request('GET','/v1/documents/test/pdf',null,true),'Validated PDF bytes preserved rather than HTML escaped');
$pdf_transport_body='<html><script>alert(1)</script></html>';$rejected=false;
try{Alkamy_Service::request('GET','/v1/documents/test/pdf',null,true);}catch(RuntimeException $e){$rejected=true;}
test_boundary($rejected,'HTML service response cannot pass PDF validation');
