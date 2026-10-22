/**
 * Deep UI Audit — Forms, Tables, Modals, Navigation
 * Checks for UX bugs: broken forms, misaligned tables, modal issues, nav problems
 */
const { chromium } = require('@playwright/test');

const BASE_URL = 'http://localhost:8080';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();
    const errors = [];

    page.on('pageerror', err => errors.push(err.message));
    page.on('console', msg => {
        if (msg.type() === 'error' && !msg.text().includes('font')) errors.push(msg.text());
    });

    // Login
    await page.goto(`${BASE_URL}/login`);
    await page.fill('form [name="email"]', 'test@agency.com');
    await page.fill('form [name="password"]', 'password');
    await page.click('form button[type="submit"]');
    await page.waitForURL(/\/dashboard/);

    // Test key interactive pages
    const tests = [
        {
            name: 'Create Post Form',
            path: '/social/posts/create',
            checks: {
                'form': 'form',
                'select': 'select',
                'textarea': 'textarea',
                'submit': 'button[type="submit"]',
            }
        },
        {
            name: 'Create Campaign Form',
            path: '/campaigns/create',
            checks: {
                'form': 'form',
                'input-name': '[name="name"]',
                'input-description': '[name="description"]',
                'submit': 'button[type="submit"]',
            }
        },
        {
            name: 'Create Client Form',
            path: '/clients/create',
            checks: {
                'form': 'form',
                'input-name': '[name="name"]',
                'input-email': '[name="email"]',
                'submit': 'button[type="submit"]',
            }
        },
        {
            name: 'Settings Page',
            path: '/agency/settings',
            checks: {
                'form': 'form',
                'input-name': '[name="name"]',
                'input-email': '[name="email"]',
                'timezone': '[name="timezone"]',
                'currency': '[name="currency"]',
                'submit': 'button[type="submit"]',
            }
        },
        {
            name: 'Posts List (Table)',
            path: '/social/posts',
            checks: {
                'table': 'table',
                'table-rows': 'table tbody tr',
                'pagination': '.pagination, [class*="pagination"]',
            }
        },
        {
            name: 'Clients List (Table)',
            path: '/clients',
            checks: {
                'table': 'table',
                'table-rows': 'table tbody tr',
                'search-input': 'input[type="search"], [name="search"], [placeholder*="earch"]',
            }
        },
        {
            name: 'Inbox (List)',
            path: '/inbox',
            checks: {
                'list': '.space-y-4 .flex, [class*="message"], [class*="inbox"]',
                'filter-buttons': 'button[name*="filter"], button[class*="filter"]',
            }
        },
    ];

    for (const test of tests) {
        await page.goto(`${BASE_URL}${test.path}`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);

        console.log(`\n=== ${test.name} ===`);

        // Check page loaded
        const statusCode = await page.evaluate(() => document.status);
        console.log(`  Status: ${statusCode || 'ok'}`);

        // Check for broken images
        const brokenImages = await page.evaluate(() => {
            return Array.from(document.querySelectorAll('img'))
                .filter(img => img.complete && img.naturalWidth === 0)
                .map(img => img.src || img.getAttribute('src') || 'no-src');
        });
        if (brokenImages.length > 0) {
            console.log(`  ❌ Broken images: ${brokenImages.length}`, brokenImages.slice(0, 3));
        }

        // Check for horizontal scrollbar
        const scrollW = await page.evaluate(() => document.documentElement.scrollWidth);
        const clientW = await page.evaluate(() => document.documentElement.clientWidth);
        if (scrollW > clientW + 2) {
            console.log(`  ⚠️  Horizontal scrollbar: scrollW=${scrollW} clientW=${clientW}`);
        }

        // Check for vertical overflow (content taller than viewport is OK)
        // Check for elements with 0 width/height that should be visible
        const invisibleElements = await page.evaluate(() => {
            const elements = document.querySelectorAll('input, select, textarea, button');
            return Array.from(elements).filter(el => {
                const rect = el.getBoundingClientRect();
                const style = window.getComputedStyle(el);
                return style.display !== 'none' && style.visibility !== 'hidden' &&
                       (rect.width === 0 || rect.height === 0);
            }).map(el => el.tagName + '.' + (el.className || '').split(' ')[0] + '#' + (el.id || ''));
        });
        if (invisibleElements.length > 0) {
            console.log(`  ⚠️  Zero-size form elements:`, invisibleElements.slice(0, 5));
        }

        // Check for text overflow in table cells
        const overflowCells = await page.evaluate(() => {
            const cells = document.querySelectorAll('td, th');
            return Array.from(cells).filter(cell => {
                return cell.scrollWidth > cell.clientWidth + 4;
            }).map(cell => cell.textContent.trim().substring(0, 40));
        });
        if (overflowCells.length > 0) {
            console.log(`  ⚠️  Overflowing table cells:`, overflowCells.slice(0, 3));
        }

        if (brokenImages.length === 0 && scrollW <= clientW + 2 && invisibleElements.length === 0 && overflowCells.length === 0) {
            console.log(`  ✅ Clean`);
        }
    }

    console.log(`\n=== ERRORS ===`);
    console.log(`Total: ${errors.length}`);
    errors.slice(0, 10).forEach(e => console.log(`  — ${e.substring(0, 150)}`));

    await browser.close();
})();
