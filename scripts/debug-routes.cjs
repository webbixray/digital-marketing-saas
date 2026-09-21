const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  
  // Test 1: Check if AI Credits route exists
  const ctx1 = await browser.newContext();
  const page1 = await ctx1.newPage();
  await page1.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded', timeout: 20000 });
  await page1.fill('input[name="email"]', 'test@agency.com');
  await page1.fill('input[name="password"]', 'password');
  await page1.click('button[type="submit"]');
  await page1.waitForURL('**/dashboard*', { timeout: 15000 });
  
  const response = await page1.goto('http://127.0.0.1:8000/ai/credits', { waitUntil: 'domcontentloaded', timeout: 20000 });
  console.log('AI Credits status:', response.status());
  console.log('AI Credits URL:', page1.url());
  await ctx1.close();
  
  // Test 2: Check inbox 500 error
  const ctx2 = await browser.newContext({ viewport: { width: 375, height: 812 } });
  const page2 = await ctx2.newPage();
  
  const errors = [];
  page2.on('response', res => {
    if (res.status() >= 400) {
      errors.push({ status: res.status(), url: res.url() });
    }
  });
  
  await page2.goto('http://127.0.0.1:8000/login', { waitUntil: 'domcontentloaded', timeout: 20000 });
  await page2.fill('input[name="email"]', 'test@agency.com');
  await page2.fill('input[name="password"]', 'password');
  await page2.click('button[type="submit"]');
  await page2.waitForURL('**/dashboard*', { timeout: 15000 });
  
  await page2.goto('http://127.0.0.1:8000/inbox', { waitUntil: 'domcontentloaded', timeout: 20000 });
  await page2.waitForTimeout(2000);
  
  console.log('\nInbox errors:', errors);
  console.log('Inbox final URL:', page2.url());
  
  await ctx2.close();
  await browser.close();
})();
