<?php
// protects read-only order snapshots; fails when signed adjustments, settled facts or partial refund documents are wrong.
define('ABSPATH',__DIR__);function __($s,$d=''){return $s;}function sanitize_text_field($s){return trim(strip_tags((string)$s));}function wc_get_price_decimals(){return 2;}function wc_format_decimal($v,$d){return number_format((float)$v,$d,'.','');}function get_option($k,$d=[]){return $d;}function get_bloginfo($k){return 'Sample seller';}
class Alkamy_Layout { static function preset($t){return [];} static function identity(){return ['name'=>'Sample seller'];} }
class Alkamy_Documents {const TYPES=['invoice','quotation','packing_slip','delivery_order','credit_note'];static function sections($o){return [];} }
class Alkamy_Order_Enhancements{static function clean_fields($v){return [];}}
class WC_Tax {static function get_rate_percent_value($id){return 9;}}
class WC_Order_Item_Product{function get_total(){return '100.004';}function get_total_tax(){return '9.00';}function get_name(){return 'Product';}function get_quantity(){return 2;}function get_taxes(){return ['total'=>[1=>'9.00']];}}
class Fee extends WC_Order_Item_Product{function get_total(){return '-10.0000';}function get_total_tax(){return '-0.90';}function get_name(){return 'Discount';}}
class FakeOrder {function get_items($t){return [new WC_Order_Item_Product,new Fee];}function get_total(){return '98.10';}function get_id(){return 11;}function get_order_number(){return '11';}function get_status(){return 'completed';}function is_paid(){return true;}function get_total_refunded(){return '20.00';}function get_currency(){return 'SGD';}function get_meta($k){return '';}function get_refunds(){return [new FakeRefund];}function __call($k,$a){return '';}}
class FakeRefund extends FakeOrder {function get_id(){return 22;}function get_amount(){return '20.00';}function get_total(){return '-20.00';}function get_items($t){return [];}function get_reason(){return 'Partial return';}}
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-document-numbers.php';
require dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-local-pdf.php';
function check($v,$n){if(!$v)throw new Exception($n);echo 'PASS '.$n."\n";}
check(method_exists('Alkamy_Local_Pdf','record'),'Local snapshot helper available');
$o=new FakeOrder;$r=Alkamy_Local_Pdf::record($o,'invoice');
check($r['snapshot']['total']===9810&&$r['snapshot']['lines'][1]['net']===-1000&&$r['snapshot']['lines'][1]['tax']===-90,'Negative discount exactly reconciled');
check($r['snapshot']['paid']===true&&$r['snapshot']['totalRefunded']===2000&&$r['status']==='completed','Paid and partial refund facts preserved');
$r=Alkamy_Local_Pdf::record($o,'credit_note',22);
check($r['snapshot']['total']===2000&&$r['snapshot']['lines'][0]['net']===2000&&$r['snapshot']['refundId']==='22'&&$r['originalNumber']==='Unnumbered invoice','Existing partial refund exports its own amount');
try{Alkamy_Local_Pdf::record($o,'credit_note',0);throw new Exception('Credit note accepted without refund');}catch(InvalidArgumentException $e){echo "PASS Refund must exist on this order\n";}
class PendingOrder extends FakeOrder{function is_paid(){return false;}function needs_payment(){return true;}function get_total_refunded(){return '0.00';}function get_checkout_payment_url(){return 'https://example.test/checkout/order-pay/11/?key=test';}}
$r=Alkamy_Local_Pdf::record(new PendingOrder,'invoice');check($r['paymentUrl']==='https://example.test/checkout/order-pay/11/?key=test','Unpaid invoice uses existing WooCommerce payment page');
$r=Alkamy_Local_Pdf::record(new PendingOrder,'quotation');check($r['paymentUrl']==='','Quotation does not expose payment action');
check(Alkamy_Local_Pdf::record($o,'invoice')['paymentUrl']==='','Settled order has no payment link');
class BrokenTotalOrder extends FakeOrder{function get_total(){return '999.00';}}
try{Alkamy_Local_Pdf::record(new BrokenTotalOrder,'invoice');throw new Exception('Accounting mismatch accepted');}catch(RuntimeException $e){echo "PASS Accounting mismatch blocks misleading export\n";}
class UntaxedProduct extends WC_Order_Item_Product{function get_total(){return '100.00';}function get_total_tax(){return '0';}}
class EmptyTaxFee extends Fee{function get_total_tax(){return '';}}
class EmptyTaxOrder extends FakeOrder{function get_items($t){return [new UntaxedProduct,new EmptyTaxFee];}function get_total(){return '90.00';}}
$r=Alkamy_Local_Pdf::record(new EmptyTaxOrder,'invoice');check($r['snapshot']['total']===9000&&$r['snapshot']['lines'][1]['tax']===0,'WooCommerce unset fee tax is zero');

class AssignedOrder extends FakeOrder{function get_meta($key){$numbers=['_alkamy_number_invoice'=>'INV-00042','_alkamy_number_quotation'=>'QUOT-00043','_alkamy_number_delivery_order'=>'DO-00044','_alkamy_number_packing_slip'=>'PS-00045'];return $numbers[$key]??'';}}
foreach(['invoice'=>'INV-00042','quotation'=>'QUOT-00043','delivery_order'=>'DO-00044','packing_slip'=>'PS-00045'] as $type=>$expected){$r=Alkamy_Local_Pdf::record(new AssignedOrder,$type);check($r['number']===$expected && $r['orderReference']==='11','Assigned '.$type.' number stays separate from Woo reference');}
$r=Alkamy_Local_Pdf::record(new AssignedOrder,'credit_note',22);check($r['originalNumber']==='INV-00042','Refund credit references assigned invoice number');
check(Alkamy_Local_Pdf::record(new FakeOrder,'invoice')['number']==='Unnumbered draft','Unassigned invoice is a draft, not the Woo order number');
