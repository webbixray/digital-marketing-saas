const { chromium } = require('@playwright/test');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  const allErrors = [];
  
  page.on('pageerror', err => {
    allErrors.push({ text: err.message, stack: err.stack });
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  
  allErrors.length = 0;
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(2000);
  
  // Get the body tag's full attributes
  const bodyAttrs = await page.evaluate(() => {
    const body = document.body;
    const attrs = {};
    for (const attr of body.attributes) {
      attrs[attr.name] = attr.value;
    }
    return attrs;
  });
  
  console.log('Body attributes:');
  for (const [key, val] of Object.entries(bodyAttrs)) {
    console.log(`  ${key}: ${val.substring(0, 200)}`);
  }
  
  // Get the full x-data from body
  const xDataContent = await page.evaluate(() => {
    const body = document.body;
    return body.getAttribute('x-data');
  });
  
  console.log('\nBody x-data (first 500 chars):');
  console.log(xDataContent ? xDataContent.substring(0, 500) : 'NOT FOUND');
  
  console.log('\nErrors:', JSON.stringify(allErrors, null, 2));
  
  await browser.close();
})();
