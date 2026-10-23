<?php
/** Private PDF layout editor and branding controls. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Layout_Editor {
 public static function init() {
  add_action( 'init', array( __CLASS__, 'register' ) );
  add_filter( 'rest_pre_dispatch', array( __CLASS__, 'rest_access' ), 10, 3 );
  add_filter( 'block_editor_settings_all', array( __CLASS__, 'editor_settings' ), 10, 2 );
  add_action( 'edit_form_after_title', array( __CLASS__, 'editor_help' ) );
  add_action( 'admin_post_alkamy_document_brand', array( __CLASS__, 'save' ) );
  add_action( 'admin_post_alkamy_layout_sample', array( __CLASS__, 'sample' ) );
  add_filter( 'allowed_block_types_all', static function ( $allowed, $context ) { return isset( $context->post ) && 'alkamy_pdf_layout' === $context->post->post_type ? self::allowed_blocks() : $allowed; }, 10, 2 );
  add_action( 'admin_enqueue_scripts', static function ( $hook ) {
   // Read-only screen navigation; no write is performed by loading editor assets.
   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
   if ( 'woocommerce_page_alkamy-invoicing' === $hook ) { wp_enqueue_media();wp_enqueue_script( 'alkamy-brand', plugins_url( 'assets/brand.js', ALKAMY_INVOICING_FILE ), array( 'jquery' ), '1.2.1', true ); }
  } );
 }
 public static function register() {
  $caps = array_fill_keys( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ), 'manage_woocommerce' );
  register_post_type( 'alkamy_pdf_layout', array( 'labels'=>array( 'name'=>__( 'PDF layouts', 'inline-order-editor-pro' ), 'singular_name'=>__( 'PDF layout', 'inline-order-editor-pro' ), 'add_new_item'=>__( 'Add PDF layout', 'inline-order-editor-pro' ), 'edit_item'=>__( 'Edit PDF layout', 'inline-order-editor-pro' ) ), 'public'=>false, 'show_ui'=>true, 'show_in_menu'=>'woocommerce', 'show_in_rest'=>true, 'supports'=>array( 'title', 'editor', 'revisions' ), 'capabilities'=>$caps, 'map_meta_cap'=>false, 'template'=>self::template() ) );
  $base = plugins_url( 'blocks/document-part/', ALKAMY_INVOICING_FILE );
  wp_register_script( 'alkamy-document-edit', $base . 'edit.js', array( 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ), '1.2.1', true );
  wp_register_script( 'alkamy-document-save', $base . 'save.js', array(), '1.2.1', true );
  wp_register_script( 'alkamy-document-block', $base . 'index.js', array( 'wp-blocks', 'alkamy-document-edit', 'alkamy-document-save', 'wp-i18n', 'wp-plugins', 'wp-editor', 'wp-data', 'wp-hooks', 'wp-dom-ready' ), '1.2.1', true );
  wp_localize_script('alkamy-document-block','alkamyLayoutEditor',array('previewUrl'=>wp_nonce_url(admin_url('admin-post.php?action=alkamy_layout_sample'),'alkamy_layout_sample')));
  register_block_type( dirname( __DIR__ ) . '/blocks/document-part', array( 'render_callback'=>array( __CLASS__, 'render' ) ) );
  register_block_pattern_category( 'alkamy-documents', array( 'label'=>__( 'PDF document layouts', 'inline-order-editor-pro' ) ) );
  foreach ( array( 'folio-layout'=>array( 'Folio document', self::pattern() ), 'terms'=>array( 'Terms section', '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">TERMS AND CONDITIONS</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Add your approved terms here.</p><!-- /wp:paragraph -->' ), 'signatures'=>array( 'Signature spaces', '<!-- wp:alkamy/document-part {"part":"signatures"} /-->' ) ) as $slug=>$pattern ) { register_block_pattern( 'alkamy/' . $slug, array( 'title'=>$pattern[0], 'categories'=>array( 'alkamy-documents' ), 'postTypes'=>array( 'alkamy_pdf_layout' ), 'content'=>$pattern[1] ) ); }
 } 
 public static function rest_access( $result, $server, $request ) {
  if ( preg_match( '#^/wp/v2/alkamy_pdf_layout(?:/|$)#', $request->get_route() ) && ! current_user_can( 'manage_woocommerce' ) ) { return new WP_Error( 'alkamy_layout_access', __( 'PDF layouts are private store documents.', 'inline-order-editor-pro' ), array( 'status'=>403 ) ); }
  return $result;
 }
 public static function allowed_blocks() { return array( 'alkamy/document-part', 'core/group','core/columns','core/column','core/paragraph','core/heading','core/image','core/spacer','core/separator','core/list','core/list-item','core/block' ); }
 public static function template() {
  return array( array( 'core/columns', array(), array( array( 'core/column', array( 'width'=>'60%' ), array( array( 'alkamy/document-part', array( 'part'=>'logo', 'spaceAfter'=>12 ) ), array( 'alkamy/document-part', array( 'part'=>'business' ) ) ) ), array( 'core/column', array( 'width'=>'40%' ), array( array( 'alkamy/document-part', array( 'part'=>'document', 'textAlign'=>'right' ) ) ) ) ) ), array( 'alkamy/document-part',array( 'part'=>'recipient','spaceAfter'=>24 ) ), array( 'alkamy/document-part',array( 'part'=>'references','spaceAfter'=>24 ) ), array( 'alkamy/document-part',array( 'part'=>'items' ) ), array( 'alkamy/document-part',array( 'part'=>'totals' ) ), array( 'alkamy/document-part',array( 'part'=>'sections' ) ), array( 'alkamy/document-part',array( 'part'=>'footer' ) ) );
 }
 public static function pattern() {
  return '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column {"width":"60%"} --><div class="wp-block-column" style="flex-basis:60%"><!-- wp:alkamy/document-part {"part":"logo","spaceAfter":12} /--><!-- wp:alkamy/document-part {"part":"business"} /--></div><!-- /wp:column --><!-- wp:column {"width":"40%"} --><div class="wp-block-column" style="flex-basis:40%"><!-- wp:alkamy/document-part {"part":"document","textAlign":"right"} /--></div><!-- /wp:column --></div><!-- /wp:columns -->' . implode( '', array_map( static function ( $part ) { return '<!-- wp:alkamy/document-part {"part":"' . $part . '"} /-->'; }, array( 'recipient','references','items','totals','sections','footer' ) ) );
 } 
 public static function editor_settings( $settings, $context ) {
  if ( ! isset( $context->post ) || 'alkamy_pdf_layout' !== $context->post->post_type ) { return $settings; }
  // Physical PDF styling must not depend on active theme presets or remote fonts.
  $settings['disableCustomColors']=false;$settings['disableCustomFontSizes']=false;$settings['disableCustomGradients']=true;$settings['gradients']=array();
  $settings['colors']=array( array( 'name'=>'Black','slug'=>'black','color'=>'#000000' ),array( 'name'=>'White','slug'=>'white','color'=>'#ffffff' ),array( 'name'=>'Grey','slug'=>'grey','color'=>'#666666' ) );
  $settings['fontSizes']=array( array('name'=>'Small','slug'=>'small','size'=>11),array('name'=>'Medium','slug'=>'medium','size'=>16),array('name'=>'Large','slug'=>'large','size'=>24) );
  $settings['fontSizes'][]=array('name'=>'Extra large','slug'=>'x-large','size'=>32);
  $settings['enableCustomUnits']=array('px','pt','em','rem');
  $spacing=array('20'=>10,'30'=>20,'40'=>30,'50'=>40,'60'=>60,'70'=>80,'80'=>96);$settings['spacingSizes']=array();
  foreach($spacing as $slug=>$size){$settings['spacingSizes'][]=array('name'=>$size . ' px','slug'=>(string)$slug,'size'=>$size . 'px');}
  if(!isset($settings['__experimentalFeatures']) || !is_array($settings['__experimentalFeatures'])){$settings['__experimentalFeatures']=array();}
  $features=&$settings['__experimentalFeatures'];
  $features['color']['palette']=array('theme'=>$settings['colors']);$features['color']['defaultPalette']=false;
  $features['color']['gradients']=array();$features['color']['customGradient']=false;$features['color']['duotone']=array();$features['color']['customDuotone']=false;
  $features['typography']['fontSizes']=array('theme'=>array_map(static function($v){$v['size'].='px';return $v;},$settings['fontSizes']));
  $features['typography']['defaultFontSizes']=false;$features['typography']['fluid']=false;$features['typography']['fontFamilies']=array();
  $features['typography']['fontFamily']=false;$features['typography']['dropCap']=false;$features['typography']['writingMode']=false;$features['typography']['textColumns']=false;$features['typography']['textIndent']=false;
  $features['spacing']['spacingSizes']=array('theme'=>$settings['spacingSizes']);$features['spacing']['defaultSpacingSizes']=false;$features['spacing']['units']=array('px','pt','em','rem');
  $variables='--wp--preset--font-size--small:11px;--wp--preset--font-size--medium:16px;--wp--preset--font-size--large:24px;--wp--preset--font-size--x-large:32px;--wp--preset--color--black:#000000;--wp--preset--color--white:#ffffff;--wp--preset--color--grey:#666666;';
  foreach($spacing as $slug=>$size){$variables.='--wp--preset--spacing--' . $slug . ':' . $size . 'px;';}

  if ( ! isset($settings['styles']) || ! is_array($settings['styles']) ) { $settings['styles']=array(); }
  $settings['styles'][]=array( 'css'=>'.editor-styles-wrapper{' . $variables . 'font-family:Arial,sans-serif;color:#000;font-size:11.33px;line-height:1.45}.editor-styles-wrapper .is-root-container{max-width:688px;margin:auto}.editor-styles-wrapper h1{font-size:16.67px}.editor-styles-wrapper h2,.editor-styles-wrapper h3{font-size:11.33px}.editor-styles-wrapper p{margin:0 0 6px}.editor-styles-wrapper .wp-block{max-width:none}' );
  return $settings;
 }
 public static function render( $attrs ) {
  if ( ! current_user_can( 'manage_woocommerce' ) ) { return ''; }
  $part = $attrs['part'] ?? 'header';
  if ( ! in_array( $part, array_merge( Alkamy_Layout::PARTS, array( 'business','document','logo','footer','paragraph', 'heading', 'spacer', 'separator' ) ), true ) ) { return ''; }
  $record = Alkamy_Layout::sample();$record['snapshot']['layout']['blocks'] = Alkamy_Layout::compile( array( array( 'blockName'=>'alkamy/document-part', 'attrs'=>$attrs ) ) );
  if('footer' !== $part){$record['snapshot']['layout']['brand']['footer'] = '';$record['snapshot']['layout']['brand']['footer_type'] = 'none';}
  $html = Alkamy_Layout::html( $record );if(!preg_match('/<main\b[^>]*>/', $html, $match, PREG_OFFSET_CAPTURE)){return '';}$start=$match[0][1]+strlen($match[0][0]);$end = strpos( $html, '</main>', $start );
  // compile() bounds block text/styles; html() escapes each output context. Keep the composed preview markup.
  // nosemgrep: audit.php.wp.security.xss.block-attr
  return '<div ' . get_block_wrapper_attributes( array( 'class'=>'alkamy-document-canvas density-' . $record['snapshot']['layout']['brand']['density'] ) ) . '><small>' . esc_html__( 'Sample data', 'inline-order-editor-pro' ) . '</small>' . substr( $html, $start, $end - $start ) . '</div>';
 }
 public static function settings() {
  $brand = Alkamy_Layout::brand();$identity=Alkamy_Layout::identity();$map = get_option( 'alkamy_layout_templates', array() );
  $templates = get_posts( array( 'post_type'=>'alkamy_pdf_layout', 'post_status'=>'publish', 'numberposts'=>100, 'orderby'=>'title', 'order'=>'ASC' ) );
  echo '<section class="alkamy-card" id="document-design"><h2>' . esc_html__( 'Document branding and PDF layouts', 'inline-order-editor-pro' ) . '</h2><p>' . esc_html__( 'Folio layouts use uppercase mastheads, borderless items and signature spaces. Packing slips and delivery orders omit monetary values. Preview uses sample data.', 'inline-order-editor-pro' ) . '</p><div class="alkamy-design-columns"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="alkamy_document_brand">';
  wp_nonce_field( 'alkamy_document_brand' );
  echo '<h3>' . esc_html__('Company details','inline-order-editor-pro') . '</h3>';
  foreach(array('name'=>'Legal business name','address'=>'Business address','uen'=>'UEN','gst'=>'GST registration number','peppolId'=>'Peppol ID') as $key=>$label){if(in_array($key,array('uen','peppolId'),true) && !Alkamy_Workspace::singapore_store()){continue;}echo '<label>' . esc_html($label);if('address' === $key){echo '<textarea name="identity[address]" rows="3">' . esc_textarea($identity[$key]) . '</textarea>';}else{echo '<input name="identity[' . esc_attr($key) . ']" value="' . esc_attr($identity[$key]) . '">';}echo '</label>';}
  echo '<label>' . esc_html__( 'Logo', 'inline-order-editor-pro' ) . '<input type="hidden" id="alkamy-logo-id" name="brand[logo_id]" value="' . esc_attr( $brand['logo_id'] ) . '"><span id="alkamy-logo-name">' . esc_html( $brand['logo_id'] ? get_the_title( $brand['logo_id'] ) : __( 'No logo selected', 'inline-order-editor-pro' ) ) . '</span></label><p><button class="button" type="button" id="alkamy-logo-choose">' . esc_html__( 'Choose logo', 'inline-order-editor-pro' ) . '</button> <button class="button" type="button" id="alkamy-logo-clear">' . esc_html__( 'Remove logo', 'inline-order-editor-pro' ) . '</button></p><p class="description">' . esc_html__( 'PNG or JPEG, up to 256 KB and 2000 × 2000 pixels. Logo is embedded in document snapshots.', 'inline-order-editor-pro' ) . '</p>';
  $logo=Alkamy_Layout::raster($brand['logo_id']);
  echo '<img id="alkamy-logo-preview" alt="' . esc_attr__('Selected document logo','inline-order-editor-pro') . '" src="' . esc_attr($logo) . '" style="max-width:240px;max-height:80px;object-fit:contain"' . ($logo ? '' : ' hidden') . '>';
  foreach ( array( 'logo_width'=>'Logo width (40–180 pt)', 'phone'=>'Business phone', 'email'=>'Business email', 'website'=>'Website', 'prepared_by'=>'Prepared by' ) as $key=>$label ) { echo '<label>' . esc_html( $label ) . '<input name="brand[' . esc_attr( $key ) . ']" value="' . esc_attr( $brand[ $key ] ) . '"' . ( 'logo_width' === $key ? ' type="number" min="40" max="180"' : '' ) . '></label>'; }
  echo '<label>' . esc_html__( 'Paper size', 'inline-order-editor-pro' ) . '<select name="brand[paper]"><option value="a4"' . selected( $brand['paper'], 'a4', false ) . '>A4</option><option value="letter"' . selected( $brand['paper'], 'letter', false ) . '>US Letter</option></select></label><label>' . esc_html__( 'Footer text (optional)', 'inline-order-editor-pro' ) . '<textarea name="brand[footer]">' . esc_textarea( $brand['footer'] ) . '</textarea></label>';
  foreach(array('density'=>array('label'=>'Line spacing','choices'=>array('standard'=>'Standard','compact'=>'Compact')),'footer_type'=>array('label'=>'Footer type','choices'=>array('signature'=>'Signature fields and text','text'=>'Text only','company'=>'Company details and text','none'=>'No footer'))) as $key=>$control){echo '<label>' . esc_html($control['label']) . '<select name="brand[' . esc_attr($key) . ']">';foreach($control['choices'] as $value=>$label){echo '<option value="' . esc_attr($value) . '"' . selected($brand[$key],$value,false) . '>' . esc_html($label) . '</option>';}echo '</select></label>';}
  echo '<p class="description">' . esc_html__('Compact spacing leaves more room for document sections. Custom layouts can override item-table spacing and footer type in their block settings.','inline-order-editor-pro') . '</p>';
  foreach ( Alkamy_Documents::TYPES as $type ) {
   echo '<label>' . esc_html( ucwords( str_replace( '_', ' ', $type ) ) ) . '<select name="layouts[' . esc_attr( $type ) . ']"><option value="0">' . esc_html__( 'Folio house layout', 'inline-order-editor-pro' ) . '</option>';
   foreach ( $templates as $post ) { echo '<option value="' . esc_attr( $post->ID ) . '"' . selected( $map[ $type ] ?? 0, $post->ID, false ) . '>' . esc_html( $post->post_title ) . '</option>'; }
   echo '</select></label>';
  }
  echo '<p><a href="' . esc_url( admin_url( 'post-new.php?post_type=alkamy_pdf_layout' ) ) . '">' . esc_html__( 'Create a custom block layout ↗', 'inline-order-editor-pro' ) . '</a> · <a href="' . esc_url( admin_url( 'edit.php?post_type=alkamy_pdf_layout' ) ) . '">' . esc_html__( 'Edit PDF layouts ↗', 'inline-order-editor-pro' ) . '</a></p><button class="button button-primary">' . esc_html__( 'Save document design', 'inline-order-editor-pro' ) . '</button></form><div><label>' . esc_html__( 'Preview document', 'inline-order-editor-pro' ) . '<select id="alkamy-design-type">';
  foreach ( Alkamy_Documents::TYPES as $type ) { echo '<option value="' . esc_attr( $type ) . '">' . esc_html( ucwords( str_replace( '_', ' ', $type ) ) ) . '</option>'; }
  $url = wp_nonce_url( admin_url( 'admin-post.php?action=alkamy_layout_sample' ), 'alkamy_layout_sample' );
  echo '</select></label><p class="description">' . esc_html__( 'Save changes to refresh this preview. Download PDFs from each order.', 'inline-order-editor-pro' ) . '</p><iframe title="' . esc_attr__( 'Saved document design preview', 'inline-order-editor-pro' ) . '" id="alkamy-design-preview" data-url="' . esc_url( $url ) . '" src="' . esc_url( $url ) . '" sandbox="" style="width:100%;height:680px;border:1px solid #dcdcde"></iframe></div></div></section>';
 }
 public static function editor_help( $post ) {
  if ( 'alkamy_pdf_layout' !== $post->post_type || ! current_user_can( 'manage_woocommerce' ) ) { return; }
  echo '<p class="description">' . esc_html__( 'Arrange order-data blocks with Groups and Columns. Add headings, paragraphs, lists and local PNG/JPEG images. Insert PDF document patterns, then edit them freely. Publish a layout and assign it in Invoicing → Document branding. Revisions preserve earlier designs. Only local PNG/JPEG images and safe document blocks enter PDFs. Reusable patterns require edit access. Large content in columns stacks vertically for safe PDF pagination.', 'inline-order-editor-pro' ) . '</p>';
 }
 public static function save() {
  if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Access denied.', 'inline-order-editor-pro' ), '', array( 'response'=>403 ) ); }
  check_admin_referer( 'alkamy_document_brand' );
  $brand = Alkamy_Layout::clean_brand( map_deep( wp_unslash( $_POST['brand'] ?? array() ), 'sanitize_textarea_field' ) );
  if ( $brand['logo_id'] ) {
   if ( ! Alkamy_Layout::raster( $brand['logo_id'] ) ) { wp_die( esc_html__( 'Choose a PNG or JPEG logo within the displayed limits.', 'inline-order-editor-pro' ), '', array( 'response'=>400 ) ); }
  }
  $map = array();$input = map_deep( wp_unslash( $_POST['layouts'] ?? array() ), 'absint' );
  foreach ( Alkamy_Documents::TYPES as $type ) { $id = absint( $input[ $type ] ?? 0 );$post = $id ? get_post( $id ) : null;if ( $post && ( 'alkamy_pdf_layout' !== $post->post_type || 'publish' !== $post->post_status || ! current_user_can( 'edit_post', $id ) ) ) { wp_die( esc_html__( 'Choose a published PDF layout you can edit.', 'inline-order-editor-pro' ), '', array( 'response'=>400 ) ); }$missing=$post ? Alkamy_Layout::validation_errors($type,Alkamy_Layout::preset($type,$id)) : array();if($missing){wp_die(esc_html('This layout is missing required order areas for ' . str_replace('_',' ',$type) . ': ' . implode(', ',$missing)), '', array('response'=>400));}$map[ $type ] = $post ? $id : 0; }
  if(isset($_POST['identity']) && is_array($_POST['identity'])){$input=map_deep(wp_unslash($_POST['identity']),'sanitize_textarea_field');$identity=Alkamy_Layout::identity();foreach(array('name','address','uen','gst','peppolId') as $key){if(!array_key_exists($key,$input)){continue;}$value=isset($input[$key]) && is_scalar($input[$key]) ? $input[$key] : '';$identity[$key]='address' === $key ? sanitize_textarea_field($value) : sanitize_text_field($value);}update_option('alkamy_identity',$identity,false);}
  update_option( 'alkamy_document_brand', $brand, false );update_option( 'alkamy_layout_templates', $map, false );
  wp_safe_redirect( Alkamy_Admin::url() . '#document-design' );exit;
 }
 public static function sample() {
  if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Access denied.', 'inline-order-editor-pro' ), '', array( 'response'=>403 ) ); }
  check_admin_referer( 'alkamy_layout_sample' );
  $type = sanitize_key( $_GET['type'] ?? 'invoice' );
  $layout_id=absint($_GET['layout_id'] ?? 0);
  if($layout_id && !current_user_can('edit_post',$layout_id)){wp_die(esc_html__('Access denied.','inline-order-editor-pro'),'',array('response'=>403));}
  $record=Alkamy_Layout::sample($type);
  if($layout_id){$record['snapshot']['layout']=Alkamy_Layout::preset($type,$layout_id,true);}
  nocache_headers();header( 'Content-Type: text/html; charset=UTF-8' );
  echo Alkamy_Layout::html( $record ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Document renderer escapes each text/attribute and permits only bounded raster logo data.
  exit;
 }
}
