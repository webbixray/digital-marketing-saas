const { chromium } = require('@playwright/test');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });

  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(2000);

  // Get the full HTML
  const html = await page.content();
  
  // Extract the body tag
  const bodyMatch = html.match(/<body[^>]*>/);
  console.log('Body tag:', bodyMatch ? bodyMatch[0] : 'NOT FOUND');
  
  // Find the x-data attribute value
  const xDataMatch = html.match(/x-data="([^"]*)"/);
  if (xDataMatch) {
    console.log('\nFull x-data value:');
    console.log(xDataMatch[1]);
  }
  
  // Save the full HTML for inspection
  fs.writeFileSync('test-results/dashboard-html.html', html);
  console.log('\nFull HTML saved to test-results/dashboard-html.html');
  
  await browser.close();
})();
