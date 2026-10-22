const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  const errors = [];
  page.on('pageerror', err => errors.push(err.message));
  page.on('console', msg => {
    if (msg.type() === 'error' && !msg.text().includes('favicon')) {
      errors.push(msg.text());
    }
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  
  errors.length = 0;
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(2000);
  
  if (errors.length === 0) {
    console.log('✅ NO ERRORS — JS syntax fix verified!');
  } else {
    console.log('❌ Still has errors:');
    errors.forEach(e => console.log('  -', e));
  }
  
  await browser.close();
})();
