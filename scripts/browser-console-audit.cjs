/**
 * Browser Console Audit Script
 * Captures all console errors, warnings, and failed network requests
 * across all key pages of the application.
 */
const { chromium } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://localhost:8080';

const pages = [
    { name: 'Login', path: '/login' },
    { name: 'Register', path: '/register' },
    { name: 'Landing', path: '/' },
    { name: 'Pricing', path: '/pricing' },
    { name: 'Features', path: '/features' },
    { name: 'Dashboard', path: '/dashboard', auth: true },
    { name: 'Social Posts', path: '/social/posts', auth: true },
    { name: 'Create Post', path: '/social/posts/create', auth: true },
    { name: 'Campaigns', path: '/campaigns', auth: true },
    { name: 'Analytics', path: '/analytics', auth: true },
    { name: 'Billing', path: '/agency/billing', auth: true },
    { name: 'Inbox', path: '/inbox', auth: true },
    { name: 'Calendar', path: '/calendar', auth: true },
    { name: 'AI Content', path: '/ai', auth: true },
    { name: 'Workflows', path: '/workflows', auth: true },
    { name: 'Clients', path: '/clients', auth: true },
    { name: 'Settings', path: '/agency/settings', auth: true },
];

async function auditPage(browser, pageConfig) {
    const context = await browser.newContext();
    const page = await context.newPage();

    const consoleErrors = [];
    const consoleWarnings = [];
    const consoleInfo = [];
    const networkErrors = [];
    const failedRequests = [];

    page.on('console', msg => {
        const type = msg.type();
        const text = msg.text();
        if (type === 'error') consoleErrors.push(text);
        else if (type === 'warning') consoleWarnings.push(text);
        else if (type === 'info') consoleInfo.push(text);
    });

    page.on('pageerror', err => {
        consoleErrors.push(`PAGE_ERROR: ${err.message}`);
    });

    page.on('requestfailed', request => {
        failedRequests.push(`${request.url()} — ${request.failure()?.errorText || 'unknown'}`);
    });

    page.on('response', response => {
        if (response.status() >= 400) {
            networkErrors.push(`${response.status()} ${response.url()}`);
        }
    });

    try {
        await page.goto(`${BASE_URL}${pageConfig.path}`, { waitUntil: 'networkidle', timeout: 15000 });

        // If auth needed, login first
        if (pageConfig.auth) {
            const url = page.url();
            if (url.includes('/login')) {
                await page.fill('[name="email"]', 'test@agency.com');
                await page.fill('[name="password"]', 'password');
                await page.click('button[type="submit"]');
                await page.waitForURL(/\/dashboard/, { timeout: 10000 });
                // Now navigate to the actual page
                await page.goto(`${BASE_URL}${pageConfig.path}`, { waitUntil: 'networkidle', timeout: 15000 });
            }
        }

        // Wait for any async operations
        await page.waitForTimeout(1000);

        // Additional UX checks
        const brokenImages = await page.evaluate(() => {
            const imgs = Array.from(document.querySelectorAll('img'));
            return imgs.filter(img => img.complete && img.naturalWidth === 0)
                .map(img => img.src || img.getAttribute('src') || 'no-src');
        });

        const emptyButtons = await page.evaluate(() => {
            const btns = Array.from(document.querySelectorAll('button, a[role="button"]'));
            return btns.filter(btn => !btn.textContent.trim() && !btn.querySelector('i, svg, img'))
                .map(btn => btn.className || btn.id || 'unnamed');
        });

        const hasHScroll = await page.evaluate(() =>
            document.documentElement.scrollWidth > document.documentElement.clientWidth + 2);

        const viewportMeta = await page.evaluate(() => {
            const meta = document.querySelector('meta[name="viewport"]');
            return meta ? meta.content : 'MISSING';
        });

        const result = {
            name: pageConfig.name,
            path: pageConfig.path,
            consoleErrors,
            consoleWarnings,
            consoleInfo,
            networkErrors,
            failedRequests,
            brokenImages,
            emptyButtons,
            hasHScroll,
            viewportMeta,
        };

        return result;
        } catch (err) {
            consoleErrors.push(`NAVIGATION_ERROR: ${err.message}`);
        }

        await context.close();

        return {
            name: pageConfig.name,
            path: pageConfig.path,
            consoleErrors,
            consoleWarnings,
            consoleInfo,
            networkErrors,
            failedRequests,
            brokenImages: [],
            emptyButtons: [],
            hasHScroll: false,
            viewportMeta: 'ERROR',
        };
    }

(async () => {
    const browser = await chromium.launch({ headless: true });
    const results = [];

    for (const pageConfig of pages) {
        console.log(`\nAuditing: ${pageConfig.name} (${pageConfig.path})`);
        const result = await auditPage(browser, pageConfig);
        results.push(result);

        if (result.consoleErrors.length > 0) {
            console.log(`  ❌ Errors: ${result.consoleErrors.length}`);
            result.consoleErrors.forEach(e => console.log(`     — ${e.substring(0, 120)}`));
        }
        if (result.consoleWarnings.length > 0) {
            console.log(`  ⚠️  Warnings: ${result.consoleWarnings.length}`);
            result.consoleWarnings.forEach(w => console.log(`     — ${w.substring(0, 120)}`));
        }
        if (result.networkErrors.length > 0) {
            console.log(`  🌐 Network Errors: ${result.networkErrors.length}`);
            result.networkErrors.forEach(e => console.log(`     — ${e.substring(0, 120)}`));
        }
        if (result.failedRequests.length > 0) {
            console.log(`  🚫 Failed Requests: ${result.failedRequests.length}`);
            result.failedRequests.forEach(r => console.log(`     — ${r.substring(0, 120)}`));
        }
        if (result.consoleErrors.length === 0 && result.consoleWarnings.length === 0 && result.networkErrors.length === 0) {
            console.log(`  ✅ Clean`);
        }
    }

    await browser.close();

    // Summary
    console.log('\n\n========== SUMMARY ==========');
    const totalErrors = results.reduce((sum, r) => sum + r.consoleErrors.length, 0);
    const totalWarnings = results.reduce((sum, r) => sum + r.consoleWarnings.length, 0);
    const totalNetworkErrors = results.reduce((sum, r) => sum + r.networkErrors.length, 0);
    const totalFailedRequests = results.reduce((sum, r) => sum + r.failedRequests.length, 0);

    console.log(`Total Console Errors: ${totalErrors}`);
    console.log(`Total Console Warnings: ${totalWarnings}`);
    console.log(`Total Network Errors: ${totalNetworkErrors}`);
    console.log(`Total Failed Requests: ${totalFailedRequests}`);
    console.log(`Pages Audited: ${results.length}`);

    // Pages with issues
    const pagesWithIssues = results.filter(r => r.consoleErrors.length > 0 || r.networkErrors.length > 0 || r.failedRequests.length > 0);
    if (pagesWithIssues.length > 0) {
        console.log('\nPages with issues:');
        pagesWithIssues.forEach(p => {
            console.log(`  — ${p.name}: ${p.consoleErrors.length} errors, ${p.networkErrors.length} network errors, ${p.failedRequests.length} failed requests`);
        });
    }
})();
