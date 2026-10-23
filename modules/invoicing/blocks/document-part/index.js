(function(wp){
 // WP 7 refreshes global styles after boot. Override only PDF editor control values,
 // using Gutenberg's setting filter rather than mutating the active theme or stores.
 const isDocumentEditor=()=>wp.data.select('core/editor')?.getCurrentPostType?.()==='alkamy_pdf_layout';
 const fonts=[{name:'Small',slug:'small',size:'11px'},{name:'Medium',slug:'medium',size:'16px'},{name:'Large',slug:'large',size:'24px'},{name:'Extra large',slug:'x-large',size:'32px'}];
 const colors=[{name:'Black',slug:'black',color:'#000000'},{name:'White',slug:'white',color:'#ffffff'},{name:'Grey',slug:'grey',color:'#666666'}];
 const spaces=Object.entries({'20':10,'30':20,'40':30,'50':40,'60':60,'70':80,'80':96}).map(([slug,size])=>({name:`${size} px`,slug,size:`${size}px`}));
 const values={
  'typography.fontSizes':fonts,'typography.fontSizes.theme':fonts,'typography.fontSizes.custom':[],'typography.fontSizes.default':[],
  'color.palette':colors,'color.palette.theme':colors,'color.palette.custom':[],'color.palette.default':[],
  'spacing.spacingSizes':spaces,'spacing.spacingSizes.theme':spaces,'spacing.spacingSizes.default':[],'spacing.spacingSizes.custom':[],
  'spacing.defaultSpacingSizes':false,'spacing.units':['px','pt','em','rem'],
  'typography.defaultFontSizes':false,'typography.customFontSize':true,'color.defaultPalette':false,
  'color.gradients':[],'color.gradients.theme':[],'color.gradients.custom':[],'color.gradients.default':[],
  'color.defaultGradients':false,'color.customGradient':false,'background.gradient':false,'background.backgroundImage':false,
  'color.duotone':[],'color.duotone.theme':[],'color.duotone.custom':[],'color.duotone.default':[],
  'color.defaultDuotone':false,'color.customDuotone':false,
  'typography.fontFamilies':[],'typography.fontFamilies.theme':[],'typography.fontFamilies.custom':[],'typography.fontFamilies.default':[],
  'typography.fluid':false,'typography.dropCap':false,'typography.writingMode':false,'typography.textColumns':false,'typography.textIndent':false,'shadow':false
 };
 wp.hooks.addFilter('blockEditor.useSetting.before','alkamy/document-settings',(current,path)=>isDocumentEditor()&&Object.prototype.hasOwnProperty.call(values,path)?values[path]:current);
 // Theme style variations print theme-specific CSS that the portable PDF compiler
 // cannot carry. Hide those choices only in this document editor window.
 wp.domReady(()=>{
  let stop;
  const clearThemeStyles=()=>{
   if(!isDocumentEditor())return;
   const registry=wp.data.select('core/blocks');
   if(typeof registry?.getBlockStyles!=='function'||typeof wp.blocks.unregisterBlockStyle!=='function')return;
   // Unsubscribe before mutating the registry so store notifications cannot re-enter.
   if(stop){const dispose=stop;stop=undefined;dispose();}
   ['core/group','core/columns','core/column','core/paragraph','core/heading','core/image','core/spacer','core/separator','core/list','core/list-item'].forEach(name=>(registry.getBlockStyles(name)||[]).forEach(style=>wp.blocks.unregisterBlockStyle(name,style.name)));
  };
  if(isDocumentEditor())clearThemeStyles();else stop=wp.data.subscribe(clearThemeStyles);
 });
 wp.blocks.registerBlockType('alkamy/document-part',{apiVersion:3,title:wp.i18n.__('Document area','inline-order-editor-pro'),category:'widgets',icon:'media-document',attributes:{part:{type:'string',default:'header'},spaceAfter:{type:'number',default:16},text:{type:'string',default:''},textAlign:{type:'string',default:'left'},density:{type:'string',default:'inherit'},footerType:{type:'string',default:'inherit'}},supports:{html:false,color:{text:true,background:true},spacing:{margin:true,padding:true},typography:{fontSize:true}},edit:window.AlkamyDocumentEdit,save:window.AlkamyDocumentSave});
 Object.entries(window.AlkamyDocumentParts).forEach(([part,title])=>wp.blocks.registerBlockVariation('alkamy/document-part',{name:part,title,attributes:{part},isActive:['part'],scope:['inserter']}));
 if(wp.plugins && wp.editor && wp.editor.PluginDocumentSettingPanel){
  wp.plugins.registerPlugin('alkamy-layout-preview',{render:function(){
   const post=wp.data.useSelect(select=>({id:select('core/editor').getCurrentPostId(),type:select('core/editor').getCurrentPostType(),dirty:select('core/editor').isEditedPostDirty()}),[]);
   if(post.type!=='alkamy_pdf_layout')return null;
   const el=wp.element.createElement,url=new URL(window.alkamyLayoutEditor.previewUrl);url.searchParams.set('layout_id',post.id);
   return el(wp.editor.PluginDocumentSettingPanel,{name:'alkamy-layout-preview',title:wp.i18n.__('PDF layout preview','inline-order-editor-pro')},
    el('p',{},wp.i18n.__('Save or publish first. The preview uses sample order data and the saved layout. Assign layouts and your logo under WooCommerce → Invoicing.','inline-order-editor-pro')),
    el('a',{href:url.href,target:'_blank',rel:'noopener'},wp.i18n.__('Preview saved layout ↗','inline-order-editor-pro')),
    post.dirty?el('p',{role:'status'},wp.i18n.__('You have unsaved layout changes.','inline-order-editor-pro')):null);
  }});
 }
})(window.wp);
