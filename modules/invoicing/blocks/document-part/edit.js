/* Native block controls; values in sample data remain bound to WooCommerce. */
(function (wp) {
 const el=wp.element.createElement, __=wp.i18n.__;
 const parts={logo:'Shared business logo',business:'Business identity',document:'Document title and reference',footer:'Shared footer',header:'Business / document header',recipient:'Customer / delivery address',references:'Custom order fields',items:'Item table',totals:'Totals and payment',sections:'Order document sections',signatures:'Received / prepared by',paragraph:'Text',heading:'Heading',spacer:'Space',separator:'Divider'};
 window.AlkamyDocumentEdit=function({attributes,setAttributes}){
  const {part,spaceAfter,text,textAlign,density,footerType}=attributes;
  return el('div',wp.blockEditor.useBlockProps(),
   el(wp.blockEditor.BlockControls,{},el(wp.blockEditor.AlignmentToolbar,{value:textAlign,onChange:textAlign=>setAttributes({textAlign})})),
   el(wp.blockEditor.InspectorControls,{},el(wp.components.PanelBody,{title:__('Document area','inline-order-editor-pro')},
    el(wp.components.SelectControl,{label:__('Area','inline-order-editor-pro'),value:part,options:Object.entries(parts).map(([value,label])=>({value,label})),onChange:part=>setAttributes({part})}),
    part==='items'?el(wp.components.SelectControl,{label:__('Line spacing','inline-order-editor-pro'),value:density,options:[{value:'inherit',label:__('Use document setting','inline-order-editor-pro')},{value:'standard',label:__('Standard','inline-order-editor-pro')},{value:'compact',label:__('Compact','inline-order-editor-pro')}],onChange:density=>setAttributes({density})}):null,
    part==='footer'?el(wp.components.SelectControl,{label:__('Footer type','inline-order-editor-pro'),value:footerType,options:[{value:'inherit',label:__('Use document setting','inline-order-editor-pro')},{value:'signature',label:__('Signature fields and text','inline-order-editor-pro')},{value:'text',label:__('Text only','inline-order-editor-pro')},{value:'company',label:__('Company details and text','inline-order-editor-pro')},{value:'none',label:__('No footer','inline-order-editor-pro')}],onChange:footerType=>setAttributes({footerType})}):null,
    el(wp.components.RangeControl,{label:__('Space after (pt)','inline-order-editor-pro'),value:spaceAfter,min:0,max:48,onChange:spaceAfter=>setAttributes({spaceAfter})}))),
   ['paragraph','heading'].includes(part)?el(wp.components.TextareaControl,{label:parts[part],value:text,onChange:text=>setAttributes({text})}):el('div',{className:'alkamy-dynamic-sample'},el(wp.serverSideRender,{block:'alkamy/document-part',attributes})));
 };
 window.AlkamyDocumentParts=parts;
})(window.wp);
