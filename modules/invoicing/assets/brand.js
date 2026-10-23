(function(){
 const choose=document.getElementById('alkamy-logo-choose'),clear=document.getElementById('alkamy-logo-clear'),id=document.getElementById('alkamy-logo-id'),label=document.getElementById('alkamy-logo-name'),logo=document.getElementById('alkamy-logo-preview');
 if(choose)choose.addEventListener('click',()=>{const frame=wp.media({title:'Choose document logo',library:{type:['image/png','image/jpeg']},multiple:false});frame.on('select',()=>{const image=frame.state().get('selection').first().toJSON();id.value=image.id;label.textContent=image.title;if(logo){logo.src=image.url;logo.hidden=false;}});frame.open();});
 if(clear)clear.addEventListener('click',()=>{id.value='0';label.textContent='No logo selected';if(logo){logo.hidden=true;logo.removeAttribute('src');}});
 const type=document.getElementById('alkamy-design-type'),preview=document.getElementById('alkamy-design-preview');
 if(type&&preview)type.addEventListener('change',()=>{const url=new URL(preview.dataset.url);url.searchParams.set('type',type.value);preview.src=url.href;});
})();
