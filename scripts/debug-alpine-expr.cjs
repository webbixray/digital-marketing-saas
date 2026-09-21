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
  
  // Get all elements with Alpine directives and their expressions
  const expressions = await page.evaluate(() => {
    const results = [];
    const allElements = document.querySelectorAll('*');
    const alpineAttrs = ['x-data', 'x-show', 'x-if', 'x-bind', 'x-model', '@click', 'x-init', 'x-for', 'x-html', 'x-text'];
    
    allElements.forEach(el => {
      alpineAttrs.forEach(attr => {
        const val = el.getAttribute(attr);
        if (val) {
          results.push({
            tag: el.tagName,
            attr: attr,
            value: val.substring(0, 200)
          });
        }
      });
    });
    return results;
  });
  
  console.log('=== ALPINE EXPRESSIONS ===');
  expressions.forEach((item, i) => {
    console.log(`[${i}] ${item.tag} ${item.attr}: ${item.value}`);
    
    // Try to parse it as a function body
    try {
      new Function('with(scope){' + item.value + '}');
    } catch(e) {
      try {
        new Function('with(scope){return (' + item.value + ');}');
      } catch(e2) {
        try {
          new Function(item.value);
        } catch(e3) {
          console.log(`  ❌ ALL PARSE FAILED: ${e3.message}`);
        }
      }
    }
  });
  
  await browser.close();
})();
