/**
 * Find elements causing horizontal overflow
 */
const { chromium } = require('@playwright/test');

const BASE_URL = 'http://localhost:8080';

const pages = [
    { name: 'Calendar', path: '/calendar' },
    { name: 'Analytics', path: '/analytics' },
    { name: 'Inbox', path: '/inbox' },
];

(async () => {
    const browser = await chromium.launch({ headless: true });

    for (const pageConfig of pages) {
        const context = await browser.newContext();
        const page = await context.newPage();

        // Login first
        await page.goto(`${BASE_URL}/login`);
        await page.fill('form [name="email"]', 'test@agency.com');
        await page.fill('form [name="password"]', 'password');
        await page.click('form button[type="submit"]');
        await page.waitForURL(/\/dashboard/);

        await page.goto(`${BASE_URL}${pageConfig.path}`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(1000);

        // Find elements wider than viewport
        const overflowInfo = await page.evaluate(() => {
            const docWidth = document.documentElement.clientWidth;
            const offenders = [];

            document.querySelectorAll('*').forEach(el => {
                const rect = el.getBoundingClientRect();
                if (rect.right > docWidth + 2 || rect.left < -2) {
                    offenders.push({
                        tag: el.tagName,
                        class: el.className?.toString().substring(0, 80) || '',
                        id: el.id || '',
                        left: Math.round(rect.left),
                        right: Math.round(rect.right),
                        width: Math.round(rect.width),
                    });
                }
            });

            return { docWidth, offenders: offenders.slice(0, 10) };
        });

        console.log(`\n=== ${pageConfig.name} ===`);
        console.log(`Viewport width: ${overflowInfo.docWidth}`);
        console.log(`Overflowing elements: ${overflowInfo.offenders.length}`);
        overflowInfo.offenders.forEach(o => {
            console.log(`  <${o.tag}> id="${o.id}" class="${o.class}" left=${o.left} right=${o.right} w=${o.width}`);
        });

        await context.close();
    }

    await browser.close();
})();
