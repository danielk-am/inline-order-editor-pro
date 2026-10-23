<?php
/** Standalone immutable byte import / dry-run regression. */
define('ABSPATH',__DIR__.'/');define('ARRAY_A','ARRAY_A');
$GLOBALS['options']=array();$GLOBALS['saved']=0;
function get_option($k,$d=false){return $GLOBALS['options'][$k]??$d;}function add_option($k,$v,$deprecated='',$autoload=''){if(isset($GLOBALS['options'][$k])){return false;}$GLOBALS['options'][$k]=$v;return true;}function absint($v){return abs((int)$v);}function wc_get_order($id){return 44===$id||'44'===$id?new WC_Order():false;}
class WC_Order {public function get_id(){return 44;}}
class WPCPI_Numbering {static function instance(){return new self();}function format_number($n,$id,$type){return '202610-000001';}}
class WPCPI_Invoice {public $type='invoice';function __construct($o){}}class WPCPI_Packing_Slip {public $type='packing_slip';function __construct($o){}}
class WPCPI_PDF_Generator {static function get_file_path($doc){return $GLOBALS['fixture'].'/'.$doc->type.'.pdf';}}
class Alkamy_Invoice_Delivery {static function atomic_add($key,$value){return add_option($key,$value,'','no');}static function import_legacy($o,$n,$bytes,$source,$created){$GLOBALS['saved']++;$GLOBALS['issued']=array('bytes'=>$bytes,'created'=>$created);}static function bytes($id){return $GLOBALS['issued']['bytes'];}}
class DB {public $prefix='wp_';public $last_error='';public $failed=false;function prepare($sql,$table){return str_replace('%i','`'.$table.'`',$sql);}function get_results($sql,$shape){if($this->failed){$this->last_error='missing ledger';return null;}return array(array('doc_type'=>'invoice','order_id'=>44,'number'=>1,'created_at'=>'2026-10-05 14:49:55'));}}$wpdb=new DB();
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-invoice-legacy.php';
$checks=0;function check($yes,$label){global $checks;if(!$yes){throw new RuntimeException($label);}++$checks;}
$GLOBALS['fixture']=sys_get_temp_dir().'/alkamy-legacy-test-'.bin2hex(random_bytes(8));mkdir($GLOBALS['fixture'],0700);$a='%PDF-1.4 exact historical invoice';$b='%PDF-1.4 exact historical packing';
try{
 file_put_contents($GLOBALS['fixture'].'/invoice.pdf',$a);file_put_contents($GLOBALS['fixture'].'/packing_slip.pdf',$b);
 $dry=Alkamy_Invoice_Legacy::migrate();check(2===count($dry),'both documents planned');check(0===$GLOBALS['saved']&&!$GLOBALS['options'],'dry run writes nothing');
 $actual=Alkamy_Invoice_Legacy::migrate(true);check($actual[0]['verified']&&$actual[1]['verified'],'verified after apply');check($a===Alkamy_Invoice_Legacy::bytes(44,'invoice'),'exact invoice bytes');check($b===Alkamy_Invoice_Legacy::bytes(44,'packing_slip'),'exact packing bytes');check('2026-10-05 14:49:55'===$GLOBALS['issued']['created'],'original issue date');
 $key='alkamy_legacy_document_44_packing_slip';$GLOBALS['options'][$key]['pdf']=base64_encode('%PDF-1.4 tampered');$denied=false;try{Alkamy_Invoice_Legacy::bytes(44,'packing_slip');}catch(RuntimeException $e){$denied=true;}check($denied,'corrupt archive denied');
 unlink($GLOBALS['fixture'].'/invoice.pdf');$denied=false;try{Alkamy_Invoice_Legacy::migrate();}catch(RuntimeException $e){$denied=true;}check($denied,'missing original blocks migration');
 foreach(array(false,true) as $apply){$wpdb->failed=true;$denied=false;try{Alkamy_Invoice_Legacy::migrate($apply);}catch(RuntimeException $e){$denied=true;}check($denied,'Ledger read failure blocks dryrun and apply');}
 echo "$checks legacy migration checks passed.\n";
}finally{foreach(glob($GLOBALS['fixture'].'/*') as $p){unlink($p);}rmdir($GLOBALS['fixture']);}
