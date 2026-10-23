// Disposable CI browser smoke. Full council UAT remains separate evidence.
import { createRequire } from 'node:module';
import fs from 'node:fs';
const require = createRequire(process.env.PLAYWRIGHT_PACKAGE + '/package.json');
const { chromium } = require('playwright');
const browser = await chromium.launch({headless:true,...(process.env.CHROME_EXECUTABLE?{executablePath:process.env.CHROME_EXECUTABLE}:{})});
const page = await browser.newPage({viewport:{width:1440,height:1100}});
const errors=[];page.on('pageerror',e=>errors.push(e.message));
const base='http://127.0.0.1:8899';
try {
 await page.goto(base+'/wp-login.php');
 await page.locator('#user_login').fill('admin');
 await page.locator('#user_pass').fill('ci-disposable-only');
 await Promise.all([page.waitForURL('**/wp-admin/**'),page.locator('#wp-submit').click()]);
 const hpos=process.env.IOEFW_TEST_EXPECT_HPOS==='yes';
 const id=process.env.CI_ORDER_ID;
 await page.goto(base+(hpos?'/wp-admin/admin.php?page=wc-orders&action=edit&id='+id:'/wp-admin/post.php?post='+id+'&action=edit'));
 await page.locator('#order_data').waitFor();
 if(process.env.CI_PLUGIN_SLUG==='inline-order-editor-pro') {
  await page.locator('#alkamy-export').waitFor();
  await page.locator('#alkamy-sections').waitFor();
  if(await page.locator('#alkamy-export [data-alkamy-action="local_pdf"]').count()<4) throw new Error('Missing PDF download controls');
 }
 for(const width of [1440,768,390]) {
  await page.setViewportSize({width,height:1100});
  await page.screenshot({path:'build/quality/order-'+width+'.png',fullPage:true});
 }
 if(errors.length) throw new Error('Browser errors: '+errors.join('; '));
 fs.writeFileSync('build/quality/browser.json',JSON.stringify({status:'PASS',order:id,storage:hpos?'hpos':'posts',widths:[1440,768,390],errors},null,2));
} finally {await browser.close();}
