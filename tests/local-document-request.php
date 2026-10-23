<?php
// protects read-only request preparation; fails when draft fields are persisted or service issuance is used.
define('ABSPATH',__DIR__);
function sanitize_text_field($s){return trim(strip_tags((string)$s));}function sanitize_textarea_field($s){return sanitize_text_field($s);}function sanitize_key($s){return preg_replace('/[^a-z0-9_]/','',$s);}function absint($n){return abs((int)$n);}function map_deep($a,$cb){return is_array($a)?array_map(function($v)use($cb){return map_deep($v,$cb);},$a):$cb($a);}
class RequestOrder {public $name='Saved customer';public $fields=[];public function get_meta($key){return '';}public function update_meta_data($k,$v){$this->fields=$v;}}
class Alkamy_Order_Enhancements {public static function preview_address($o,$a){$o->name=$a['_billing_first_name']??$o->name;}public static function clean_fields($v){return $v;}}
class Alkamy_Documents {public static function normalize_order_sections($o,$rows){return $rows;}}
class Alkamy_Layout {public static function preset($type,$id=0){return ['brand'=>['paper'=>'a4'],'blocks'=>[['part'=>'items']]];}}
class Alkamy_Local_Pdf {public static function record($o,$type,$refund=0){return ['number'=>'DRAFT','snapshot'=>['type'=>$type,'customer'=>['name'=>$o->name],'customFields'=>$o->fields,'layout'=>Alkamy_Layout::preset($type)]];}}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-admin.php';
if(!method_exists('Alkamy_Admin','local_record')){fwrite(STDERR,"FAIL read-only local request path absent\n");exit(1);}
$o=new RequestOrder();$r=Alkamy_Admin::local_record($o,['type'=>'quotation','alkamy_address'=>['_billing_first_name'=>'Draft customer'],'sections'=>[['heading'=>'Draft heading','body'=>'Draft content']],'alkamy_fields_present'=>'1','alkamy_custom_fields'=>[['label'=>'Ref','value'=>'Draft ref']],'buyer_uen'=>'<b>123</b>','paper'=>'letter']);
if($o->name!=='Saved customer'||$o->fields!==[])throw new Exception('Original order modified');
if($r['snapshot']['customer']['name']!=='Draft customer'||$r['snapshot']['customer']['uen']!=='123'||$r['snapshot']['sections'][0]['heading']!=='Draft heading'||$r['snapshot']['layout']['brand']['paper']!=='letter')throw new Exception('Unsaved request fields not reflected');
echo "PASS local request reflects draft fields without modifying order or issuing payments\n";
