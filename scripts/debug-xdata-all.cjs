const { chromium } = require('@playwright/test');

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
  
  // Get all x-data attributes and try to parse them
  const xDataExpressions = await page.evaluate(() => {
    const results = [];
    document.querySelectorAll('[x-data]').forEach(el => {
      const val = el.getAttribute('x-data');
      results.push({
        tag: el.tagName,
        xdata: val ? val.substring(0, 200) : '(empty)'
      });
    });
    return results;
  });
  
  console.log('=== X-DATA EXPRESSIONS ===');
  xDataExpressions.forEach((item, i) => {
    console.log(`[${i}] ${item.tag}: ${item.xdata}`);
    
    // Try to parse it
    if (item.xdata !== '(empty)' && !item.xdata.startsWith('layoutState')) {
      try {
        new Function('with(this){return {' + item.xdata + '}}');
        console.log('  ✅ Parses OK');
      } catch(e) {
        console.log('  ❌ Parse error:', e.message);
      }
    }
  });
  
  await browser.close();
})();
