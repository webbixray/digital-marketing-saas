/**
 * Batch UI Audit — Optimized (login once, reuse context, batch output)
 * Run with: node scripts/ui-audit-batch.cjs [batch_number]
 * Batches: 1=public+dashboard, 2=social+content, 3=campaigns+clients+ai, 4=workflows+agents+analytics, 5=email+billing+agency+admin
 */

const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8000';
const LOGIN_EMAIL = 'test@agency.com';
const LOGIN_PASSWORD = 'password';
const BATCH = parseInt(process.argv[2] || '1');

const BATCHES = {
  1: { name: 'public+dashboard', pages: [
    { name: 'Landing', path: '/', auth: false },
    { name: 'Pricing', path: '/pricing', auth: false },
    { name: 'Features', path: '/features', auth: false },
    { name: 'Login', path: '/login', auth: false },
    { name: 'Register', path: '/register', auth: false },
    { name: 'Dashboard', path: '/dashboard' },
    { name: 'Search', path: '/search' },
  ]},
  2: { name: 'social+content', pages: [
    { name: 'Social Posts', path: '/social/posts' },
    { name: 'Create Post', path: '/social/posts/create' },
    { name: 'Social Accounts', path: '/social/accounts' },
    { name: 'Create Account', path: '/social/accounts/create' },
    { name: 'Content Library', path: '/content' },
    { name: 'Create Content', path: '/content/create' },
    { name: 'Content Templates', path: '/content-templates' },
    { name: 'Create Template', path: '/content-templates/create' },
    { name: 'Media Library', path: '/media' },
    { name: 'Calendar', path: '/calendar' },
  ]},
  3: { name: 'campaigns+clients+ai', pages: [
    { name: 'Campaigns', path: '/campaigns' },
    { name: 'Create Campaign', path: '/campaigns/create' },
    { name: 'Clients', path: '/clients' },
    { name: 'Create Client', path: '/clients/create' },
    { name: 'AI Content', path: '/ai' },
    { name: 'AI Credits', path: '/ai/credits' },
    { name: 'A/B Tests', path: '/ab-testing' },
    { name: 'Create A/B Test', path: '/ab-testing/create' },
  ]},
  4: { name: 'workflows+agents+analytics', pages: [
    { name: 'Workflows', path: '/workflows' },
    { name: 'Workflow Builder', path: '/workflows/builder' },
    { name: 'Agents', path: '/agents' },
    { name: 'Agent Dashboard', path: '/agents/dashboard' },
    { name: 'Agent Workflows', path: '/agents/workflows' },
    { name: 'Analytics', path: '/analytics' },
    { name: 'Reports', path: '/reports' },
    { name: 'Create Report', path: '/reports/create' },
    { name: 'Unified Inbox', path: '/inbox' },
    { name: 'Activity Log', path: '/activity' },
  ]},
  5: { name: 'email+billing+agency+admin', pages: [
    { name: 'Email Campaigns', path: '/email/campaigns' },
    { name: 'Create Email Campaign', path: '/email/campaigns/create' },
    { name: 'Email Templates', path: '/templates' },
    { name: 'Create Email Template', path: '/templates/create' },
    { name: 'Billing', path: '/agency/billing' },
    { name: 'Billing Upgrade', path: '/agency/billing/upgrade' },
    { name: 'Invoices', path: '/agency/invoices' },
    { name: 'Agency Settings', path: '/agency/settings' },
    { name: 'Agency Team', path: '/agency/team' },
    { name: 'Roles', path: '/roles' },
    { name: 'Support Tickets', path: '/support' },
    { name: 'System Status', path: '/system-status' },
    { name: 'Admin Dashboard', path: '/admin' },
    { name: 'Failed Jobs', path: '/admin/failed-jobs' },
    { name: 'White Label', path: '/white-label' },
    { name: 'GDPR', path: '/gdpr' },
    { name: 'Referrals', path: '/referrals' },
    { name: 'Custom Fields', path: '/custom-fields' },
    { name: 'Feature Flags', path: '/feature-flags' },
    { name: 'Forms', path: '/forms' },
    { name: 'Landing Pages', path: '/landing-pages' },
    { name: 'Two-Factor', path: '/two-factor' },
    { name: 'Cancellation Survey', path: '/cancel' },
  ]},
};

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 375, height: 812 },
];

