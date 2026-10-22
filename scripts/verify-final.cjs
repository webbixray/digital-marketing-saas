const { chromium } = require('@playwright/test');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  
  const errors = [];
  page.on('pageerror', err => errors.push({ msg: err.message, stack: err.stack?.substring(0, 200) }));
  page.on('console', msg => {
    if (msg.type() === 'error' && !msg.text().includes('favicon') && !msg.text().includes('cdn')) {
      errors.push({ msg: msg.text() });
    }
  });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle', timeout: 20000 });
  await page.fill('input[name="email"]', 'test@agency.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard*', { timeout: 15000 });
  
  errors.length = 0;
  
  await page.goto('http://127.0.0.1:8000/dashboard', { waitUntil: 'networkidle', timeout: 20000 });
  await page.waitForTimeout(3000);
  
  if (errors.length === 0) {
    console.log('✅ NO ERRORS — Alpine.js working!');
    
    // Test interactivity
    const sidebarWorks = await page.evaluate(() => {
      return document.body.getAttribute('x-data') !== null;
    });
    console.log('Sidebar x-data present:', sidebarWorks);
    
    const h1 = await page.$eval('h2', el => el.textContent).catch(() => 'N/A');
    console.log('Welcome heading:', h1);
  } else {
    console.log('❌ Still has errors:');
    errors.slice(0, 3).forEach(e => console.log('  -', e.msg));
  }
  
  await browser.close();
})();
