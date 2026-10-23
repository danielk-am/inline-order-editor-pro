/* Native WordPress controls keep settings in the surrounding nonce-protected form. */
document.addEventListener('DOMContentLoaded',()=>{
 const root=document.getElementById('alkamy-invoice-delivery-controls'),config=window.alkamyInvoiceSettings;
 if(!root||!config||!window.wp?.element||!window.wp?.components)return;
 const {createElement:el,useState,createRoot}=wp.element,{ToggleControl,CheckboxControl,TextControl}=wp.components;
 function Settings(){
  const [values,setValues]=useState(config.values),change=(key,value)=>setValues(old=>({...old,[key]:value}));
  return el(wp.element.Fragment,null,
   el(ToggleControl,{label:config.labels.enabled,name:'delivery[enabled]',value:'1',checked:!!values.enabled,onChange:value=>change('enabled',value),__nextHasNoMarginBottom:true}),
   ...Object.entries(config.emails).map(([id,label])=>el(CheckboxControl,{key:id,label,name:'delivery[email_ids][]',value:id,checked:values.email_ids.includes(id),onChange:checked=>change('email_ids',checked?[...values.email_ids,id]:values.email_ids.filter(v=>v!==id)),__nextHasNoMarginBottom:true})),
   el(TextControl,{label:config.labels.prefix,name:'delivery[numbering_prefix]',value:values.numbering_prefix,maxLength:60,onChange:value=>change('numbering_prefix',value),__nextHasNoMarginBottom:true}),
   el(TextControl,{label:config.labels.padding,name:'delivery[number_padding]',type:'number',min:2,max:10,value:values.number_padding,onChange:value=>change('number_padding',value),__nextHasNoMarginBottom:true})
  );
 }
 createRoot(root).render(el(Settings));
});