(async () => {
  const batch = BATCHES[BATCH];
  if (!batch) { console.log('Invalid batch number'); process.exit(1); }

  const browser = await chromium.launch({ headless: true });
  const allResults = [];
  const screenshotDir = path.join('test-results', 'ui-audit-screenshots');
  fs.mkdirSync(screenshotDir, { recursive: true });

  console.log(`\n=== BATCH ${BATCH}: ${batch.name} ===`);

  for (const vp of VIEWPORTS) {
    console.log(`\n--- Viewport: ${vp.name} (${vp.width}x${vp.height}) ---`);
    const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
    const page = await ctx.newPage();
    const errors = [], failedReqs = [], warnings = [];

    page.on('pageerror', err => errors.push({ msg: err.message, page: 'unknown' }));
    page.on('console', msg => {
      const t = msg.text();
      if (msg.type() === 'error') {
        if (!t.includes('favicon') && !t.includes('cdn.jsdelivr.net') && !t.includes('FullCalendar') && !t.includes('adminlte')) {
          errors.push({ msg: t, page: 'console' });
        }
      } else if (msg.type() === 'warning') {
        if (!t.includes('favicon') && !t.includes('cdn.jsdelivr.net') && !t.includes('FullCalendar') && !t.includes('adminlte') && !t.includes('DevTools') && !t.includes('React')) {
          warnings.push(t);
        }
      }
    });
    page.on('requestfailed', req => {
      const u = req.url();
      if (!u.includes('favicon') && !u.includes('cdn.jsdelivr.net') && !u.includes('fonts.googleapis') && !u.includes('fonts.gstatic')) {
        failedReqs.push(`${req.failure()?.errorText || 'FAILED'} ${u}`);
      }
    });
    page.on('response', res => {
      if (res.status() >= 400 && !res.url().includes('favicon') && !res.url().includes('cdn.jsdelivr.net')) {
        failedReqs.push(`${res.status()} ${res.url()}`);
      }
    });

    try {
      // Login once
      await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 20000 });
      await page.fill('form [name="email"]', LOGIN_EMAIL);
      await page.fill('form [name="password"]', LOGIN_PASSWORD);
      await page.click('form button[type="submit"]');
      await page.waitForURL(/\/dashboard/, { timeout: 15000 });

      for (const p of batch.pages) {
        if (p.auth === false) {
          // For public pages, we need a fresh context without auth — skip in this batch mode
          continue;
        }
        
        try {
          await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle', timeout: 20000 });
          await page.waitForTimeout(1500);

          // Capture per-page errors by resetting
          const pageErrors = [...errors], pageFailedReqs = [...failedReqs];
          errors.length = 0; failedReqs.length = 0;

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
            }).map(el => `${el.tagName}.${el.className.substring(0, 40)} "${el.textContent.trim().substring(0, 30)}"`);
            
            const emptyStates = Array.from(document.querySelectorAll('.empty-state, .no-data, .no-results, [class*="empty"], [class*="no-data"]')).map(el => el.textContent.trim().substring(0, 50));
            
            const tables = document.querySelectorAll('table').length;
            const tableRows = document.querySelectorAll('table tbody tr').length;
            const forms = document.querySelectorAll('form').length;
            const labels = document.querySelectorAll('label').length;
            const inputs = document.querySelectorAll('input, select, textarea').length;
            const buttons = document.querySelectorAll('button').length;
            const images = document.querySelectorAll('img').length;
            
            const hasH1 = !!document.querySelector('h1');
            const h1Text = hasH1 ? document.querySelector('h1').textContent.trim() : '';
            const h2Count = document.querySelectorAll('h2').length;
            
            const bodyText = document.body.textContent;
            const hasCard = document.querySelectorAll('.card, [class*="card"], [class*="panel"]').length;
            const hasTableResponsive = document.querySelectorAll('.table-responsive, [class*="table-wrapper"], .overflow-x-auto').length;
            
            const overflowElements = Array.from(document.querySelectorAll('*')).filter(el => {
              const r = el.getBoundingClientRect();
              return r.width > document.documentElement.clientWidth + 10 && el.children.length > 0;
            }).map(el => `${el.tagName}.${el.className.substring(0, 30)} w=${Math.round(r.width)}`);
            
            const truncatedText = Array.from(document.querySelectorAll('*')).filter(el => {
              return el.scrollWidth > el.clientWidth + 5 && el.children.length === 0 && el.textContent.trim().length > 20;
            }).map(el => `${el.tagName}.${el.className.substring(0, 30)} "${el.textContent.trim().substring(0, 30)}..."`);
            
            const missingAlt = Array.from(document.querySelectorAll('img')).filter(img => !img.alt || img.alt.trim() === '').map(img => img.src.substring(0, 80));
            
            const lowContrastEls = Array.from(document.querySelectorAll('p, span, a, button, td, th, li')).filter(el => {
              const style = window.getComputedStyle(el);
              const color = style.color;
              const bg = style.backgroundColor;
              if ((color.includes('200, 200') || color.includes('180, 180') || color.includes('160, 160') || color.includes('140, 140') || color.includes('120, 120') || color.includes('100, 100') || color.includes('gray') || color.includes('grey')) && (bg.includes('255, 255') || bg.includes('250, 250') || bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)')) {
                return true;
              }
              return false;
            }).map(el => `${el.tagName}.${el.className.substring(0, 30)} "${el.textContent.trim().substring(0, 30)}"`);
            
            const fakeButtons = Array.from(document.querySelectorAll('div[role="button"], span[role="button"], [class*="btn"], [class*="button"]')).filter(el => {
              const tag = el.tagName.toLowerCase();
              return tag !== 'button' && tag !== 'a' && !el.onclick && !el.getAttribute('tabindex');
            }).map(el => `${el.tagName}.${el.className.substring(0, 40)} "${el.textContent.trim().substring(0, 30)}"`);
            
            const inputsWithoutLabels = Array.from(document.querySelectorAll('input, select, textarea')).filter(input => {
              const id = input.id;
              const ariaLabel = input.getAttribute('aria-label');
              const ariaLabelledBy = input.getAttribute('aria-labelledby');
              const placeholder = input.placeholder;
              const hasLabel = id && document.querySelector(`label[for="${id}"]`);
              const parentLabel = input.closest('label');
              return !hasLabel && !parentLabel && !ariaLabel && !ariaLabelledBy && !placeholder && input.type !== 'hidden' && input.type !== 'submit' && input.type !== 'button';
            }).map(input => `${input.tagName} name="${input.name}" type="${input.type}"`);

            return {
              hScroll: scrollW > clientW + 2,
              scrollW, clientW, tables, tableRows, forms, buttons, labels, images,
              brokenImgs, zeroSizeEls, emptyStates,
              bodyLen: bodyText.length,
              hasH1, h1Text, h2Count,
              hasCard, hasTableResponsive,
              overflowElements: overflowElements.slice(0, 5),
              truncatedText: truncatedText.slice(0, 5),
              missingAlt: missingAlt.slice(0, 5),
              lowContrastEls: lowContrastEls.slice(0, 5),
              fakeButtons: fakeButtons.slice(0, 5),
              inputsWithoutLabels: inputsWithoutLabels.slice(0, 5),
              title: document.title,
              finalUrl: location.href,
            };
          });

          const safeName = p.name.replace(/[^a-z0-9]/gi, '_').toLowerCase();
          const screenshotPath = path.join(screenshotDir, `${vp.name}_${safeName}.png`);
          await page.screenshot({ path: screenshotPath, fullPage: false });

          allResults.push({
            ...p, viewport: vp.name, ...data,
            errors: pageErrors.length, errorDetails: pageErrors.slice(0, 5).map(e => typeof e === 'string' ? e : e.msg),
            warnings: warnings.length,
            failedReqs: pageFailedReqs.length, failedDetails: pageFailedReqs.slice(0, 5),
            screenshot: screenshotPath,
          });
          
          const status = pageErrors.length > 0 || pageFailedReqs.length > 0 ? '⚠' : '✓';
          console.log(`  ${status} ${p.name}: errors=${pageErrors.length} failed=${pageFailedReqs.length} scroll=${data.hScroll} overflow=${data.overflowElements.length}`);

        } catch (e) {
          allResults.push({ ...p, viewport: vp.name, error: e.message.substring(0, 120), errors: errors.length, failedReqs: failedReqs.length });
          console.log(`  ✗ ${p.name}: ERROR - ${e.message.substring(0, 80)}`);
        }
      }
    } finally {
      await ctx.close();
    }
  }

  await browser.close();

  const outPath = `test-results/ui-audit-batch-${BATCH}.json`;
  fs.writeFileSync(outPath, JSON.stringify(allResults, null, 2));
  
  // Also merge into master file
  const masterPath = 'test-results/ui-audit-master.json';
  let master = [];
  try { master = JSON.parse(fs.readFileSync(masterPath, 'utf8')); } catch {}
  
  // Remove any existing entries for this batch's pages and add new ones
  const batchPageNames = batch.pages.map(p => p.name);
  master = master.filter(r => !batchPageNames.includes(r.name));
  master.push(...allResults);
  fs.writeFileSync(masterPath, JSON.stringify(master, null, 2));

  console.log(`\nBatch ${BATCH} saved: ${outPath}`);
  console.log(`Master updated: ${masterPath} (${master.length} total entries)`);
  
  // Summary for this batch
  const withErrors = allResults.filter(r => r.errors > 0);
  const withFailedReqs = allResults.filter(r => r.failedReqs > 0);
  const withScroll = allResults.filter(r => r.hScroll);
  console.log(`\n  Batch ${BATCH} summary: ${allResults.length} audited, ${withErrors.length} with errors, ${withFailedReqs.length} with failed reqs, ${withScroll.length} with scroll`);
})();
