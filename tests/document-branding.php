<?php
// Protects effective company defaults, compact document spacing and selectable PDF footers.
require __DIR__.'/document-layout.php';
function get_bloginfo($key){return 'Store business';}
function get_theme_mod($key,$default=0){return $GLOBALS['theme_logo'] ?? $default;}
function get_attached_file($id){return $GLOBALS['logo_file'] ?? '';}
function wp_getimagesize($file){return getimagesize($file);}
function get_the_title($id){return 'Store logo';}
$GLOBALS['test_options']=['woocommerce_store_address'=>'1 Test Street','woocommerce_store_city'=>'Singapore','woocommerce_store_postcode'=>'123456'];
$identity=Alkamy_Layout::identity();
check($identity['name']==='Store business' && str_contains($identity['address'],'1 Test Street'),'Unset business details use configured store identity');
$GLOBALS['test_options']['alkamy_identity']=['name'=>'Legal business','address'=>"Registered address\nSecond line",'uen'=>'123X'];
check(Alkamy_Layout::identity()['name']==='Legal business','Saved legal business details take precedence');
$logo=tempnam(sys_get_temp_dir(),'alkamy-logo');file_put_contents($logo,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aDUcAAAAASUVORK5CYII='));$GLOBALS['logo_file']=$logo;$GLOBALS['theme_logo']=9;
check(Alkamy_Layout::brand()['logo_id']===9,'Unconfigured document logo uses an eligible store raster logo');
$GLOBALS['test_options']['alkamy_document_brand']=['logo_id'=>0];check(Alkamy_Layout::brand()['logo_id']===0,'Explicit logo removal remains removed');
$GLOBALS['test_options']['alkamy_document_brand']=['logo_id'=>9,'density'=>'compact','footer_type'=>'company','footer'=>'Footer marker'];
$r=Alkamy_Layout::sample();$h=Alkamy_Layout::html($r);
check(str_contains($h,'data:image/png;base64,')&&str_contains($h,'Legal business')&&str_contains($h,'Registered address'),'Logo and configured legal identity reach invoice HTML');
check(str_contains($h,'class="paper density-compact"')&&str_contains($h,'.density-compact .priced-items td'),'Compact mode controls actual item spacing');
check(str_contains($h,'class="document-footer footer-company"')&&substr_count($h,'Legal business')>=2,'Company footer contains business identity');
foreach(['text','signature','none']as$type){$r['snapshot']['layout']['brand']['footer_type']=$type;$h=Alkamy_Layout::html($r);if($type==='none'){check(!str_contains($h,'Footer marker')&&!str_contains($h,'class="signatures"'),'No footer excludes automatic text and signatures');}elseif($type==='signature'){check(substr_count($h,'class="signatures"')===1,'Signature footer renders once');}else{check(str_contains($h,'Footer marker')&&!str_contains($h,'class="signatures"'),'Text footer has no automatic signature fields');}}
$r['snapshot']['layout']['blocks'][]=['part'=>'footer','footerType'=>'company'];$r['snapshot']['layout']['brand']['footer_type']='none';$h=Alkamy_Layout::html($r);check(substr_count($h,'footer-company')===1,'Layout footer type override works without auto duplicate');
$b=Alkamy_Layout::compile([['blockName'=>'alkamy/document-part','attrs'=>['part'=>'items','density'=>'compact']],['blockName'=>'alkamy/document-part','attrs'=>['part'=>'footer','footerType'=>'company']]]);
check($b[0]['density']==='compact'&&$b[1]['footerType']==='company','Per-layout table density and footer choice compile');
$c=Alkamy_Layout::clean_brand(['density'=>'bad','footer_type'=>'bad']);check($c['density']==='standard'&&$c['footer_type']==='signature','Unknown choices fall back to safe defaults');
unlink($logo);
