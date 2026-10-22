const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  const allErrors = [];
  page.on('pageerror', err => {
    allErrors.push({ msg: err.message, stack: err.stack?.substring(0, 300) });
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  
  // Clear errors from login page
  allErrors.length = 0;
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(3000);
  
  if (allErrors.length === 0) {
    console.log('✅ NO ERRORS!');
  } else {
    console.log('❌ Errors found:');
    allErrors.forEach(e => {
      console.log('  MSG:', e.msg);
      console.log('  STACK:', e.stack);
      console.log('---');
    });
  }
  
  await browser.close();
})();
