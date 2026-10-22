const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  const allErrors = [];
  
  page.on('console', msg => {
    if (msg.type() === 'error') {
      allErrors.push({
        text: msg.text(),
        location: msg.location(),
        type: 'console.error'
      });
    }
  });
  
  page.on('pageerror', err => {
    allErrors.push({
      text: err.message,
      stack: err.stack,
      type: 'pageerror'
    });
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  
  allErrors.length = 0;
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(2000);
  
  console.log('Dashboard errors:', JSON.stringify(allErrors, null, 2));
  
  const scripts = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('script')).map(s => ({
      src: s.src || null,
      inline: !s.src,
      content: s.src ? null : s.textContent.substring(0, 300),
      hasNonce: s.nonce || null
    }));
  });
  
  console.log('\nScripts on dashboard:', JSON.stringify(scripts, null, 2));
  
  await browser.close();
})();
