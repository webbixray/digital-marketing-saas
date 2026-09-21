/**
 * Comprehensive page audit for the Digital Marketing SaaS platform.
 * Checks console errors, failed requests, horizontal scroll, broken images,
 * zero-size content elements, and layout issues on every authenticated page.
 */

const { chromium } = require('@playwright/test');
const fs = require('fs');

const BASE = 'http://127.0.0.1:8000';
const LOGIN_EMAIL = 'test@agency.com';
const LOGIN_PASSWORD = 'password';

const pages = [
  { name: 'Dashboard', path: '/dashboard' },
  { name: 'Posts', path: '/social/posts' },
  { name: 'Create Post', path: '/social/posts/create' },
  { name: 'Edit Post', path: '/social/posts/26/edit' },
  { name: 'Post Detail', path: '/social/posts/26' },
  { name: 'Accounts', path: '/social/accounts' },
  { name: 'Inbox', path: '/inbox' },
  { name: 'Calendar', path: '/calendar' },
  { name: 'Analytics', path: '/analytics' },
  { name: 'Campaigns', path: '/campaigns' },
  { name: 'Campaign Detail', path: '/campaigns/1' },
  { name: 'Clients', path: '/clients' },
  { name: 'Client Detail', path: '/clients/1' },
  { name: 'Content Library', path: '/content' },
  { name: 'Content Templates', path: '/content-templates' },
  { name: 'AI Content', path: '/ai' },
  { name: 'AI Credits', path: '/ai/credits' },
  { name: 'Workflows', path: '/workflows' },
  { name: 'A/B Tests', path: '/ab-tests' },
  { name: 'Billing', path: '/billing' },
  { name: 'Invoices', path: '/billing/invoices' },
  { name: 'Team', path: '/agency/team' },
  { name: 'Settings', path: '/agency/settings' },
  { name: 'Admin', path: '/admin' },
  { name: 'Activity Log', path: '/activity' },
  { name: 'Audit Trail', path: '/audit-trail' },
];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const results = [];

  for (const p of pages) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const errors = [];
    const failedReqs = [];

    page.on('pageerror', err => errors.push(err.message));
    page.on('console', msg => {
      if (msg.type() === 'error') {
        const t = msg.text();
        if (!t.includes('favicon') && !t.includes('FullCalendar') && !t.includes('cdn.jsdelivr.net')) {
          errors.push(t);
        }
      }
    });
    page.on('requestfailed', req => {
      const u = req.url();
      if (!u.includes('favicon') && !u.includes('cdn.jsdelivr.net')) {
        failedReqs.push(`${req.failure()?.errorText || ''} ${u}`);
      }
    });
    page.on('response', res => {
      if (res.status() >= 400 && !res.url().includes('favicon')) {
        failedReqs.push(`${res.status()} ${res.url()}`);
      }
    });

    try {
      // Login
      await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 20000 });
      await page.fill('form [name="email"]', LOGIN_EMAIL);
      await page.fill('form [name="password"]', LOGIN_PASSWORD);
      await page.click('form button[type="submit"]');
      await page.waitForURL(/\/dashboard/, { timeout: 15000 });

      // Navigate to target page
      await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle', timeout: 20000 });
      await page.waitForTimeout(2000);

      const data = await page.evaluate(() => {
        const main = document.querySelector('#main-content');
        const aside = document.querySelector('aside');
        const scrollW = document.documentElement.scrollWidth;
        const clientW = document.documentElement.clientWidth;
        const asideRect = aside ? aside.getBoundingClientRect() : null;
        const mainRect = main ? main.getBoundingClientRect() : null;
        const brokenImgs = Array.from(document.querySelectorAll('img')).filter(img => !img.complete || img.naturalWidth === 0).map(img => img.src);
        const zeroSizeEls = Array.from(document.querySelectorAll('div, span, td, th, a, button, input, select, textarea')).filter(el => {
          const r = el.getBoundingClientRect();
          return r.width === 0 && r.height === 0 && el.children.length === 0 && el.textContent.trim().length > 0;
        }).map(el => `${el.tagName}.${el.className} "${el.textContent.trim().substring(0, 30)}"`);
        const tables = document.querySelectorAll('table').length;
        const forms = document.querySelectorAll('form').length;
        const buttons = document.querySelectorAll('button').length;
        const labels = document.querySelectorAll('label').length;
        const inputs = document.querySelectorAll('input, select, textarea').length;
        const images = document.querySelectorAll('img').length;
        const bodyText = document.body.textContent;
        const hasH1 = !!document.querySelector('h1');
        const h1Text = hasH1 ? document.querySelector('h1').textContent.trim() : '';

        return {
          hScroll: scrollW > clientW + 2,
          scrollW, clientW,
          tables, forms, buttons, labels, images,
          brokenImgs, zeroSizeEls,
          bodyLen: bodyText.length,
          hasH1, h1Text,
          sidebar: asideRect ? { left: Math.round(asideRect.left), w: Math.round(asideRect.width), visible: asideRect.width > 0 } : null,
          main: mainRect ? { left: Math.round(mainRect.left), w: Math.round(mainRect.width) } : null,
          title: document.title,
          finalUrl: location.href,
          error: false,
        };
      });

      results.push({ ...p, ...data, errors: errors.length, failedReqs: failedReqs.length, errorDetails: errors.slice(0, 5), failedDetails: failedReqs.slice(0, 5) });
      console.log(`✓ ${p.name}: errors=${errors.length} failed=${failedReqs.length} hScroll=${data.hScroll} broken=${data.brokenImgs.length}`);
    } catch (e) {
      results.push({ ...p, error: e.message.substring(0, 120), errors: errors.length, failedReqs: failedReqs.length });
      console.log(`✗ ${p.name}: ERROR - ${e.message.substring(0, 80)}`);
    }

    await ctx.close();
  }

  await browser.close();

  // Save full audit results
  const outPath = 'test-results/full-page-audit.json';
  fs.writeFileSync(outPath, JSON.stringify(results, null, 2));
  console.log(`\nFull audit saved: ${outPath}`);

  // Summary
  const withErrors = results.filter(r => r.errors > 0 || r.failedReqs > 0);
  const withBrokenImgs = results.filter(r => r.brokenImgs && r.brokenImgs.length > 0);
  const withScroll = results.filter(r => r.hScroll);

  console.log(`\n===== AUDIT SUMMARY =====`);
  console.log(`Total pages audited: ${results.length}`);
  console.log(`Pages with failed requests: ${withErrors.length}`);
  console.log(`Pages with broken images: ${withBrokenImgs.length}`);
  console.log(`Pages with horizontal scroll: ${withScroll.length}`);

  if (withErrors.length > 0) {
    console.log(`\n--- Pages with failures ---`);
    withErrors.forEach(r => {
      console.log(`  ${r.name} (${r.path}): ${r.errors} errors, ${r.failedReqs} failed reqs`);
      r.errorDetails.slice(0, 3).forEach(e => console.log(`    ERR: ${e.substring(0, 150)}`));
      r.failedDetails.slice(0, 3).forEach(f => console.log(`    REQ: ${f.substring(0, 150)}`));
    });
  }

  if (withBrokenImgs.length > 0) {
    console.log(`\n--- Pages with broken images ---`);
    withBrokenImgs.forEach(r => {
      console.log(`  ${r.name}: ${r.brokenImgs.length} broken`);
      r.brokenImgs.slice(0, 3).forEach(src => console.log(`    IMG: ${src.substring(0, 150)}`));
    });
  }
})();
