const { chromium } = require('@playwright/test');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  // Capture ALL console messages
  const messages = [];
  page.on('console', msg => {
    messages.push({ type: msg.type(), text: msg.text(), location: msg.location() });
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  messages.length = 0;
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(2000);
  
  // Get the x-data attribute value directly from the DOM
  const xDataValue = await page.evaluate(() => {
    return document.body.getAttribute('x-data');
  });
  
  console.log('=== X-DATA ATTRIBUTE VALUE ===');
  console.log(xDataValue);
  console.log('\n=== CONSOLE MESSAGES ===');
  messages.forEach(m => console.log(`[${m.type}] ${m.text}`));
  
  // Save x-data to file for JS validation
  fs.writeFileSync('test-results/xdata-value.txt', xDataValue || 'NOT FOUND');
  
  await browser.close();
})();
