/**
 * Detailed Sidebar Visual Audit
 * Checks sidebar positioning, width, connection to main content, and visual issues
 */
const { chromium } = require('@playwright/test');

const BASE_URL = 'http://localhost:8080';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();

    // Login
    await page.goto(`${BASE_URL}/login`);
    await page.fill('form [name="email"]', 'test@agency.com');
    await page.fill('form [name="password"]', 'password');
    await page.click('form button[type="submit"]');
    await page.waitForURL(/\/dashboard/);

    // Go to dashboard
    await page.goto(`${BASE_URL}/dashboard`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1000);

    // Detailed sidebar analysis
    const sidebarInfo = await page.evaluate(() => {
        const aside = document.querySelector('aside');
        const nav = document.querySelector('nav.sidebar-nav');
        const main = document.querySelector('#main-content');
        const header = document.querySelector('header');
        const body = document.body;

        const asideRect = aside ? aside.getBoundingClientRect() : null;
        const navRect = nav ? nav.getBoundingClientRect() : null;
        const mainRect = main ? main.getBoundingClientRect() : null;
        const headerRect = header ? header.getBoundingClientRect() : null;

        const asideStyle = aside ? window.getComputedStyle(aside) : null;
        const mainStyle = main ? window.getComputedStyle(main) : null;
        const bodyStyle = window.getComputedStyle(body);

        return {
            aside: asideRect ? {
                left: Math.round(asideRect.left),
                top: Math.round(asideRect.top),
                right: Math.round(asideRect.right),
                width: Math.round(asideRect.width),
                height: Math.round(asideRect.height),
                position: asideStyle?.position,
                display: asideStyle?.display,
                zIndex: asideStyle?.zIndex,
                backgroundColor: asideStyle?.backgroundColor,
                borderRight: asideStyle?.borderRight,
                boxShadow: asideStyle?.boxShadow,
            } : null,
            nav: navRect ? {
                left: Math.round(navRect.left),
                top: Math.round(navRect.top),
                width: Math.round(navRect.width),
                height: Math.round(navRect.height),
            } : null,
            main: mainRect ? {
                left: Math.round(mainRect.left),
                top: Math.round(mainRect.top),
                width: Math.round(mainRect.width),
                height: Math.round(mainRect.height),
                paddingLeft: mainStyle?.paddingLeft,
            } : null,
            header: headerRect ? {
                left: Math.round(headerRect.left),
                top: Math.round(headerRect.top),
                width: Math.round(headerRect.width),
                height: Math.round(headerRect.height),
            } : null,
            body: {
                display: bodyStyle?.display,
                flexDirection: bodyStyle?.flexDirection,
                overflow: bodyStyle?.overflow,
            },
            // Check if sidebar and main content are visually connected
            gapBetween: asideRect && mainRect ? Math.round(mainRect.left - asideRect.right) : null,
            // Check if header spans full width or just main content
            headerSpansFull: headerRect ? headerRect.left === 0 : null,
            // Check sidebar background color matches main
            sidebarBg: asideStyle?.backgroundColor,
            mainBg: mainStyle?.backgroundColor,
        };
    });

    console.log('=== SIDEBAR DETAILED ANALYSIS ===');
    console.log('Aside:', JSON.stringify(sidebarInfo.aside, null, 2));
    console.log('Nav:', JSON.stringify(sidebarInfo.nav, null, 2));
    console.log('Main:', JSON.stringify(sidebarInfo.main, null, 2));
    console.log('Header:', JSON.stringify(sidebarInfo.header, null, 2));
    console.log('Body:', JSON.stringify(sidebarInfo.body, null, 2));
    console.log('Gap between sidebar and main:', sidebarInfo.gapBetween, 'px');
    console.log('Header spans full width:', sidebarInfo.headerSpansFull);
    console.log('Sidebar bg:', sidebarInfo.sidebarBg);
    console.log('Main bg:', sidebarInfo.mainBg);

    // Check sidebar nav items
    const navItems = await page.evaluate(() => {
        const items = document.querySelectorAll('nav.sidebar-nav a, nav.sidebar-nav button');
        return Array.from(items).slice(0, 10).map(item => {
            const rect = item.getBoundingClientRect();
            return {
                text: item.textContent.trim().substring(0, 30),
                left: Math.round(rect.left),
                top: Math.round(rect.top),
                width: Math.round(rect.width),
                height: Math.round(rect.height),
                visible: rect.width > 0 && rect.height > 0,
            };
        });
    });

    console.log('\n=== NAV ITEMS ===');
    navItems.forEach(item => {
        console.log(`  "${item.text}" left=${item.left} top=${item.top} w=${item.width} h=${item.height} visible=${item.visible}`);
    });

    // Check if sidebar has proper border/separator
    const borderInfo = await page.evaluate(() => {
        const aside = document.querySelector('aside');
        if (!aside) return null;
        const style = window.getComputedStyle(aside);
        return {
            borderRight: style.borderRight,
            borderRightWidth: style.borderRightWidth,
            borderRightColor: style.borderRightColor,
            boxShadow: style.boxShadow,
        };
    });

    console.log('\n=== SIDEBAR BORDER ===');
    console.log(JSON.stringify(borderInfo, null, 2));

    // Check sidebar toggle button
    const toggleBtn = await page.evaluate(() => {
        const btn = document.querySelector('button[aria-label*="sidebar"], button[aria-label*="menu"], button[aria-label*="toggle"]');
        if (!btn) return null;
        const rect = btn.getBoundingClientRect();
        return {
            text: btn.textContent.trim().substring(0, 30),
            ariaLabel: btn.getAttribute('aria-label'),
            left: Math.round(rect.left),
            top: Math.round(rect.top),
            width: Math.round(rect.width),
            height: Math.round(rect.height),
            visible: rect.width > 0 && rect.height > 0,
        };
    });

    console.log('\n=== TOGGLE BUTTON ===');
    console.log(JSON.stringify(toggleBtn, null, 2));

    // Check if sidebar is inside a flex container with main content
    const layoutStructure = await page.evaluate(() => {
        const aside = document.querySelector('aside');
        const main = document.querySelector('#main-content');
        if (!aside || !main) return null;

        const asideParent = aside.parentElement;
        const mainParent = main.parentElement;

        return {
            asideParentTag: asideParent?.tagName,
            asideParentClass: asideParent?.className?.substring(0, 80),
            mainParentTag: mainParent?.tagName,
            mainParentClass: mainParent?.className?.substring(0, 80),
            sameParent: asideParent === mainParent,
            asideParentDisplay: asideParent ? window.getComputedStyle(asideParent).display : null,
            mainParentDisplay: mainParent ? window.getComputedStyle(mainParent).display : null,
        };
    });

    console.log('\n=== LAYOUT STRUCTURE ===');
    console.log(JSON.stringify(layoutStructure, null, 2));

    await browser.close();
})();
