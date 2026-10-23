<?php
// Asia Mannequin's existing rental metadata must remain compatible with its quote emails.
define('ABSPATH',__DIR__);
function __($s,$d=''){return $s;}
function esc_html($s){return htmlspecialchars($s,ENT_QUOTES);}
function esc_html__($s,$d=''){return esc_html($s);}
function sanitize_text_field($v){return trim(strip_tags((string)$v));}
function sanitize_textarea_field($v){return trim(strip_tags((string)$v));}
function wp_unslash($v){return $v;}
function current_user_can(...$v){return $GLOBALS['allowed']??true;}
function wp_verify_nonce($v,$action){return $v==='valid';}
function add_action(...$v){$GLOBALS['hooks'][]=$v;}
class Alkamy_Workspace{static function mode_enabled(){return $GLOBALS['mode']??true;}}
class AM_WPCRQ_Rental_Fields{}
class WC_Admin_Meta_Boxes{static function add_error($v){$GLOBALS['errors'][]=$v;}}
class RentalOrder{public $meta;function __construct($v){$this->meta=$v;}function get_id(){return 5;}function get_meta($key){return $this->meta; }function update_meta_data($k,$v){$this->meta=$v;}}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-rental-order-fields.php';
function check($v,$label){if(!$v)throw new Exception($label);echo "PASS $label\n";}
$base=['am_method'=>'collection','am_location'=>'Original','am_start_date'=>'2026-10-14','am_start_time'=>'09:01','am_return_date'=>'2026-10-16','am_return_time'=>'17:00','timezone'=>'Asia/Singapore','additional_info'=>'Note','future_key'=>['preserve']];
$input=['am_method'=>'delivery','am_location'=>'<b>Changed</b>','start'=>'2026-10-14T09:01','return'=>'2026-10-16T17:00','timezone'=>'Asia/Singapore','additional_info'=>"Line one\nLine two"];
$o=new RentalOrder($base);
$clean=Alkamy_Rental_Order_Fields::clean($base,$input);
check($clean['am_location']==='Changed'&&$clean['future_key']===['preserve'],'Sanitize edits and preserve unknown metadata');
check($clean['am_start_date']==='2026-10-14'&&$clean['am_start_time']==='09:01'&&$clean['additional_info']==="Line one\nLine two",'Retain source date/time schema and multiline notes');
foreach(['start'=>'2026-02-30T10:00','return'=>'2026-10-13T17:00','am_method'=>'bad','am_location'=>[]] as $key=>$value){try{Alkamy_Rental_Order_Fields::clean($base,array_replace($input,[$key=>$value]));throw new Exception('Invalid input accepted');}catch(InvalidArgumentException $e){check(true,"Reject invalid $key without mutation");}}
$_POST=['alkamy_rental_present'=>'1','_alkamy_rental_nonce'=>'bad','alkamy_rental'=>$input];Alkamy_Rental_Order_Fields::save($o);check($o->meta===$base,'Reject invalid nonce');
$_POST['_alkamy_rental_nonce']='valid';$GLOBALS['allowed']=false;Alkamy_Rental_Order_Fields::save($o);check($o->meta===$base,'Reject insufficient capability');$GLOBALS['allowed']=true;
$GLOBALS['mode']=false;Alkamy_Rental_Order_Fields::save($o);check($o->meta===$base,'Normal order mode unchanged');$GLOBALS['mode']=true;
Alkamy_Rental_Order_Fields::save($o);check($o->meta===$clean,'Authorized native order save edits existing metadata');
$empty=new RentalOrder('');Alkamy_Rental_Order_Fields::save($empty);check($empty->meta==='','Never invent rental metadata for unrelated orders');
$_POST['alkamy_rental']['start']='bad';$old=$o->meta;Alkamy_Rental_Order_Fields::save($o);check($o->meta===$old&&!empty($GLOBALS['errors']),'Validation error preserves whole previous rental record');

$blank=Alkamy_Rental_Order_Fields::clean($base,array_replace($input,['start'=>'','return'=>'']));check($blank['am_start_date']===''&&$blank['am_return_time']==='','Both requested times can be explicitly cleared');
function remove_action(...$v){$GLOBALS['removed'][]=$v;}
function wp_nonce_field(...$v){echo 'nonce';}
function woocommerce_wp_select($v){$GLOBALS['fields'][]=$v;}
function woocommerce_wp_text_input($v){$GLOBALS['fields'][]=$v;}
function woocommerce_wp_textarea_input($v){$GLOBALS['fields'][]=$v;}
function wp_timezone(){return new DateTimeZone('Asia/Singapore');}
$GLOBALS['mode']=false;ob_start();Alkamy_Rental_Order_Fields::display($o);$html=ob_get_clean();check($html===''&&empty($GLOBALS['removed']),'Plain mode preserves original display callback');
$GLOBALS['mode']=true;ob_start();Alkamy_Rental_Order_Fields::display($o);$html=ob_get_clean();check(count($GLOBALS['fields'])===5&&$GLOBALS['removed'][0][1]===['AM_WPCRQ_Rental_Fields','display'],'Invoicing mode replaces source admin display with five labeled Woo controls');
check($GLOBALS['fields'][2]['type']==='datetime-local'&&$GLOBALS['fields'][4]['rows']===4,'Requested time and multiline notes use appropriate controls');

function wc_get_order($id){return $GLOBALS['native_order'];}
class NativeRentalOrder extends RentalOrder{public $writes=0;function save_meta_data(){$this->writes++;}}
$GLOBALS['native_order']=new NativeRentalOrder($base);$_POST['alkamy_rental']=$input;
Alkamy_Rental_Order_Fields::process(5);check($GLOBALS['native_order']->writes===1&&$GLOBALS['native_order']->meta===$clean,'Native hook persists only existing rental metadata with CRUD');
$_POST['_alkamy_rental_nonce']='bad';Alkamy_Rental_Order_Fields::process(5);check($GLOBALS['native_order']->writes===1,'Denied native request does not persist metadata');
Alkamy_Rental_Order_Fields::init();check($GLOBALS['hooks'][1][0]==='woocommerce_process_shop_order_meta'&&$GLOBALS['hooks'][1][2]===40,'Register actual Woo native save hook after document workspace');

$store_time=Alkamy_Rental_Order_Fields::clean($base,array_replace($input,['timezone'=>'Invalid/Zone']));check($store_time['timezone']==='Asia/Singapore','Ignore posted time zone and use the store setting');
check(!in_array('alkamy_rental_timezone',array_column($GLOBALS['fields'],'id'),true),'No separate rental time zone field');
