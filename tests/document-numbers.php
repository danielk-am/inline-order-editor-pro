<?php
// Protects manually assigned document numbers and independent WooCommerce references.
eval('namespace Automattic\\WooCommerce\\Utilities; class OrderUtil {public static $hpos=true;public static function custom_orders_table_usage_is_enabled(){return self::$hpos;}}');
define('ABSPATH',__DIR__);function __($s,$d=''){return $s;}function sanitize_text_field($v){return trim(strip_tags((string)$v));}function get_option($key,$default=null){return $GLOBALS['reservation']??$default;}function wc_get_order_statuses(){return ['wc-pending'=>'Pending','wc-completed'=>'Completed'];}function wc_get_orders($q){$GLOBALS['query']=$q;return $GLOBALS['duplicate'] ?? [];}
class NumberOrder{public $meta=[];function get_id(){return 23;}function get_meta($k){return $this->meta[$k]??'';}function update_meta_data($k,$v){$this->meta[$k]=$v;}}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-document-numbers.php';
function check($v,$label){if(!$v)throw new Exception($label);echo 'PASS '.$label."\n";}
$o=new NumberOrder();Alkamy_Document_Numbers::apply($o,['invoice'=>'INV-000123','quotation'=>'Q/2026/001','delivery_order'=>'DO-005','packing_slip'=>'PS-009']);
check(Alkamy_Document_Numbers::read($o)['invoice']==='INV-000123','Assigned number preserves prefix and leading zeros');
check(Alkamy_Document_Numbers::read($o)['quotation']==='Q/2026/001','Document series remain separate');
check($GLOBALS['query']['limit']===2&&$GLOBALS['query']['meta_query'][0]['key']==='_alkamy_number_packing_slip','Duplicate lookup is bounded and matches document series');
$old=$o->meta;$GLOBALS['duplicate']=[99];try{Alkamy_Document_Numbers::apply($o,['invoice'=>'INV-DUP','quotation'=>'CHANGED']);throw new Exception('Duplicate allowed');}catch(InvalidArgumentException $e){check($o->meta===$old,'Duplicate rejected before any number changes');}$GLOBALS['duplicate']=[];
try{Alkamy_Document_Numbers::apply($o,['invoice'=>str_repeat('x',101)]);throw new Exception('Oversized allowed');}catch(InvalidArgumentException $e){check($o->meta===$old,'Oversized identifier cannot be silently truncated');}
Alkamy_Document_Numbers::apply($o,['quotation'=>'<b>Q-002</b>','bad'=>'x']);check(Alkamy_Document_Numbers::read($o)['invoice']==='INV-000123'&&Alkamy_Document_Numbers::read($o)['quotation']==='Q-002','Absent fields preserved and supplied strings sanitized');
Alkamy_Document_Numbers::apply($o,['invoice'=>'']);check(Alkamy_Document_Numbers::read($o)['invoice']==='','Explicit clearing restores an unnumbered draft');

function get_posts($q){$GLOBALS['legacy_query']=$q;return $GLOBALS['duplicate']??[];}
\Automattic\WooCommerce\Utilities\OrderUtil::$hpos=false;
Alkamy_Document_Numbers::apply($o,['invoice'=>'INV-LEGACY-NEW']);
check(isset($GLOBALS['legacy_query']['meta_query'])&&$GLOBALS['legacy_query']['posts_per_page']===2,'Legacy storage uses bounded WordPress metadata query');
class Alkamy_Invoice_Delivery {static function get_issued($id){return $GLOBALS['issued']??null;}static function assert_number_available($n,$id){if($n==='INV-RESERVED')throw new RuntimeException('Reserved invoice number');}}
$GLOBALS['issued']=['number'=>'INV-LEGACY-NEW'];$old=$o->meta;
foreach(['INV-CHANGED',''] as $value){try{Alkamy_Document_Numbers::apply($o,['invoice'=>$value]);throw new Exception('Issued invoice identity changed');}catch(InvalidArgumentException $e){check($o->meta===$old,'Issued invoice number cannot be changed or cleared');}}
$GLOBALS['issued']=null;try{Alkamy_Document_Numbers::apply($o,['invoice'=>'INV-RESERVED']);throw new Exception('Issued number reused');}catch(InvalidArgumentException $e){check($o->meta===$old,'Previously issued number cannot be assigned to another order');}

$GLOBALS['reservation']='INV-RETRY';try{Alkamy_Document_Numbers::apply($o,['invoice'=>'INV-CHANGED']);throw new Exception('Reserved reference changed');}catch(InvalidArgumentException $e){check($o->meta===$old,'Failed invoice attempt retains its reserved identity');}
