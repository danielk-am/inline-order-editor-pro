// The PDF editor must not advertise theme-only appearance controls that disappear in print.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
let postType='alkamy_pdf_layout', settingFilter, removed=[];
const wp={
 data:{select:store=>store==='core/blocks'?{getBlockStyles:()=>[{name:'display'},{name:'subtitle'}]}:{getCurrentPostType:()=>postType},subscribe:()=>()=>{}},
 hooks:{addFilter:(hook,name,fn)=>{assert.equal(hook,'blockEditor.useSetting.before');settingFilter=fn;}},
 domReady:fn=>fn(),
 blocks:{registerBlockType:()=>{},registerBlockVariation:()=>{},unregisterBlockStyle:(block,style)=>removed.push([block,style])},
 i18n:{__:s=>s},
};
const file=new URL('../modules/invoicing/blocks/document-part/index.js',import.meta.url);
vm.runInNewContext(fs.readFileSync(file,'utf8'),{window:{wp,AlkamyDocumentParts:{}},URL});
assert.equal(settingFilter(undefined,'typography.fontSizes.theme').length,4);
assert.equal(settingFilter(undefined,'typography.fontSizes.theme').at(-1).slug,'x-large');
assert.equal(settingFilter(undefined,'color.customGradient'),false);
assert.deepEqual(Array.from(settingFilter(undefined,'color.gradients.theme')),[]);
assert.equal(settingFilter(undefined,'color.palette.theme')[0].color,'#000000');
assert.equal(settingFilter(undefined,'spacing.spacingSizes.theme')[2].size,'30px');
assert.ok(removed.some(([block,style])=>block==='core/paragraph'&&style==='display'));
postType='post';
assert.equal(settingFilter('normal-theme-value','typography.fontSizes.theme'),'normal-theme-value');
assert.equal(settingFilter('normal-theme-gradient','color.customGradient'),'normal-theme-gradient');
console.log('PASS 9 PDF editor setting scope and supported-control assertions');
