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
  
  // Get the full inline script content
  const scriptContent = await page.evaluate(() => {
    const scripts = document.querySelectorAll('script:not([src])');
    return scripts.length > 0 ? scripts[0].textContent : 'NO SCRIPT FOUND';
  });
  
  console.log('=== FULL INLINE SCRIPT ===');
  console.log(scriptContent);
  console.log('=== END ===');
  
  // Try to evaluate it in a Function to see the exact error
  try {
    new Function(scriptContent);
    console.log('\n✅ Script parses correctly');
  } catch(e) {
    console.log('\n❌ Parse error:', e.message);
    // Find the line
    const lines = scriptContent.split('\n');
    lines.forEach((line, i) => {
      try {
        new Function(line);
      } catch(e) {
        console.log(`  Line ${i+1}: ${line.substring(0, 100)}`);
        console.log(`  Error: ${e.message}`);
      }
    });
  }
  
  await browser.close();
})();
