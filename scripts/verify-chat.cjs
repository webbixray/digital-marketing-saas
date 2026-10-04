// Verify /chat/v2 renders 200 with no console errors
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  const errors = [];
  page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text().substring(0, 200)); });
  page.on('pageerror', err => errors.push('PAGEERROR: ' + String(err).substring(0, 300)));

  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('input[name="email"]', 'owner@agency.com');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');

  const res = await page.goto('http://127.0.0.1:8000/chat/v2');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);

  console.log('HTTP status:', res.status());
  console.log('title:', await page.title());
  const emptyState = await page.evaluate(() => document.body.innerText.substring(0, 300));
  console.log('page text:', emptyState.replace(/\n+/g, ' | ').substring(0, 200));
  console.log('console errors:', errors.length);
  errors.slice(0, 5).forEach(e => console.log(' ', e));
  await browser.close();
})();
