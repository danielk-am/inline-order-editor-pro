document.addEventListener('ioefw:suggestions',event=>{
 const {options,term}=event.detail,index=options.findIndex(option=>option.type==='custom');
 if(index>=0)options.splice(index+1,0,{type:'custom_product',label:`Add "${term}" as a new item and save as a product`,detail:'Saves a draft product for review.'});
});
/* Shared section controls and isolated preview submissions. No nested order forms. */
document.addEventListener('DOMContentLoaded',()=>{
 const reindex=root=>root.querySelectorAll('.alkamy-section').forEach((row,i)=>row.querySelectorAll('[name]').forEach(el=>{el.name=el.name.replace(/(alkamy_)?sections\[[^\]]+\]/,(_,prefix)=>`${prefix||''}sections[${i}]`);}));
 const positionMap=row=>{
  const title=row.querySelector('.alkamy-section-title');if(title)title.textContent=row.querySelector('[data-field=heading]')?.value||(row.querySelector('[name$="[kind]"]')?'Order terms':'Additional information');
 };

 const add=(root,preset)=>{
  const template=root.querySelector('.alkamy-row-template'),list=root.querySelector('.alkamy-sections');if(!template||!list)return;
  if(list.children.length>=31){const status=root.querySelector('.alkamy-section-status');if(status)status.textContent='Maximum 31 sections. Remove a section before adding another.';return;}
  const row=template.content.firstElementChild.cloneNode(true);
  if(preset){row.querySelector('[data-field="heading"]').value=preset.heading;row.querySelector('[data-field="body"]').value=preset.body;row.querySelectorAll('[type="checkbox"]').forEach(el=>{el.checked=preset.documents.includes(el.value);});}
  if(row.matches('details'))row.open=true;
  list.append(row);reindex(root);positionMap(row);row.querySelector('[data-field="heading"]').focus();
 };

 const termsPreset=document.querySelector('#alkamy-terms-preset'),termsText=document.querySelector('#ioefw-order-terms-text');
 if(termsPreset&&termsText){
  const box=termsText.closest('.inside');if(box)box.prepend(termsPreset.closest('.alkamy-terms-presets'));
  termsPreset.addEventListener('change',()=>{
   if(!termsPreset.value)return;
   if(termsText.value.trim()&&termsText.value!==termsPreset.value&&!confirm('Replace this order’s current terms with the selected preset?')){termsPreset.value='';return;}
   termsText.value=termsPreset.value;termsText.dispatchEvent(new Event('input',{bubbles:true}));termsText.focus();termsPreset.value='';
  });
 }
 const header=document.querySelector('#order_data'),mode=document.querySelector('#alkamy-invoice-mode-control');
 if(header){
  const links=header.querySelector('.alkamy-order-links'),payment=header.querySelector('.wc-order-status label a');
  if(links&&payment){payment.textContent=payment.textContent.replace(/\s*[→↗]\s*$/u,'')+' ↗';payment.target='_blank';payment.rel='noopener';payment.title='Customer payment page (opens in a new tab)';links.prepend(payment);}
 }
 if(mode && window.wp?.element && window.wp?.components){
  const el=wp.element.createElement;
  function InvoiceModeToggle(){
   const [enabled,setEnabled]=wp.element.useState(mode.dataset.enabled==='1'),[busy,setBusy]=wp.element.useState(false),dirty=wp.element.useRef(false);
   wp.element.useEffect(()=>{
    const form=mode.closest('form'),changed=e=>{if(!e.target.closest('.alkamy-mode-toggle'))dirty.current=true;},clicked=e=>{if(e.target.closest('.alkamy-add,.alkamy-remove,.alkamy-up,.alkamy-field-add,.alkamy-field-remove,.alkamy-field-up'))dirty.current=true;};
    form?.addEventListener('input',changed);form?.addEventListener('change',changed);form?.addEventListener('click',clicked);
    return()=>{form?.removeEventListener('input',changed);form?.removeEventListener('change',changed);form?.removeEventListener('click',clicked);};
   },[]);
   return el(wp.components.ToggleControl,{label:mode.dataset.label,checked:enabled,disabled:busy,__nextHasNoMarginBottom:true,onChange:async next=>{
    if(dirty.current&&!confirm('Switching layouts reloads this page. Unsaved changes will be lost. Switch anyway?'))return;
    const previous=enabled,status=document.querySelector('.alkamy-mode-status');setEnabled(next);setBusy(true);status.textContent='Switching layout…';
    try{
     const response=await fetch(mode.dataset.endpoint,{method:'POST',credentials:'same-origin',body:new URLSearchParams({action:'alkamy_invoice_mode',nonce:mode.dataset.nonce,enabled:next?'1':'0'})});
     const result=await response.json();if(!response.ok||!result.success)throw new Error('Preference was not saved.');window.location.reload();
    }catch(error){setEnabled(previous);setBusy(false);status.textContent='Could not switch layout. Please try again.';}
   }});
  }
  wp.element.createRoot(mode).render(el(InvoiceModeToggle));
 }
 if(header&&document.body.classList.contains('alkamy-order-screen')){
  header.classList.add('alkamy-inline-header');
  const custom=header.querySelector('.alkamy-custom-fields');
  if(custom){header.append(custom);const identifiers=header.querySelector('.alkamy-invoicenow-details');if(identifiers)custom.before(identifiers);}
  header.querySelectorAll('.order_data_column').forEach(column=>{
   const edit=column.querySelector('div.edit_address');
   if(edit){edit.style.display='block';column.classList.add('alkamy-inline-address');}
  });
  const shipping=header.querySelector('#_shipping_first_name')?.closest('.order_data_column');
  if(shipping){
   const toggle=document.createElement('label');toggle.className='alkamy-shipping-toggle';
   const checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.name='alkamy_shipping_same_billing';checkbox.value='1';
   checkbox.checked=header.querySelector('[name="alkamy_shipping_same_saved"]')?.value==='1';
   toggle.append(checkbox,document.createTextNode(' Shipping address is the same as billing'));
   shipping.querySelector('h3').after(toggle);
   const note=shipping.querySelector('#excerpt')?.closest('.form-field');if(note)shipping.append(note);
   const syncShipping=()=>{
    if(!checkbox.checked)return;
    // Native Woo tax recalculation reads these inputs before Update order is pressed.
    for(const key of ['first_name','last_name','company','address_1','address_2','city','postcode','country','state','phone']){
     const source=header.querySelector('#_billing_'+key),target=header.querySelector('#_shipping_'+key);
     if(source&&target&&target.value!==source.value){target.value=source.value;if(window.jQuery)window.jQuery(target).trigger('change');}
    }
   };
   const change=()=>{shipping.classList.toggle('alkamy-same-shipping',checkbox.checked);syncShipping();};checkbox.addEventListener('change',change);
   header.addEventListener('input',e=>{if(e.target.name?.startsWith('_billing_'))syncShipping();});
   if(window.jQuery)window.jQuery(header).on('change.alkamyShipping','[name^="_billing_"]',syncShipping);
   else header.addEventListener('change',e=>{if(e.target.name?.startsWith('_billing_'))syncShipping();});
   change();
  }
  header.querySelectorAll('.order_data_column .form-field').forEach(row=>{const value=document.createElement('div');value.className='alkamy-header-value';[...row.childNodes].filter(node=>node.nodeName!=='LABEL').forEach(node=>value.append(node));row.append(value);});
  const customer=header.querySelector('.wc-customer-user'),customerLinks=customer?.querySelectorAll('label a');
  if(customerLinks?.length){
   const links=document.createElement('div');links.className='alkamy-customer-links';
   customerLinks.forEach(link=>links.append(link));customer.querySelector('.alkamy-header-value').append(links);
  }

 }
 const reindexFields=()=>document.querySelectorAll('.alkamy-custom-field').forEach((row,i)=>row.querySelectorAll('[name]').forEach(el=>{el.name=el.name.replace(/alkamy_custom_fields\[[^\]]+\]/,`alkamy_custom_fields[${i}]`);}));
 document.querySelectorAll('.alkamy-custom-fields').forEach(root=>root.addEventListener('click',e=>{
  const button=e.target.closest('button');if(!button)return;
  if(button.matches('.alkamy-field-add')){if(root.querySelectorAll('.alkamy-custom-field').length>=20)return;const row=root.querySelector('template').content.firstElementChild.cloneNode(true);root.querySelector('.alkamy-field-rows').append(row);reindexFields();row.querySelector('input').focus();}
  if(button.matches('.alkamy-field-remove'))button.closest('.alkamy-custom-field').remove();
  reindexFields();changed();
 }));
 document.querySelectorAll('.alkamy-field-rows,.alkamy-sections').forEach(list=>{
  list.addEventListener('click',e=>{if(e.target.closest('.alkamy-section-drag'))e.preventDefault();});
  const sections=list.matches('.alkamy-sections'),handleSelector=sections?'.alkamy-section-drag':'.alkamy-field-drag',rowSelector=sections?'.alkamy-section':'.alkamy-custom-field';
  let drag;
  const announce=row=>{
   if(sections)reindex(list.parentElement);else reindexFields();
   const position=[...list.children].indexOf(row)+1,label=(sections?row.querySelector('[data-field=heading]'):row.querySelector('input')).value||(sections?'Section':'Field');
   list.parentNode.querySelector(sections?'.alkamy-section-status':'.alkamy-reorder-status').textContent=`${label} moved to position ${position} of ${list.children.length}.`;
   row.dispatchEvent(new Event('input',{bubbles:true}));
  };
  list.addEventListener('keydown',e=>{
   const handle=e.target.closest(handleSelector);if(!handle||!['ArrowUp','ArrowDown'].includes(e.key))return;
   e.preventDefault();const row=handle.closest(rowSelector),other=e.key==='ArrowUp'?row.previousElementSibling:row.nextElementSibling;
   if(other){list.insertBefore(row,e.key==='ArrowUp'?other:other.nextElementSibling);handle.focus();announce(row);}
  });
  list.addEventListener('pointerdown',e=>{
   const handle=e.target.closest(handleSelector);if(!handle||e.button!==0||drag)return;
   const row=handle.closest(rowSelector);drag={row,handle,next:row.nextElementSibling,id:e.pointerId,y:e.clientY,moved:false};
   handle.setPointerCapture(e.pointerId);handle.focus();row.classList.add('alkamy-field-dragging');e.preventDefault();
  });
  list.addEventListener('pointermove',e=>{
   if(!drag||e.pointerId!==drag.id||Math.abs(e.clientY-drag.y)<5)return;
   if(sections){if(e.clientY<90)window.scrollBy(0,-25);else if(e.clientY>window.innerHeight-70)window.scrollBy(0,25);}
   const target=document.elementFromPoint(e.clientX,e.clientY)?.closest(rowSelector);
   if(!target||target===drag.row||target.parentNode!==list)return;
   const rect=target.getBoundingClientRect();list.insertBefore(drag.row,e.clientY<rect.top+rect.height/2?target:target.nextElementSibling);drag.handle.setPointerCapture(drag.id);drag.moved=true;
  });
  const finish=(e,cancel)=>{
   if(!drag||e.pointerId!==drag.id)return;const current=drag;drag=null;
   if(cancel)list.insertBefore(current.row,current.next?.parentNode===list?current.next:null);
   current.row.classList.remove('alkamy-field-dragging');if(current.handle.hasPointerCapture(e.pointerId))current.handle.releasePointerCapture(e.pointerId);
   if(current.moved&&!cancel)announce(current.row);
  };
  list.addEventListener('pointerup',e=>finish(e,false));list.addEventListener('pointercancel',e=>finish(e,true));
 });
 const preview=document.querySelector('.alkamy-preview'),editor=document.querySelector('.alkamy-editor');
 let timer,issued=false,request,previewUrl;
 const submit=(operation,{type='',documentId='',format='',refundId=''}={})=>{
  if(!preview)return;
  const form=document.createElement('form');form.method='post';form.action=preview.dataset.endpoint;form.hidden=true;
  const field=(name,value)=>{const input=document.createElement('input');input.type='hidden';input.name=name;input.value=value;form.append(input);};
  field('action','alkamy_action');field('_wpnonce',preview.dataset.nonce);field('order_id',preview.dataset.order);field('operation',operation);field('document_id',documentId);
  if(type)field('type',type);if(refundId)field('refund_id',refundId);if(preview.querySelector('.alkamy-preview-layout'))field('layout_id',preview.querySelector('.alkamy-preview-layout').value);
  if(editor?.dataset.embedded==='1')field('return_to_order','1');
  if(editor){reindex(editor);editor.querySelectorAll('input[name],textarea[name],select[name]').forEach(el=>{if(el.disabled||(['checkbox','radio'].includes(el.type)&&!el.checked))return;field(el.name.replace(/^alkamy_sections\[/,'sections['),el.value);});}
  document.querySelectorAll('.alkamy-invoicenow-details input[name]').forEach(el=>field(el.name,el.value));
  reindexFields();document.querySelectorAll('.alkamy-custom-fields input[name]').forEach(el=>{if(el.type!=='checkbox'||el.checked)field(el.name,el.value);});
  const isPreview=operation.startsWith('preview_')||format;
  if(header){const same=header.querySelector('[name="alkamy_shipping_same_billing"]');if(same)field('alkamy_shipping_same_billing',same.checked?'1':'0');}
  if((format||operation==='issue'||['local_pdf','local_view_pdf'].includes(operation))&&header)header.querySelectorAll('[name^="_billing_"],[name^="_shipping_"]').forEach(el=>{if(!el.disabled)field('alkamy_address['+el.name+']',el.value);});
  if(format){field('paper',preview.querySelector('.alkamy-preview-paper')?.value||'a4');field('preview',format);field('preview_type',preview.querySelector('.alkamy-preview-type').value);}
  if(operation==='issue')field('workspace','1');
  if(isPreview){form.target=preview.querySelector('iframe').name;preview.querySelector('.alkamy-preview-status').textContent=documentId?'Issued document preview. Original contents are preserved.':'Draft preview. Nothing is issued or sent.';}
  if(['local_pdf','local_view_pdf'].includes(operation))field('paper',preview.querySelector('.alkamy-preview-paper')?.value||'a4');
  if(operation==='pdf'||['local_pdf','local_view_pdf'].includes(operation))form.target='_blank';
  if(isPreview){
   request?.abort();request=new AbortController();const current=request,frame=preview.querySelector('iframe'),status=preview.querySelector('.alkamy-preview-status');
   status.textContent='Loading preview…';
   fetch(preview.dataset.endpoint,{method:'POST',body:new FormData(form),credentials:'same-origin',signal:current.signal}).then(async response=>{
    const blob=await response.blob(),isPdf=response.ok&&blob.type.includes('application/pdf');const html=isPdf?null:await blob.text();if(current!==request)return;
    if(previewUrl){URL.revokeObjectURL(previewUrl);previewUrl=null;}
    if(isPdf){frame.removeAttribute('sandbox');frame.removeAttribute('srcdoc');previewUrl=URL.createObjectURL(blob);frame.src=previewUrl+'#view=FitH&navpanes=0';}
    else{frame.setAttribute('sandbox','');frame.removeAttribute('src');frame.srcdoc=html;}
    status.textContent=response.ok?(documentId?'Issued document preview. Original contents are preserved.':'Draft preview. Nothing is issued or sent.'):'Preview unavailable. See the message below.';
   }).catch(error=>{if(current===request&&error.name!=='AbortError')status.textContent='Preview could not be loaded. Choose Refresh preview to retry.';});
   return;
  }
  document.body.append(form);form.submit();form.remove();
 };
 const refresh=()=>{clearTimeout(timer);issued=false;const paperControl=preview.querySelector('.alkamy-paper-control');if(paperControl)paperControl.hidden=preview.querySelector('.alkamy-preview-format').value!=='layout';submit('sections',{format:preview.querySelector('.alkamy-preview-format').value});};
 const changed=()=>{if(!preview)return;clearTimeout(timer);timer=setTimeout(refresh,700);};
 document.querySelectorAll('.alkamy form,.alkamy-editor').forEach(root=>{
  root.addEventListener('click',e=>{const button=e.target.closest('button');if(!button)return;if(button.matches('.alkamy-add')){add(root);changed();}if(button.matches('.alkamy-remove')){button.closest('.alkamy-section').remove();reindex(root);changed();}if(button.matches('.alkamy-up')){const row=button.closest('.alkamy-section');if(row.previousElementSibling)row.parentNode.insertBefore(row,row.previousElementSibling);reindex(root);changed();}if(button.dataset.confirm&&!confirm(button.dataset.confirm))e.preventDefault();});
  root.querySelectorAll('.alkamy-section').forEach(positionMap);
  root.addEventListener('input',e=>{const row=e.target.closest('.alkamy-section');if(row)positionMap(row);});
  root.addEventListener('change',e=>{
   const row=e.target.closest('.alkamy-section');if(!row)return;
   if(e.target.matches('.alkamy-section-preset')&&e.target.value){
    const preset=JSON.parse(e.target.value),heading=row.querySelector('[data-field=heading]'),body=row.querySelector('[data-field=body]');
    if((heading.value||body.value)&&!confirm('Replace this section with the selected preset?')){e.target.value='';return;}
    heading.value=preset.heading;body.value=preset.body;row.querySelector('[data-field=position]').value=preset.position||'after_items';
    row.querySelectorAll('[type=checkbox]').forEach(el=>el.checked=preset.documents.includes(el.value));e.target.value='';row.dispatchEvent(new Event('input',{bubbles:true}));
   }
   positionMap(row);changed();
  });
  root.addEventListener('submit',()=>reindex(root));
 });
 if(preview&&editor){
  document.querySelectorAll('.alkamy-custom-fields,.alkamy-invoicenow-details,#order_data').forEach(root=>{root.addEventListener('input',changed);root.addEventListener('change',changed);});
  editor.addEventListener('input',changed);editor.addEventListener('change',changed);
  preview.querySelectorAll('select').forEach(el=>el.addEventListener('change',refresh));
  document.addEventListener('click',e=>{const button=e.target.closest('[data-alkamy-action]');if(!button)return;e.preventDefault();clearTimeout(timer);const op=button.dataset.alkamyAction;
   if(op==='preview'){refresh();return;}
   if(op==='credit'&&!confirm('Create a full credit note and refund the full payment if paid?'))return;
   if(op==='email'&&!confirm('Send this issued document by email to the customer?'))return;
   if(op==='issue'&&!confirm('Create this document using the current sections and saved order items?'))return;
   issued=op.startsWith('preview_');submit(op,{type:button.dataset.type,documentId:button.dataset.document,refundId:button.dataset.refund});
  });
  // WooCommerce refreshes saved order items through Ajax on both order screens.
  if(window.jQuery)window.jQuery(document.body).on('wc_order_items_reloaded',()=>{if(!issued)changed();});
  refresh();
 }
});
