/**
 * Focused Browser Audit — Calendar, Analytics, Inbox
 * Checks for real app bugs found in previous audit:
 * 1. Calendar: x-trap without Focus plugin
 * 2. Analytics: cached Eloquent models fail to unserialize
 * 3. Inbox: cached Eloquent models fail to unserialize
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

        const consoleErrors = [];
        const pageErrors = [];

        page.on('console', msg => {
            if (msg.type() === 'error') consoleErrors.push(msg.text());
        });
        page.on('pageerror', err => pageErrors.push(err.message));

        console.log(`\n=== ${pageConfig.name} (${pageConfig.path}) ===`);

        try {
            const response = await page.goto(`${BASE_URL}${pageConfig.path}`, {
                waitUntil: 'networkidle',
                timeout: 15000,
            });

            await page.waitForTimeout(1000);

            console.log(`HTTP Status: ${response.status()}`);
            if (response.status() >= 400) {
                console.log(`❌ Server error`);
            }

            // Check for horizontal scrollbar
            const hasHScroll = await page.evaluate(() =>
                document.documentElement.scrollWidth > document.documentElement.clientWidth + 2);
            if (hasHScroll) console.log(`⚠️  Horizontal scrollbar detected`);

            console.log(`Console errors: ${consoleErrors.length}`);
            consoleErrors.slice(0, 5).forEach(e => console.log(`  — ${e.substring(0, 120)}`));

            console.log(`Page errors: ${pageErrors.length}`);
            pageErrors.slice(0, 5).forEach(e => console.log(`  — ${e.substring(0, 120)}`));

            if (consoleErrors.length === 0 && pageErrors.length === 0) {
                console.log(`✅ Clean`);
            }
        } catch (err) {
            console.log(`Navigation failed: ${err.message.substring(0, 120)}`);
        }

        await context.close();
    }

    await browser.close();
})();
