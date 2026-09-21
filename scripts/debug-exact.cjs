const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  page.on('pageerror', async err => {
    console.log('MESSAGE:', err.message);
    // Try to get source location
    try {
      const loc = await page.evaluate(() => {
        const scripts = document.querySelectorAll('script');
        let info = '';
        scripts.forEach(s => {
          if (s.textContent.includes('expandedSections')) {
            info += 'Script contains expandedSections\n';
          }
        });
        return info;
      });
      console.log('INFO:', loc);
    } catch(e) {}
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(3000);

  // Get body's x-data value  
  const bodyXData = await page.evaluate(() => {
    return document.body.getAttribute('x-data');
  });
  console.log('\nBODY X-DATA:');
  console.log(bodyXData);
  
  await browser.close();
})();
