const { chromium } = require('@playwright/test');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  const errors = [];
  page.on('pageerror', err => errors.push({ msg: err.message, stack: err.stack }));
  page.on('console', msg => {
    if (msg.type() === 'error' && !msg.text().includes('favicon')) errors.push({ msg: msg.text() });
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  errors.length = 0;
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(1000);
  
  // Check what the body tag looks like
  const bodyHtml = await page.evaluate(() => {
    const body = document.body;
    return body.outerHTML.substring(0, 600);
  });
  
  console.log('=== BODY TAG (first 600 chars) ===');
  console.log(bodyHtml);
  
  console.log('\n=== DATA-SECTIONS VALUE ===');
  const ds = await page.evaluate(() => document.body.dataset.sections);
  console.log(ds);
  
  console.log('\n=== X-DATA ATTRIBUTE (first 300 chars) ===');
  const xd = await page.evaluate(() => document.body.getAttribute('x-data'));
  console.log(xd);
  
  console.log('\n=== ERRORS ===');
  errors.forEach(e => console.log('  -', e.msg));
  
  await browser.close();
})();
