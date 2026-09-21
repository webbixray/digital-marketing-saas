const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  const errors = [];
  page.on('pageerror', err => errors.push(err.message));

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  errors.length = 0;
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(3000);
  
  // Get all inline scripts and their line numbers
  const inlineScripts = await page.evaluate(() => {
    return Array.from(document.querySelectorAll('script:not([src])')).map(s => ({
      nonce: s.nonce,
      content: s.textContent.substring(0, 300)
    }));
  });
  
  console.log('Inline scripts found:', inlineScripts.length);
  inlineScripts.forEach((s, i) => {
    console.log(`[Script ${i}] nonce="${s.nonce}":`);
    console.log(s.content.substring(0, 200));
    console.log('---');
  });
  
  console.log('\nErrors:', errors);
  
  await browser.close();
})();
