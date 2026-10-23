<?php
// protects safe portable document templates; fails when unsupported blocks or unchecked media reach PDF output.
define('ABSPATH',__DIR__);
function wp_strip_all_tags($s){return strip_tags(preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is','',(string)$s));}function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}function esc_attr($s){return esc_html($s);}function sanitize_text_field($s){return trim(strip_tags((string)$s));}function sanitize_textarea_field($s){return sanitize_text_field($s);}function absint($n){return abs((int)$n);}function get_option($k,$d=[]){return $GLOBALS['test_options'][$k] ?? $d;}function __($s,$d=''){return $s;}
class Alkamy_Documents{const TYPES=['invoice','quotation','packing_slip','delivery_order','credit_note'];}
$file=dirname(__DIR__).'/modules/invoicing/includes/class-alkamy-layout.php';
if(!is_file($file)){fwrite(STDERR,"FAIL document layout renderer not implemented\n");exit(1);}require $file;
function check($ok,$name){if(!$ok)throw new Exception($name);echo 'PASS '.$name."\n";}
$clean=Alkamy_Layout::clean_brand(['logo_id'=>-1,'logo_width'=>9999,'paper'=>'evil','phone'=>'<script>bad</script>','footer'=>'footer']);
check($clean['paper']==='a4'&&$clean['logo_width']===180,'Invalid paper and oversized logo bounded');
$blocks=Alkamy_Layout::compile([['blockName'=>'alkamy/document-part','attrs'=>['part'=>'items','spaceAfter'=>999]],['blockName'=>'core/html','innerHTML'=>'<script>x</script>'],['blockName'=>'core/paragraph','innerHTML'=>'<p>Hello <strong>world</strong></p>']]);
check(count($blocks)===2&&$blocks[0]['part']==='items'&&$blocks[0]['spaceAfter']===48&&$blocks[1]['text']==='Hello world','Only supported blocks compile, in merchant order');
$record=Alkamy_Layout::sample('packing_slip');$record['snapshot']['lines'][0]['net']=9876500;$record['snapshot']['customFields'][]=['label'=>'Reference','value'=>'<script>alert(1)</script>'];
$html=Alkamy_Layout::html($record);
check(strpos($html,'98765')===false&&strpos($html,'Unit price')===false&&strpos($html,'Subtotal')===false,'Packing slip excludes all monetary columns and totals');
check(strpos($html,'<script>')===false&&strpos($html,'&lt;script&gt;')!==false,'Custom fields escaped');
$record=Alkamy_Layout::sample('credit_note');$record['originalNumber']='INV-100';
check(strpos(Alkamy_Layout::html($record),'INV-100')!==false,'Credit note references original invoice');
$nested=Alkamy_Layout::compile([['blockName'=>'core/group','attrs'=>['style'=>['spacing'=>['padding'=>['top'=>'24px']], 'color'=>['text'=>'#123456']]],'innerBlocks'=>[['blockName'=>'core/columns','innerBlocks'=>[['blockName'=>'core/column','attrs'=>['width'=>'60%'],'innerBlocks'=>[['blockName'=>'alkamy/document-part','attrs'=>['part'=>'business']]]],['blockName'=>'core/column','innerBlocks'=>[['blockName'=>'core/heading','attrs'=>['level'=>3,'textAlign'=>'right'],'innerHTML'=>'<h3>Custom heading</h3>']]]]]]]]);
check(count($nested)===1&&$nested[0]['part']==='group'&&$nested[0]['children'][0]['part']==='columns','Nested authored groups and columns retained');
check($nested[0]['style']['padding-top']==='24px'&&$nested[0]['style']['color']==='#123456','Authored bounded styles retained');
$record=Alkamy_Layout::sample();$record['snapshot']['layout']['blocks']=$nested;$html=Alkamy_Layout::html($record);
check(strpos($html,'display:table')!==false&&strpos($html,'Custom heading')!==false&&strpos($html,'padding-top:24px')!==false,'Compiled presentation reaches print-safe renderer');
$unsafe=Alkamy_Layout::compile([['blockName'=>'core/image','attrs'=>['url'=>'https://evil.test/x.png']],['blockName'=>'core/paragraph','attrs'=>['style'=>['color'=>['text'=>'red; background:url(http://evil)']]],'innerHTML'=>'<p>safe<script>bad()</script></p>']]);
check(count($unsafe)===1&&empty($unsafe[0]['style']['color']),'Remote image and CSS injection rejected');
$GLOBALS['test_options']['alkamy_document_brand']=['footer'=>'Shared footer marker'];
$record=Alkamy_Layout::sample();$record['snapshot']['layout']['blocks'][]=['part'=>'group','children'=>[['part'=>'footer']]];
check(substr_count(Alkamy_Layout::html($record),'Shared footer marker')===1,'Explicit nested footer appears exactly once');
$record=Alkamy_Layout::sample();$record['snapshot']['paid']=true;$record['snapshot']['totalRefunded']=1000;
check(strpos(Alkamy_Layout::html($record),'Payment due within')===false&&strpos(Alkamy_Layout::html($record),'Refunded:')!==false,'Paid and partially refunded invoices suppress payment demand');
$record=Alkamy_Layout::sample();$record['localExport']=true;
check(strpos(Alkamy_Layout::html($record),'days of issue')===false,'Local current-order exports make no invoice issuance promise');
$record['snapshot']['layout']['blocks']=[['part'=>'columns','children'=>[['part'=>'column','children'=>[['part'=>'items']]]]]];
check(strpos(Alkamy_Layout::html($record),'class="document-columns"')===false,'Flowing items in columns stack for safe pagination');
$palette=Alkamy_Layout::compile([['blockName'=>'core/paragraph','attrs'=>['textColor'=>'black','backgroundColor'=>'white','fontSize'=>'large'],'innerHTML'=>'<p>text</p>']]);
check($palette[0]['style']['color']==='#000000'&&$palette[0]['style']['font-size']==='24px','Palette presets and font-size presets reach PDFs');
$malformed=Alkamy_Layout::compile([['blockName'=>'core/column','attrs'=>['width'=>['bad']]],['blockName'=>'core/paragraph','attrs'=>['fontSize'=>['bad'],'style'=>['color'=>['text'=>['bad']]]],'innerHTML'=>'<p>safe</p>']]);
check(count($malformed)===2,'Malformed presentation attributes safely ignored');
$missing=Alkamy_Layout::validation_errors('packing_slip',['blocks'=>[['part'=>'header'],['part'=>'recipient'],['part'=>'items']]]);
check(!$missing&&in_array('totals',Alkamy_Layout::validation_errors('invoice',['blocks'=>[['part'=>'header'],['part'=>'recipient'],['part'=>'items']]]),true),'Required financial areas are validated by document type');
$style=Alkamy_Layout::style(['style'=>['border'=>['width'=>'2px','style'=>'dashed','color'=>'#000000'],'typography'=>['lineHeight'=>'1.8','fontStyle'=>'italic']]]);
check($style['border-width']==='2px'&&$style['line-height']==='1.8','Border and typography controls compile into bounded CSS');
check(Alkamy_Layout::dimension('var:preset|spacing|40')==='30px','Native PDF spacing presets resolve to physical CSS values');
$html=Alkamy_Layout::html(Alkamy_Layout::sample());
check(strpos($html,'<table class="document-meta">')!==false&&strpos($html,'>No.</th>')!==false&&strpos($html,'<table class="billing recipient">')!==false&&strpos($html,'>Address</th>')!==false&&strpos($html,'<dl')===false,'Document metadata and billing grids use real table rows for PDF rendering');
check(strpos($html,'<th>Description</th><th class="numeric">Price</th><th class="numeric">Qty</th><th class="numeric">Amount</th>')!==false,'Priced item columns follow actual Folio price then quantity order');
echo "PASS layout contracts\n";
