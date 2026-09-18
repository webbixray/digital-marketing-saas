/**
 * Sidebar + Navigation UX Audit
 * Checks sidebar positioning, visibility, navigation links, and UX issues
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
        if (msg.type() === 'error') errors.push(msg.text());
    });

    // Login
    await page.goto(`${BASE_URL}/login`);
    await page.fill('form [name="email"]', 'test@agency.com');
    await page.fill('form [name="password"]', 'password');
    await page.click('form button[type="submit"]');
    await page.waitForURL(/\/dashboard/);

    // Test each key page
    const pages = [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Social Posts', path: '/social/posts' },
        { name: 'Calendar', path: '/calendar' },
        { name: 'Analytics', path: '/analytics' },
        { name: 'Inbox', path: '/inbox' },
        { name: 'Clients', path: '/clients' },
        { name: 'Settings', path: '/agency/settings' },
    ];

    for (const p of pages) {
        await page.goto(`${BASE_URL}${p.path}`, { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);

        // Get sidebar dimensions
        const sidebar = await page.evaluate(() => {
            const nav = document.querySelector('nav.sidebar-nav');
            if (!nav) return { found: false };
            const r = nav.getBoundingClientRect();
            const style = window.getComputedStyle(nav);
            return {
                found: true,
                left: Math.round(r.left),
                top: Math.round(r.top),
                width: Math.round(r.width),
                height: Math.round(r.height),
                display: style.display,
                visibility: style.visibility,
                transform: style.transform,
            };
        });

        // Get main content position
        const main = await page.evaluate(() => {
            const m = document.querySelector('#main-content');
            if (!m) return { found: false };
            const r = m.getBoundingClientRect();
            return { left: Math.round(r.left), width: Math.round(r.width) };
        });

        // Get header
        const header = await page.evaluate(() => {
            const h = document.querySelector('header');
            if (!h) return { found: false };
            const r = h.getBoundingClientRect();
            return { left: Math.round(r.left), width: Math.round(r.width), height: Math.round(r.height) };
        });

        // Get breadcrumb
        const breadcrumb = await page.evaluate(() => {
            const b = document.querySelector('nav[aria-label="Breadcrumb"]');
            if (!b) return { found: false };
            const r = b.getBoundingClientRect();
            return { left: Math.round(r.left), width: Math.round(r.width), text: b.textContent.trim().substring(0, 80) };
        });

        console.log(`\n=== ${p.name} (${p.path}) ===`);
        console.log(`  Header:    left=${header.left} w=${header.width} h=${header.height}`);
        console.log(`  Sidebar:   left=${sidebar.left} w=${sidebar.width} h=${sidebar.height} display=${sidebar.display}`);
        console.log(`  Main:      left=${main.left} w=${main.width}`);
        console.log(`  Breadcrumb: ${breadcrumb.found ? `left=${breadcrumb.left} w=${breadcrumb.width} "${breadcrumb.text}"` : 'not found'}`);

        // Check sidebar is visible and positioned correctly
        if (!sidebar.found) {
            console.log(`  ❌ SIDEBAR NOT FOUND`);
        } else if (sidebar.left < 0) {
            console.log(`  ⚠️  Sidebar off-screen (left=${sidebar.left})`);
        } else if (sidebar.width < 200) {
            console.log(`  ⚠️  Sidebar too narrow (${sidebar.width}px)`);
        }

        // Check if main content overlaps sidebar
        if (sidebar.found && main.left < sidebar.left + sidebar.width) {
            console.log(`  ⚠️  Main content overlaps sidebar (main.left=${main.left}, sidebar.right=${sidebar.left + sidebar.width})`);
        }
    }

    console.log(`\n=== ERRORS ===`);
    console.log(`Total: ${errors.length}`);
    errors.slice(0, 10).forEach(e => console.log(`  — ${e.substring(0, 150)}`));

    await browser.close();
})();
