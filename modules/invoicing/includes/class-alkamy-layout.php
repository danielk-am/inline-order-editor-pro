<?php
/** Portable document compositions shared by previews and the PDF service. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Layout {
 const PARTS = array( 'header', 'recipient', 'references', 'items', 'totals', 'sections', 'signatures' );
 public static function defaults() {
  return array_map( static function ( $part ) { return array( 'part'=>$part, 'spaceAfter'=>16 ); }, array_diff( self::PARTS, array( 'signatures' ) ) );
 }
 /** Effective defaults are read-only. Explicitly saved empty values remain editable. */
 public static function identity() {
  $address=array();foreach(array('address','address_2','city','postcode') as $key){$address[]=get_option('woocommerce_store_' . $key,'');}
  $defaults=array('name'=>function_exists('get_bloginfo') ? get_bloginfo('name') : 'Sample business','address'=>implode("\n",array_filter($address)),'uen'=>'','gst'=>'','peppolId'=>'');
  $saved=get_option('alkamy_identity',array());
  foreach($defaults as $key=>$value){$defaults[$key]=sanitize_textarea_field(is_array($saved) && array_key_exists($key,$saved) ? $saved[$key] : $value);}
  return $defaults;
 }
 public static function brand() {
  $input=get_option('alkamy_document_brand',array());$input=is_array($input)?$input:array();
  if(!array_key_exists('logo_id',$input) && function_exists('get_theme_mod')){$id=absint(get_theme_mod('custom_logo',0));if(self::raster($id)){$input['logo_id']=$id;}}
  return self::clean_brand($input);
 }
 public static function clean_brand( $input ) {
  $input = is_array( $input ) ? $input : array();
  $clean = array( 'logo_id'=>absint( $input['logo_id'] ?? 0 ), 'logo_width'=>max( 40, min( 180, absint( $input['logo_width'] ?? 100 ) ) ), 'paper'=>'letter' === ( $input['paper'] ?? '' ) ? 'letter' : 'a4' );
  foreach ( array( 'phone', 'email', 'website', 'prepared_by' ) as $key ) { $clean[ $key ] = substr( sanitize_text_field( $input[ $key ] ?? '' ), 0, 200 ); }
  $clean['footer'] = substr( sanitize_textarea_field( $input['footer'] ?? '' ), 0, 2000 );
  $clean['density']='compact' === ($input['density'] ?? '') ? 'compact' : 'standard';
  $clean['footer_type']=in_array($input['footer_type'] ?? '',array('text','company','signature','none'),true) ? $input['footer_type'] : 'signature';
  return $clean;
 }
 /** Bounded document DSL; never execute WordPress render_block(), HTML, or shortcodes. */
 public static function compile( $blocks, $depth = 0, &$budget = null, &$media_budget = null, $references = array() ) {
  if ( null === $media_budget ) { $media_budget = 1048576; }
  if ( null === $budget ) { $budget = 160; }
  if ( $depth > 8 || ! is_array( $blocks ) ) { return array(); }
  $result = array();
  foreach ( $blocks as $block ) {
   if ( --$budget < 0 ) { break; }
   $name = $block['blockName'] ?? ''; $attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : array(); $node = null;
   if ( 'core/block' === $name ) {
    $ref=absint($attrs['ref'] ?? 0);
    $post=$ref && !in_array($ref,$references,true) && function_exists('get_post') ? get_post($ref) : null;
    if($post && 'wp_block' === $post->post_type && 'publish' === $post->post_status && current_user_can('edit_post',$ref)){
     $next=$references;$next[]=$ref;
     $node=array('part'=>'group','children'=>self::compile(parse_blocks($post->post_content),$depth+1,$budget,$media_budget,$next),'spaceAfter'=>0);
    }
   } elseif ( 'alkamy/document-part' === $name && in_array( $attrs['part'] ?? 'header', array_merge( self::PARTS, array( 'business', 'document', 'logo', 'footer', 'paragraph', 'heading', 'spacer', 'separator' ) ), true ) ) {
    $node = array( 'part'=>$attrs['part'] ?? 'header', 'text'=>substr( sanitize_textarea_field( $attrs['text'] ?? '' ), 0, 8000 ), 'spaceAfter'=>max( 0, min( 48, (int) ( $attrs['spaceAfter'] ?? 16 ) ) ) );
    if(in_array($attrs['density'] ?? '',array('standard','compact'),true)){$node['density']=$attrs['density'];}
    if(in_array($attrs['footerType'] ?? '',array('text','company','signature','none'),true)){$node['footerType']=$attrs['footerType'];}
   } elseif ( in_array( $name, array( 'core/group', 'core/columns', 'core/column' ), true ) ) {
    $node = array( 'part'=>'core/group' === $name && 'flex' === ($attrs['layout']['type'] ?? '') && 'vertical' !== ($attrs['layout']['orientation'] ?? '') ? 'columns' : substr( $name, 5 ), 'children'=>self::compile( $block['innerBlocks'] ?? array(), $depth + 1, $budget, $media_budget, $references ), 'spaceAfter'=>0 );
    if ( 'core/column' === $name && is_string( $attrs['width'] ?? '' ) && preg_match( '/^([0-9]{1,2}|100)%$/D', $attrs['width'] ?? '' ) ) { $node['width'] = $attrs['width']; }
    $gap_input=$attrs['style']['spacing']['blockGap'] ?? '';
    $gap = self::dimension( is_array($gap_input) ? ($gap_input['left'] ?? $gap_input['top'] ?? '') : $gap_input, 0, 96 );
    if ( $gap ) { $node['gap'] = $gap; }
   } elseif ( in_array( $name, array( 'core/paragraph', 'core/heading' ), true ) ) {
    $node = array( 'part'=>'core/heading' === $name ? 'heading' : 'paragraph', 'text'=>substr( html_entity_decode( wp_strip_all_tags( $block['innerHTML'] ?? '' ), ENT_QUOTES, 'UTF-8' ), 0, 8000 ), 'rich'=>self::rich_text( $block['innerHTML'] ?? '' ), 'spaceAfter'=>12, 'level'=>max( 1, min( 6, (int) ( $attrs['level'] ?? 2 ) ) ) );
   } elseif ( 'core/list' === $name ) {
    $node = array( 'part'=>'list', 'ordered'=>! empty( $attrs['ordered'] ), 'entries'=>array(), 'spaceAfter'=>12 );
    foreach ( array_slice( $block['innerBlocks'] ?? array(), 0, 50 ) as $item ) { if ( 'core/list-item' === ( $item['blockName'] ?? '' ) ) { $node['entries'][] = self::rich_text( $item['innerHTML'] ?? '' ); } }
    // Legacy lists keep their safe list markup rather than losing the bullets.
    if ( ! $node['entries'] ) { $node['rich'] = self::rich_text( $block['innerHTML'] ?? '', true ); }
   } elseif ( 'core/image' === $name ) {
    $data = self::raster( absint( $attrs['id'] ?? 0 ) );
    if ( $data && strlen($data) <= $media_budget ) { $media_budget -= strlen($data); $node = array( 'part'=>'image', 'data'=>$data, 'alt'=>substr( sanitize_text_field( $attrs['alt'] ?? '' ), 0, 200 ), 'spaceAfter'=>12 ); $width = self::dimension( $attrs['width'] ?? '', 24, 640 ); if ( $width ) { $node['width'] = $width; } }
   } elseif ( 'core/separator' === $name ) { $node = array( 'part'=>'separator', 'spaceAfter'=>12 ); }
   elseif ( 'core/spacer' === $name ) { $node = array( 'part'=>'spacer', 'spaceAfter'=>(int) ( self::dimension( $attrs['height'] ?? '16px', 0, 160 ) ?: 16 ) ); }
   // Reusable patterns compile to frozen children; cycles and references without edit access are omitted.
   if ( $node ) { $node['style'] = self::style( $attrs ); $result[] = $node; }
  }
  return $result;
 }
 public static function dimension( $value, $min = 0, $max = 96 ) {
  // These exact local document presets are also installed in the PDF editor canvas.
  $spacing=array('20'=>'10px','30'=>'20px','40'=>'30px','50'=>'40px','60'=>'60px','70'=>'80px','80'=>'96px');
  if(is_string($value) && preg_match('/^var:preset\|spacing\|([0-9]{2})$/D',$value,$token)){$value=$spacing[$token[1]] ?? '';}
  if(is_string($value) && preg_match('/^var\(--wp--preset--spacing--([0-9]{2})\)$/D',$value,$token)){$value=$spacing[$token[1]] ?? '';}
  if ( ! is_scalar( $value ) || ! preg_match( '/^([0-9]+(?:\.[0-9]+)?)(px|pt|rem|em)?$/D', (string) $value, $match ) ) { return ''; }
  $number = (float) $match[1]; $unit = $match[2] ?? 'px';
  if ( in_array( $unit, array( 'rem', 'em' ), true ) ) { $number *= 16; } elseif ( 'pt' === $unit ) { $number *= 4 / 3; }
  return max( $min, min( $max, $number ) ) . 'px';
 }
 public static function style( $attrs ) {
  $style = array(); $input = isset($attrs['style']) && is_array($attrs['style']) ? $attrs['style'] : array();
  $palette=array( 'black'=>'#000000','white'=>'#ffffff','grey'=>'#666666' );
  foreach(array('textColor'=>'color','backgroundColor'=>'background-color') as $attr=>$css){$v=$attrs[$attr] ?? '';if(is_string($v) && isset($palette[$v])){$style[$css]=$palette[$v];}}
  foreach ( array( 'text'=>'color', 'background'=>'background-color' ) as $key=>$css ) { $v=$input['color'][ $key ] ?? ''; if ( is_string($v) && preg_match( '/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/D', $v ) ) { $style[ $css ]=$v; } }
  foreach ( array( 'padding', 'margin' ) as $key ) { $value = $input['spacing'][ $key ] ?? array(); foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) { $v=self::dimension( is_array( $value ) ? ( $value[ $side ] ?? '' ) : $value ); if ( $v ) { $style[ $key . '-' . $side ]=$v; } } }
  $v=self::dimension( $input['typography']['fontSize'] ?? '', 8, 48 );
  if ( ! $v && isset( $attrs['fontSize'] ) && is_string($attrs['fontSize']) ) { $sizes=array( 'small'=>'11px', 'medium'=>'16px', 'large'=>'24px', 'x-large'=>'32px' ); $v=$sizes[ $attrs['fontSize'] ] ?? ''; }
  if ( $v ) { $style['font-size']=$v; }
  $align=$attrs['textAlign'] ?? ( $attrs['align'] ?? '' ); if ( in_array( $align, array( 'left', 'center', 'right' ), true ) ) { $style['text-align']=$align; }
  foreach ( array( 'fontWeight'=>array( '400','500','600','700','800','900' ), 'textTransform'=>array( 'none','uppercase','lowercase','capitalize' ) ) as $key=>$values ) { $v=(string) ( $input['typography'][ $key ] ?? '' ); if ( in_array( $v,$values,true ) ) { $style['fontWeight' === $key ? 'font-weight' : 'text-transform']=$v; } }
  $v=$input['typography']['lineHeight'] ?? '';if(is_scalar($v) && is_numeric($v) && (float)$v>=1 && (float)$v<=3){$style['line-height']=(string)$v;}
  $v=self::dimension($input['typography']['letterSpacing'] ?? '',0,8);if($v){$style['letter-spacing']=$v;}
  $v=$input['typography']['textDecoration'] ?? '';if(in_array($v,array('none','underline','line-through'),true)){$style['text-decoration']=$v;}
  $v=$input['typography']['fontStyle'] ?? '';if(in_array($v,array('normal','italic'),true)){$style['font-style']=$v;}
  $v=$input['typography']['fontFamily'] ?? '';if(in_array($v,array('Arial','Arial, sans-serif','serif','sans-serif','monospace','Georgia','Times New Roman'),true)){$style['font-family']=$v;}
  $border=$input['border'] ?? array();
  if(is_array($border)){
   foreach(array('width'=>'border-width','radius'=>'border-radius') as $key=>$css){$v=self::dimension($border[$key] ?? '',0,16);if($v){$style[$css]=$v;}}
   $v=$border['style'] ?? '';if(in_array($v,array('solid','dashed','dotted','double','none'),true)){$style['border-style']=$v;}
   $v=$border['color'] ?? '';if(is_string($v) && preg_match('/^#[a-fA-F0-9]{3}(?:[a-fA-F0-9]{3})?$/D',$v)){$style['border-color']=$v;}
  }
  $v=self::dimension($input['dimensions']['minHeight'] ?? '',0,1000);if($v){$style['min-height']=$v;}
  $v=$attrs['verticalAlignment'] ?? '';if(in_array($v,array('top','center','bottom'),true)){$style['vertical-align']='center' === $v?'middle':$v;}
  return $style;
 }
 public static function rich_text( $html, $lists = false ) {
  // Only inline formatting survives. No user links, images, style, scripts or attributes.
  $html = preg_replace( '#<(script|style|iframe|object)\b[^>]*>.*?</\1>#is', '', substr( (string) $html, 0, 16000 ) );
  $tags=array( 'strong'=>array(), 'b'=>array(), 'em'=>array(), 'i'=>array(), 'u'=>array(), 's'=>array(), 'br'=>array(), 'code'=>array() );
  if ( $lists ) { $tags += array( 'ul'=>array(), 'ol'=>array(), 'li'=>array() ); }
  if ( function_exists( 'wp_kses' ) ) { return wp_kses( $html, $tags ); }
  return strip_tags( $html, '<strong><b><em><i><u><s><br><code>' );
 }
 public static function raster( $id ) {
  if ( ! $id || ! function_exists( 'get_attached_file' ) ) { return ''; }
  $file=get_attached_file( $id );
  // Attachment filters can return streams. Reject them before any stat, image probe or read.
  // Keep filtered local paths, including offloaded-to-disk media outside the uploads directory.
  if ( ! is_string( $file ) || '' === $file || false !== strpos( $file, "\0" ) || false !== strpos( $file, '://' ) ) { return ''; }
  $file=realpath( $file );
  $size=$file && is_file( $file ) && is_readable( $file ) ? filesize( $file ) : 0;
  $image=$size && $size <= 262144 ? wp_getimagesize( $file ) : false;
  if ( ! $image || ! in_array( $image['mime'],array( 'image/png','image/jpeg' ),true ) || $image[0] > 2000 || $image[1] > 2000 ) { return ''; }
  $bytes=file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a canonical local raster path after rejecting streams and checking size and MIME.
  return false === $bytes ? '' : 'data:' . $image['mime'] . ';base64,' . base64_encode( $bytes );
 }
 /** Dompdf cannot split a table-cell across pages. Large flowing sections become stacked blocks. */
 public static function short_columns( $nodes, $snapshot ) {
  $text=0;
  $inspect=static function($blocks) use (&$inspect,&$text){foreach($blocks as $b){if(in_array($b['part'] ?? '',array('items','sections'),true)){return false;}$text+=strlen($b['text'] ?? '');foreach($b['entries'] ?? array() as $v){$text+=strlen(wp_strip_all_tags($v));}if($text>1000 || !$inspect($b['children'] ?? array())){return false;}}return true;};
  if(!$inspect($nodes)){return false;}
  foreach(array('seller','customer','shipping') as $key){if(strlen(implode(' ',array_map('strval',$snapshot[$key] ?? array())))>1000){return false;}}
  return true;
 }
 /** Required bound data is checked for the selected document type, never while drafting. */
 public static function validation_errors( $type, $layout ) {
  $parts=array();$collect=static function($nodes) use (&$collect,&$parts){foreach($nodes as $b){$parts[]=$b['part'] ?? '';$collect($b['children'] ?? array());}};$collect($layout['blocks'] ?? array());
  $required=array('items','recipient');if(!in_array($type,array('packing_slip','delivery_order'),true)){$required[]='totals';}
  $missing=array();foreach($required as $part){if(!in_array($part,$parts,true)){$missing[]=$part;}}
  foreach(array('business','document') as $part){if(!in_array('header',$parts,true) && !in_array($part,$parts,true)){$missing[]=$part;}}
  return $missing;
 }
 public static function preset( $type, $template_id = 0, $allow_draft = false ) {
  $brand = self::brand();
  $map = get_option( 'alkamy_layout_templates', array() ); $blocks = self::defaults();
  $selected = absint( $template_id ?: ( $map[ $type ] ?? 0 ) );
  $post = $selected ? get_post( $selected ) : null;
  if ( $post && 'alkamy_pdf_layout' === $post->post_type && ( 'publish' === $post->post_status || ($allow_draft && in_array($post->post_status,array('draft','pending','auto-draft'),true) && current_user_can('edit_post',$post->ID)) ) ) { $blocks = self::compile( parse_blocks( $post->post_content ) ); }
  $brand['logoData'] = self::raster( $brand['logo_id'] );
  return array( 'version'=>2, 'brand'=>$brand, 'blocks'=>$blocks );
 }
 public static function sample( $type = 'invoice' ) {
  if ( ! in_array( $type, Alkamy_Documents::TYPES ?? array(), true ) ) { $type = 'invoice'; }
  return array( 'number'=>'SAMPLE-1001', 'created'=>'2026-10-15', 'originalNumber'=>'invoice' === $type ? '' : ( 'credit_note' === $type ? 'SAMPLE-INV-1000' : '' ), 'snapshot'=>array( 'type'=>$type, 'orderId'=>'1001', 'currency'=>'SGD', 'dueDays'=>14, 'seller'=>self::identity(), 'customer'=>array( 'name'=>'Sample customer', 'address'=>'10 Example Road, Singapore 654321', 'email'=>'customer@example.test', 'uen'=>'' ), 'shipping'=>array( 'name'=>'Sample recipient', 'address'=>'20 Delivery Road, Singapore 654321' ), 'customFields'=>array( array( 'label'=>'Purchase order', 'value'=>'SAMPLE-PO-123' ) ), 'lines'=>array( array( 'name'=>'Display mannequin', 'quantity'=>2, 'net'=>20000, 'tax'=>1800 ), array( 'name'=>'Clothing rack', 'quantity'=>1, 'net'=>6000, 'tax'=>540 ) ), 'total'=>28340, 'sections'=>array( array( 'heading'=>'Terms and conditions', 'body'=>'Sample terms. Replace with your approved terms preset.', 'documents'=>array( $type ), 'position'=>'footer' ) ), 'layout'=>self::preset( $type ) ) );
 }
 public static function legacy_html($value) {
  if('' === (string)$value){return '';}
  return wp_kses((string)$value,array('section'=>array('class'=>true),'div'=>array('class'=>true),'h2'=>array(),'h3'=>array(),'h4'=>array(),'p'=>array('class'=>true),'strong'=>array(),'em'=>array(),'b'=>array(),'i'=>array(),'br'=>array(),'ul'=>array(),'ol'=>array(),'li'=>array(),'table'=>array('class'=>true),'tbody'=>array(),'tr'=>array(),'td'=>array('class'=>true,'colspan'=>true),'span'=>array('class'=>true),'hr'=>array()));
 }
 public static function footer_html($seller_snapshot,$brand,$type,$has_signatures=false) {
  if(!empty($seller_snapshot['legacy']['footerOverride'])){return self::legacy_html($seller_snapshot['legacy']['footerHtml'] ?? '');}
  if('none' === $type){return '';}
  $seller=$seller_snapshot['seller'] ?? array();$content='';
  if('company' === $type){
   $content='<strong>' . esc_html($seller['name'] ?? '') . '</strong><br>' . nl2br(esc_html($seller['address'] ?? ''));
   foreach(array('uen'=>'UEN','gst'=>'GST registration') as $key=>$label){if(!empty($seller[$key])){$content.='<br>' . esc_html($label . ': ' . $seller[$key]);}}
   $contacts=array_filter(array($brand['phone'],$brand['email'],$brand['website']));if($contacts){$content.='<br>' . esc_html(implode(' · ',$contacts));}
  } elseif('signature' === $type && !$has_signatures){
   $content='<div class="signatures"><div><strong>Prepared by</strong><div class="signature-line"></div>' . esc_html($brand['prepared_by'] ?: ($seller['name'] ?? '')) . '<br>' . esc_html($brand['email']) . '</div><div><strong>Received by</strong><div class="signature-line"></div>Name / signature / company stamp</div></div>';
  }
  if($brand['footer']){$content.=($content ? '<p>' : '') . nl2br(esc_html($brand['footer'])) . ($content ? '</p>' : '');}
  return $content ? '<footer class="document-footer footer-' . esc_attr($type) . '">' . $content . '</footer>' : '';
 }
 public static function html( $record, $paper = null ) {
  require_once __DIR__ . '/class-alkamy-markdown.php';
  $s = $record['snapshot']; $layout = $s['layout'] ?? self::preset( $s['type'] ); $brand = self::clean_brand( $layout['brand'] ?? array() );
  $packing = in_array( $s['type'], array( 'packing_slip', 'delivery_order' ), true );
  $money = static function ( $v ) use ( $s ) { return esc_html( $s['currency'] . ' ' . number_format( $v / 100, 2, '.', ',' ) ); };
  $titles = array( 'invoice'=>'Invoice', 'quotation'=>'Quotation', 'packing_slip'=>'Packing slip', 'delivery_order'=>'Delivery order', 'credit_note'=>'Credit note' );
  $has_signatures=static function($nodes) use (&$has_signatures){foreach($nodes as $node){if('signatures' === ($node['part'] ?? '') || $has_signatures($node['children'] ?? array())){return true;}}return false;};
  $explicit_signatures=$has_signatures($layout['blocks'] ?? array());
  $legacy_rendered=false;
  $render = static function ( $nodes ) use ( &$legacy_rendered, &$render, $s, $layout, $brand, $packing, $money, $titles, $record, $explicit_signatures ) {
  $body = '';
  foreach ( $nodes as $block ) {
   $part = $block['part']; $content = '';
   if ( in_array( $part, array( 'group', 'column', 'columns' ), true ) ) {
    $children = $block['children'] ?? array();
    if ( 'columns' === $part && self::short_columns($children,$s) ) {
     $gap=self::dimension($block['gap'] ?? '',0,96) ?: '0px';
     $content='<div class="document-columns" style="display:table;table-layout:fixed;width:100%;border-spacing:' . esc_attr($gap) . ' 0"><div style="display:table-row">';
     foreach ( $children as $child ) { $width=isset( $child['width'] ) && preg_match( '/^([0-9]{1,2}|100)%$/D', $child['width'] ) ? 'width:' . $child['width'] . ';' : ''; $vertical=in_array($child['style']['vertical-align'] ?? '',array('top','middle','bottom'),true)?$child['style']['vertical-align']:'top';$content.='<div style="display:table-cell;vertical-align:' . $vertical . ';' . $width . '">' . $render( array( $child ) ) . '</div>'; }
     $content.='</div></div>';
    } else { if(isset($block['gap'])){foreach($children as $i=>&$child){if($i<count($children)-1){$child['style']['margin-bottom']=self::dimension($block['gap'],0,96);}}unset($child);} $content=$render( $children ); }
   } elseif ( 'image' === $part || 'logo' === $part ) {
    $data='logo' === $part ? ( $layout['brand']['logoData'] ?? '' ) : ( $block['data'] ?? '' );
    if ( preg_match( '#^data:image/(png|jpeg);base64,[A-Za-z0-9+/=]+$#D', $data ) && strlen( $data ) <= 350000 ) { $width='logo' === $part ? $brand['logo_width'] . 'pt' : ( self::dimension( $block['width'] ?? '180px',24,640 ) ?: '180px' ); $content='<img class="document-logo" src="' . esc_attr( $data ) . '" width="' . (int) $width . '" style="width:' . esc_attr( $width ) . ';max-width:100%" alt="' . esc_attr( $block['alt'] ?? '' ) . '">'; }
   } elseif ( 'business' === $part ) {
    $content='<div class="business"><h1>' . esc_html( $s['seller']['name'] ) . '</h1><p>' . nl2br( esc_html( $s['seller']['address'] ?? '' ) ) . '</p>';
    foreach ( array( 'phone','email','website' ) as $key ) { if ( $brand[ $key ] ) { $content.='<div>' . esc_html( $brand[ $key ] ) . '</div>'; } }
    foreach ( array( 'uen'=>'UEN','gst'=>'GST registration' ) as $key=>$label ) { if ( ! empty( $s['seller'][ $key ] ) ) { $content.='<div class="registration">' . esc_html( $label . ': ' . $s['seller'][ $key ] ) . '</div>'; } }
    $content.='</div>';
   } elseif ( 'document' === $part ) {
    $content='<div class="document-heading"><h1>' . esc_html( $titles[ $s['type'] ] ) . '</h1><p class="document-number">' . esc_html( $record['number'] ) . '</p><p>' . esc_html( substr( $record['created'] ?? '',0,10 ) ) . '</p><p>Order: ' . esc_html( $record['orderReference'] ?? $s['orderId'] ) . '</p>' . ( ! empty( $record['originalNumber'] ) ? '<p>Original invoice: ' . esc_html( $record['originalNumber'] ) . '</p>' : '' ) . '</div>';
   } elseif ( 'header' === $part ) {
    $logo = $layout['brand']['logoData'] ?? '';
    if ( preg_match( '#^data:image/(png|jpeg);base64,[A-Za-z0-9+/=]+$#D', $logo ) && strlen( $logo ) <= 350000 ) { $content .= '<img class="document-logo" src="' . esc_attr( $logo ) . '" style="width:' . (int) $brand['logo_width'] . 'pt" alt="">'; }
    $content .= '<div class="header"><div class="business"><h1>' . esc_html( $s['seller']['name'] ) . '</h1><p>' . nl2br( esc_html( $s['seller']['address'] ?? '' ) ) . '</p>';
    foreach ( array( 'phone', 'email', 'website' ) as $key ) { if ( $brand[ $key ] ) { $content .= '<div>' . esc_html( $brand[ $key ] ) . '</div>'; } }
    foreach ( array( 'uen'=>'UEN', 'gst'=>'GST registration' ) as $key=>$label ) { if ( ! empty( $s['seller'][ $key ] ) ) { $content .= '<p>' . esc_html( $label . ': ' . $s['seller'][ $key ] ) . '</p>'; } }
    $content .= '</div><div class="document-heading"><h1>' . esc_html( $titles[ $s['type'] ] ) . '</h1><table class="document-meta"><tbody><tr><th style="width:30%">No.</th><td class="document-number" style="width:70%;text-align:right">' . esc_html( $record['number'] ) . '</td></tr><tr><th style="width:30%">Date</th><td style="width:70%;text-align:right">' . esc_html( substr( $record['created'] ?? '', 0, 10 ) ) . '</td></tr><tr><th style="width:30%">Order</th><td style="width:70%;text-align:right">' . esc_html( $record['orderReference'] ?? $s['orderId'] ) . '</td></tr></tbody></table>';
    if ( ! empty( $record['originalNumber'] ) ) { $content .= '<p>Original invoice: ' . esc_html( $record['originalNumber'] ) . '</p>'; }
    $content .= '</div></div>';
   } elseif ( 'recipient' === $part ) {
    $recipient = $packing && ! empty( $s['shipping']['address'] ) ? $s['shipping'] : $s['customer'];
    $content = '<table class="billing recipient"><tbody><tr><th style="width:17%;font-weight:400;text-align:left">' . ( $packing ? 'Deliver to' : 'Bill to' ) . '</th><td style="width:83%;text-align:left">' . esc_html( $recipient['name'] ) . '</td></tr><tr><th style="width:17%;font-weight:400;text-align:left">Address</th><td style="width:83%;text-align:left">' . nl2br( esc_html( $recipient['address'] ?? '' ) ) . '</td></tr></tbody></table>';
    foreach ( array( 'uen'=>'Buyer UEN', 'peppolId'=>'Peppol ID' ) as $key=>$label ) { if ( ! empty( $s['customer'][ $key ] ) ) { $content .= '<p>' . esc_html( $label . ': ' . $s['customer'][ $key ] ) . '</p>'; } }
   } elseif ( 'references' === $part ) {
    foreach ( $s['customFields'] ?? array() as $field ) { $content .= '<table class="billing references"><tbody><tr><th style="width:17%;font-weight:400;text-align:left">' . esc_html( $field['label'] ) . '</th><td style="width:83%;text-align:left">' . esc_html( $field['value'] ) . '</td></tr></tbody></table>'; }
   } elseif ( 'items' === $part ) {
    $content = '<table class="' . ($packing?'quantity-items':'priced-items') . (isset($block['density']) ? ' table-density-' . $block['density'] : '') . '"><thead><tr><th>Description</th>' . ( $packing ? '<th class="numeric">Qty</th>' : '<th class="numeric">Price</th><th class="numeric">Qty</th><th class="numeric">Amount</th>' ) . '</tr></thead><tbody>';
    foreach ( $s['lines'] as $line ) { $content .= '<tr><td>' . esc_html( $line['name'] ) . '</td>' . ( $packing ? '<td class="numeric">' . esc_html($line['quantity']) . '</td>' : '<td class="numeric">' . $money( $line['quantity'] ? $line['net'] / $line['quantity'] : 0 ) . '</td><td class="numeric">' . esc_html($line['quantity']) . '</td><td class="numeric">' . $money( $line['net'] ) . '</td>' ) . '</tr>'; }
    $content .= '</tbody></table>';
   } elseif ( 'totals' === $part && ! $packing ) {
    $net = array_sum( array_column( $s['lines'], 'net' ) ); $tax = array_sum( array_column( $s['lines'], 'tax' ) );
    $total_label='credit_note' === $s['type'] ? 'Credit total' : (!empty($s['paid']) || !empty($s['totalRefunded']) || 'refunded' === ($s['orderStatus'] ?? '') ? 'Order total' : ('quotation' === $s['type'] ? 'Quoted total' : 'Total payable'));
    $content = '<table class="totals"><tbody><tr><td>Subtotal</td><td>' . $money( $net ) . '</td></tr><tr><td>Tax</td><td>' . $money( $tax ) . '</td></tr><tr class="payable"><th>' . esc_html($total_label) . '</th><th><span>' . $money( $s['total'] ) . '</span></th></tr></tbody></table>';
    if ( !empty($s['paid']) ) { $content.='<p>Payment recorded' . (!empty($s['totalRefunded']) ? ' · Refunded: ' . $money($s['totalRefunded']) : '') . '</p>'; }
    elseif ( !empty($s['totalRefunded']) || 'refunded' === ($s['orderStatus'] ?? '') ) { $content.='<p>Refund recorded: ' . $money($s['totalRefunded'] ?? 0) . '</p>'; }
    elseif ( 'invoice' === $s['type'] ) { $content .= !empty($record['localExport']) ? '<p>Order payment pending.</p>' : '<p>Payment due within ' . (int) $s['dueDays'] . ' days of issue.</p>'; }
    if ( !empty($s['paid']) || !empty($s['totalRefunded']) || 'refunded' === ($s['orderStatus'] ?? '') ) { /* Settled orders have no payment call to action. */ }
    elseif ( ! empty( $record['paymentUrl'] ) && 'invoice' === $s['type'] ) { $content .= '<p><a href="' . esc_url( $record['paymentUrl'] ) . '">Open secure payment page</a></p>'; }
   } elseif ( 'sections' === $part ) {
    foreach ( $s['sections'] ?? array() as $section ) { if ( in_array( $s['type'], $section['documents'], true ) && trim( $section['body'] ) ) { $content .= '<section class="terms"><h2>' . esc_html( $section['heading'] ) . '</h2>' . Alkamy_Markdown::render( $section['body'] ) . '</section>'; } }
    if(!$legacy_rendered){$content.=self::legacy_html(($s['legacy']['sectionsHtml'] ?? '') . ($s['legacy']['productTermsHtml'] ?? ''));$legacy_rendered=true;}
   } elseif ( 'signatures' === $part && empty($s['legacy']['footerOverride']) ) { $content = '<div class="signatures"><div><strong>Prepared by</strong><div class="signature-line"></div>' . esc_html( $brand['prepared_by'] ?: $s['seller']['name'] ) . '<br>' . esc_html( $brand['email'] ) . '</div><div><strong>Received by</strong><div class="signature-line"></div>Name / signature / company stamp</div></div>'; }
   elseif ( in_array( $part, array( 'paragraph', 'heading' ), true ) ) { $tag = 'heading' === $part ? 'h' . max(1,min(6,(int)($block['level'] ?? 2))) : 'p'; $content = '<' . $tag . ( isset($block['style']['font-size']) ? ' style="font-size:inherit"' : '' ) . '>' . ( isset($block['rich']) ? self::rich_text($block['rich']) : nl2br( esc_html( $block['text'] ?? '' ) ) ) . '</' . $tag . '>'; }
   elseif ( 'footer' === $part ) { $content=self::footer_html($s,$brand,$block['footerType'] ?? $brand['footer_type'],$explicit_signatures); }
   elseif ( 'list' === $part ) { $tag=!empty($block['ordered'])?'ol':'ul';$content='<' . $tag . '>';foreach($block['entries'] ?? array() as $item){$content.='<li>' . self::rich_text($item) . '</li>';}$content.='</' . $tag . '>';if(!empty($block['rich'])){$content=self::rich_text($block['rich'],true);} }
   elseif ( 'separator' === $part ) { $content = '<hr>'; }
   if ( $content || 'spacer' === $part ) {
    $css='margin-bottom:' . max(0,min(160,(int)($block['spaceAfter'] ?? 16))) * ('compact' === $brand['density'] ? 0.5 : 1) . 'pt;';
    // Revalidate compiled style keys before building attributes, even for stored snapshots.
    foreach ( $block['style'] ?? array() as $key=>$value ) { if ( preg_match('/^(color|background-color|font-size|text-align|font-weight|text-transform|line-height|font-style|font-family|letter-spacing|text-decoration|border-(width|radius|style|color)|min-height|vertical-align|padding-(top|right|bottom|left)|margin-(top|right|bottom|left))$/D',$key) && (preg_match('/^(#[a-fA-F0-9]{3,6}|[0-9.]+px|[0-9.]+|left|center|right|top|middle|bottom|[4-9]00|solid|dashed|dotted|double|italic|normal|underline|line-through|none|uppercase|lowercase|capitalize)$/D',(string)$value) || ('font-family' === $key && in_array($value,array('Arial','Arial, sans-serif','serif','sans-serif','monospace','Georgia','Times New Roman'),true))) ) { $css.=$key . ':' . $value . ';'; } }
    $body.='<div class="document-part' . (isset($block['style']['font-size'])?' has-custom-font':'') . '" style="' . esc_attr($css) . '">' . $content . '</div>';
   }
  }
  return $body;
  };
  $nodes=$layout['blocks'] ?? self::defaults();
  $body=$render( $nodes );
  if(!$legacy_rendered){$body.=self::legacy_html(($s['legacy']['sectionsHtml'] ?? '') . ($s['legacy']['productTermsHtml'] ?? ''));}
  $has_footer=static function($blocks) use (&$has_footer){foreach($blocks as $b){if('footer' === ($b['part'] ?? '') || $has_footer($b['children'] ?? array())){return true;}}return false;};
  $append_footer=!$has_footer($nodes) ? self::footer_html($s,$brand,$brand['footer_type'],$explicit_signatures) : '';
  $density_css='.density-compact{line-height:1.25}.density-compact .business h1{margin-bottom:8px}.density-compact .recipient{margin-top:14px}.density-compact .priced-items td,.density-compact .quantity-items td,.table-density-compact td{padding-top:5px}.density-compact .table-density-standard td,.table-density-standard td{padding-top:11px}.density-compact .totals{margin-top:12px}.density-compact .totals td,.density-compact .totals th{padding-top:5px}.density-compact .terms{margin-top:14px}.density-compact h2,.density-compact h3{margin-bottom:8px}.density-compact .document-footer{margin-top:16px}.density-compact .signatures{margin-top:16px}.density-compact .document-heading h1{margin-bottom:12px}';
  $paper = 'letter' === ( $paper ?? $brand['paper'] ) ? 'letter' : 'a4';
  return '<!doctype html><html><head><meta charset="UTF-8"><title>' . esc_html( $record['number'] ) . '</title><style>@page{size:' . ( 'letter' === $paper ? 'letter' : 'A4' ) . ';margin:14mm}*{box-sizing:border-box}body{margin:0;background:#edf0f3;color:#000;font:11.33px/1.45 Arial,sans-serif}.paper{width:' . ( 'letter' === $paper ? '816' : '794' ) . 'px;min-height:' . ( 'letter' === $paper ? '1056' : '1123' ) . 'px;margin:20px auto;background:#fff;padding:53px;box-shadow:0 2px 8px #0002}.header,.signatures{display:table;table-layout:fixed;width:100%;break-inside:avoid}.header>div,.signatures>div{display:table-cell;vertical-align:top}.header>div:first-child{width:60%;padding-right:40px}.header>div:last-child{width:40%;text-align:right}.business h1{font-size:16.67px;text-transform:uppercase;margin:0 0 15px;line-height:1.3}.document-heading h1{font-size:16.67px;letter-spacing:.06em;margin:0 0 19px}.document-heading p{margin-bottom:9px}.document-number{word-wrap:break-word;overflow-wrap:anywhere}h1{text-transform:uppercase}h2,h3{font-size:11.33px;font-weight:400;letter-spacing:.09em;margin:0 0 15px}p{margin:0 0 6px;overflow-wrap:anywhere}.registration{margin-top:12px}.recipient{margin-top:34px}.document-logo{max-height:90px;object-fit:contain;margin-bottom:12px}table{width:100%;border-collapse:collapse;table-layout:fixed}th,td{padding:11px 11px 0 0;vertical-align:top;overflow-wrap:anywhere;border:0}thead th{padding:0 11px 7px 0;border-bottom:1px solid #000;text-align:left;font-weight:600}th:first-child{width:55%}th:nth-child(2){width:17%}th:nth-child(3){width:12%}th:nth-child(4){width:16%}th:not(:first-child),td:not(:first-child){text-align:right}th:last-child,td:last-child{padding-right:0}.numeric{text-align:right}.quantity-items th:first-child{width:83%}.quantity-items th:nth-child(2){width:17%}.document-meta th{width:65px;text-align:left;font-weight:400;padding:0 10px 7px 0}.document-meta td{text-align:right;padding:0 0 7px}.billing th{width:17%;text-align:left;font-weight:400;padding:0 15px 7px 0}.billing td{width:83%;text-align:left;padding:0 0 7px}.billing.references{margin-bottom:6px}.totals{margin-top:25px}.totals td:first-child,.totals th:first-child{width:84%;text-align:right;padding-right:11px}.totals td:last-child,.totals th:last-child{text-align:right;width:16%}.totals th{font-weight:700}.payable span{border-bottom:3px double #000;padding-bottom:2px}.terms{margin:34px 0 0;font-size:11.33px}.terms p{margin-bottom:6px}.signatures{margin-top:30px;padding-top:11px;font-size:10.67px}.signatures>div{width:50%;padding-right:45px}.signatures>div:last-child{padding-right:0;padding-left:20px}.signature-line{height:38px;border-bottom:1px solid #777;margin-bottom:4px}.document-footer{margin-top:30px;font-size:10px}.document-footer .signatures{margin-top:0}a{color:#000;text-decoration:underline}thead{display:table-header-group}tr{break-inside:avoid}h2,h3{break-after:avoid}.document-columns .document-part{max-width:100%}.has-custom-font h1,.has-custom-font h2,.has-custom-font h3,.has-custom-font h4{font-size:inherit}hr{border:0;border-top:1px solid #000}@media print{body{background:white}.paper{width:auto;min-height:0;margin:0;padding:0;box-shadow:none}}@media screen and (max-width:830px){.paper{zoom:calc((100vw - 24px) / ' . ( 'letter' === $paper ? '816' : '794' ) . 'px)}}' . $density_css . '</style></head><body><main class="paper density-' . esc_attr($brand['density']) . '">' . $body . $append_footer . '</main></body></html>';
 }
}
