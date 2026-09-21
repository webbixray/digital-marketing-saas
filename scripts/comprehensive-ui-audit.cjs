/**
 * Comprehensive Frontend UI/UX Audit — Digital Marketing SaaS
 * 
 * Audits ALL pages across 3 viewports (desktop, tablet, mobile).
 * Captures screenshots, console errors, failed requests, layout issues,
 * and common UI/UX problems.
 */

const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8000';
const LOGIN_EMAIL = 'test@agency.com';
const LOGIN_PASSWORD = 'password';

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'mobile', width: 375, height: 812 },
];

// Complete page inventory — public + authenticated
const pages = [
  // Public pages
  { name: 'Landing', path: '/', auth: false },
  { name: 'Pricing', path: '/pricing', auth: false },
  { name: 'Features', path: '/features', auth: false },
  { name: 'Login', path: '/login', auth: false },
  { name: 'Register', path: '/register', auth: false },
  
  // Dashboard
  { name: 'Dashboard', path: '/dashboard' },
  
  // Social
  { name: 'Social Posts', path: '/social/posts' },
  { name: 'Create Post', path: '/social/posts/create' },
  { name: 'Social Accounts', path: '/social/accounts' },
  { name: 'Create Account', path: '/social/accounts/create' },
  
  // Campaigns
  { name: 'Campaigns', path: '/campaigns' },
  { name: 'Create Campaign', path: '/campaigns/create' },
  
  // Clients
  { name: 'Clients', path: '/clients' },
  { name: 'Create Client', path: '/clients/create' },
  
  // Content
  { name: 'Content Library', path: '/content' },
  { name: 'Create Content', path: '/content/create' },
  { name: 'Content Templates', path: '/content-templates' },
  { name: 'Create Template', path: '/content-templates/create' },
  { name: 'Media Library', path: '/media' },
  
  // AI
  { name: 'AI Content', path: '/ai' },
  { name: 'AI Credits', path: '/ai/credits' },
  
  // Workflows
  { name: 'Workflows', path: '/workflows' },
  { name: 'Workflow Builder', path: '/workflows/builder' },
  
  // Agents
  { name: 'Agents', path: '/agents' },
  { name: 'Agent Dashboard', path: '/agents/dashboard' },
  { name: 'Agent Workflows', path: '/agents/workflows' },
  
  // Analytics & Reports
  { name: 'Analytics', path: '/analytics' },
  { name: 'Reports', path: '/reports' },
  { name: 'Create Report', path: '/reports/create' },
  
  // A/B Testing
  { name: 'A/B Tests', path: '/ab-testing' },
  { name: 'Create A/B Test', path: '/ab-testing/create' },
  
  // Email
  { name: 'Email Campaigns', path: '/email/campaigns' },
  { name: 'Create Email Campaign', path: '/email/campaigns/create' },
  { name: 'Email Templates', path: '/templates' },
  { name: 'Create Email Template', path: '/templates/create' },
  
  // Inbox & Activity
  { name: 'Unified Inbox', path: '/inbox' },
  { name: 'Activity Log', path: '/activity' },
  { name: 'Team Activity', path: '/team-activity' },
  { name: 'Comments', path: '/comments' },
  
  // Calendar
  { name: 'Calendar', path: '/calendar' },
  
  // Billing
  { name: 'Billing', path: '/agency/billing' },
  { name: 'Billing Upgrade', path: '/agency/billing/upgrade' },
  { name: 'Invoices', path: '/agency/invoices' },
  
  // Agency
  { name: 'Agency Settings', path: '/agency/settings' },
  { name: 'Agency Team', path: '/agency/team' },
  
  // Roles & Permissions
  { name: 'Roles', path: '/roles' },
  { name: 'Create Role', path: '/roles/create' },
  
  // Support & System
  { name: 'Support Tickets', path: '/support' },
  { name: 'Create Ticket', path: '/support/create' },
  { name: 'System Status', path: '/system-status' },
  { name: 'System Backup', path: '/system-backup' },
  
  // Forms & Landing Pages
  { name: 'Forms', path: '/forms' },
  { name: 'Create Form', path: '/forms/create' },
  { name: 'Landing Pages', path: '/landing-pages' },
  { name: 'Create Landing Page', path: '/landing-pages/create' },
  
  // Custom Fields
  { name: 'Custom Fields', path: '/custom-fields' },
  { name: 'Create Custom Field', path: '/custom-fields/create' },
  
  // Feature Flags
  { name: 'Feature Flags', path: '/feature-flags' },
  { name: 'Create Feature Flag', path: '/feature-flags/create' },
  
  // Referrals & GDPR
  { name: 'Referrals', path: '/referrals' },
  { name: 'GDPR', path: '/gdpr' },
  
  // Admin
  { name: 'Admin Dashboard', path: '/admin' },
  { name: 'Admin Health', path: '/admin/health' },
  { name: 'Failed Jobs', path: '/admin/failed-jobs' },
  
  // Approvals
  { name: 'Pending Approvals', path: '/approvals/pending' },
  
  // Onboarding
  { name: 'Onboarding', path: '/onboarding' },
  
  // 2FA
  { name: 'Two-Factor', path: '/two-factor' },
  
  // White Label
  { name: 'White Label', path: '/white-label' },
  
  // Search
  { name: 'Search', path: '/search' },
  
  // Cancellation
  { name: 'Cancellation Survey', path: '/cancel' },
];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const allResults = [];
  const screenshotDir = path.join('test-results', 'ui-audit-screenshots');
  fs.mkdirSync(screenshotDir, { recursive: true });

  // Only test desktop for speed; spot-check mobile on key pages
  const viewports = [
    { name: 'desktop', width: 1440, height: 900 },
    { name: 'mobile', width: 375, height: 812 },
  ];

  for (const vp of viewports) {
    console.log(`\n=== Viewport: ${vp.name} (${vp.width}x${vp.height}) ===`);
    
    for (const p of pages) {
      const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
      const page = await ctx.newPage();
      const errors = [];
      const failedReqs = [];
      const warnings = [];

      page.on('pageerror', err => errors.push(err.message));
      page.on('console', msg => {
        if (msg.type() === 'error') {
          const t = msg.text();
          if (!t.includes('favicon') && !t.includes('cdn.jsdelivr.net') && !t.includes('FullCalendar') && !t.includes('adminlte')) {
            errors.push(t);
          }
        }
        if (msg.type() === 'warning') {
          const t = msg.text();
          if (!t.includes('favicon') && !t.includes('cdn.jsdelivr.net') && !t.includes('FullCalendar') && !t.includes('adminlte') && !t.includes('DevTools')) {
            warnings.push(t);
          }
        }
      });
      page.on('requestfailed', req => {
        const u = req.url();
        if (!u.includes('favicon') && !u.includes('cdn.jsdelivr.net') && !u.includes('fonts.googleapis.com') && !u.includes('fonts.gstatic.com')) {
          failedReqs.push(`${req.failure()?.errorText || 'FAILED'} ${u}`);
        }
      });
      page.on('response', res => {
        if (res.status() >= 400 && !res.url().includes('favicon') && !res.url().includes('cdn.jsdelivr.net')) {
          failedReqs.push(`${res.status()} ${res.url()}`);
        }
      });

      try {
        // Login if needed
        if (p.auth !== false) {
          await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 20000 });
          await page.fill('form [name="email"]', LOGIN_EMAIL);
          await page.fill('form [name="password"]', LOGIN_PASSWORD);
          await page.click('form button[type="submit"]');
          await page.waitForURL(/\/dashboard/, { timeout: 15000 });
        }

        // Navigate to target page
        await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle', timeout: 20000 });
        await page.waitForTimeout(2500);

        // Comprehensive page analysis
        const data = await page.evaluate(() => {
          const main = document.querySelector('#main-content');
          const aside = document.querySelector('aside');
          const scrollW = document.documentElement.scrollWidth;
          const clientW = document.documentElement.clientWidth;
          const asideRect = aside ? aside.getBoundingClientRect() : null;
          const mainRect = main ? main.getBoundingClientRect() : null;
          
          // Broken images
          const brokenImgs = Array.from(document.querySelectorAll('img')).filter(img => !img.complete || img.naturalWidth === 0).map(img => img.src);
          
          // Zero-size elements with text
          const zeroSizeEls = Array.from(document.querySelectorAll('div, span, td, th, a, button, input, select, textarea')).filter(el => {
            const r = el.getBoundingClientRect();
            return r.width === 0 && r.height === 0 && el.children.length === 0 && el.textContent.trim().length > 0;
          }).map(el => `${el.tagName}.${el.className.substring(0, 40)} "${el.textContent.trim().substring(0, 30)}"`);
          
          // Empty state detection
          const emptyStates = Array.from(document.querySelectorAll('.empty-state, .no-data, .no-results, [class*="empty"], [class*="no-data"]')).map(el => el.textContent.trim().substring(0, 50));
          
          // Tables
          const tables = document.querySelectorAll('table').length;
          const tableRows = document.querySelectorAll('table tbody tr').length;
          
          // Forms
          const forms = document.querySelectorAll('form').length;
          const labels = document.querySelectorAll('label').length;
          const inputs = document.querySelectorAll('input, select, textarea').length;
          
          // Buttons
          const buttons = document.querySelectorAll('button').length;
          const primaryBtns = document.querySelectorAll('button[class*="primary"], button[class*="btn-primary"], a[class*="primary"], a[class*="btn-primary"]').length;
          
          // Images
          const images = document.querySelectorAll('img').length;
          
          // Headings
          const hasH1 = !!document.querySelector('h1');
          const h1Text = hasH1 ? document.querySelector('h1').textContent.trim() : '';
          const h2Count = document.querySelectorAll('h2').length;
          const h3Count = document.querySelectorAll('h3').length;
          
          // Body text
          const bodyText = document.body.textContent;
          
          // Check for common UI issues
          const hasCard = document.querySelectorAll('.card, [class*="card"], [class*="panel"]').length;
          const hasTableResponsive = document.querySelectorAll('.table-responsive, [class*="table-wrapper"], .overflow-x-auto').length;
          const hasSidebar = !!document.querySelector('aside, [class*="sidebar"], nav[class*="side"]');
          
          // Check for overflow issues
          const overflowElements = Array.from(document.querySelectorAll('*')).filter(el => {
            const r = el.getBoundingClientRect();
            return r.width > document.documentElement.clientWidth + 10 && el.children.length > 0;
          }).map(el => `${el.tagName}.${el.className.substring(0, 30)} w=${Math.round(r.width)}`);
          
          // Check for text truncation issues
          const truncatedText = Array.from(document.querySelectorAll('*')).filter(el => {
            return el.scrollWidth > el.clientWidth + 5 && el.children.length === 0 && el.textContent.trim().length > 20;
          }).map(el => `${el.tagName}.${el.className.substring(0, 30)} "${el.textContent.trim().substring(0, 30)}..."`);
          
          // Check for missing alt text on images
          const missingAlt = Array.from(document.querySelectorAll('img')).filter(img => !img.alt || img.alt.trim() === '').map(img => img.src.substring(0, 80));
          
          // Check for low contrast (basic heuristic)
          const lowContrastEls = Array.from(document.querySelectorAll('p, span, a, button, td, th, li')).filter(el => {
            const style = window.getComputedStyle(el);
            const color = style.color;
            const bg = style.backgroundColor;
            // Simple heuristic: light gray text on white background
            if ((color.includes('200, 200') || color.includes('180, 180') || color.includes('160, 160') || color.includes('140, 140') || color.includes('120, 120') || color.includes('100, 100') || color.includes('gray') || color.includes('grey')) && (bg.includes('255, 255') || bg.includes('250, 250') || bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)')) {
              return true;
            }
            return false;
          }).map(el => `${el.tagName}.${el.className.substring(0, 30)} "${el.textContent.trim().substring(0, 30)}"`);
          
          // Check for unclickable elements that look like buttons
          const fakeButtons = Array.from(document.querySelectorAll('div[role="button"], span[role="button"], [class*="btn"], [class*="button"]')).filter(el => {
            const tag = el.tagName.toLowerCase();
            return tag !== 'button' && tag !== 'a' && !el.onclick && !el.getAttribute('tabindex');
          }).map(el => `${el.tagName}.${el.className.substring(0, 40)} "${el.textContent.trim().substring(0, 30)}"`);
          
          // Check for missing form labels
          const inputsWithoutLabels = Array.from(document.querySelectorAll('input, select, textarea')).filter(input => {
            const id = input.id;
            const ariaLabel = input.getAttribute('aria-label');
            const ariaLabelledBy = input.getAttribute('aria-labelledby');
            const placeholder = input.placeholder;
            const hasLabel = id && document.querySelector(`label[for="${id}"]`);
            const parentLabel = input.closest('label');
            return !hasLabel && !parentLabel && !ariaLabel && !ariaLabelledBy && !placeholder && input.type !== 'hidden' && input.type !== 'submit';
          }).map(input => `${input.tagName} name="${input.name}" type="${input.type}"`);

          return {
            hScroll: scrollW > clientW + 2,
            scrollW, clientW,
            tables, tableRows, forms, buttons, primaryBtns, labels, images,
            brokenImgs, zeroSizeEls, emptyStates,
            bodyLen: bodyText.length,
            hasH1, h1Text, h2Count, h3Count,
            hasCard, hasTableResponsive, hasSidebar,
            overflowElements: overflowElements.slice(0, 5),
            truncatedText: truncatedText.slice(0, 5),
            missingAlt: missingAlt.slice(0, 5),
            lowContrastEls: lowContrastEls.slice(0, 5),
            fakeButtons: fakeButtons.slice(0, 5),
            inputsWithoutLabels: inputsWithoutLabels.slice(0, 5),
            title: document.title,
            finalUrl: location.href,
            error: false,
          };
        });

        // Capture screenshot
        const safeName = p.name.replace(/[^a-z0-9]/gi, '_').toLowerCase();
        const screenshotPath = path.join(screenshotDir, `${vp.name}_${safeName}.png`);
        await page.screenshot({ path: screenshotPath, fullPage: false });

        allResults.push({
          ...p,
          viewport: vp.name,
          ...data,
          errors: errors.length,
          warnings: warnings.length,
          errorDetails: errors.slice(0, 5),
          warningDetails: warnings.slice(0, 3),
          failedReqs: failedReqs.length,
          failedDetails: failedReqs.slice(0, 5),
          screenshot: screenshotPath,
        });
        
        const status = errors.length > 0 || failedReqs.length > 0 ? '⚠' : '✓';
        console.log(`  ${status} ${p.name} (${vp.name}): errors=${errors.length} failed=${failedReqs.length} hScroll=${data.hScroll} broken=${data.brokenImgs.length} overflow=${data.overflowElements.length}`);
      } catch (e) {
        allResults.push({ ...p, viewport: vp.name, error: e.message.substring(0, 120), errors: errors.length, failedReqs: failedReqs.length });
        console.log(`  ✗ ${p.name} (${vp.name}): ERROR - ${e.message.substring(0, 80)}`);
      }

      await ctx.close();
    }
  }

  await browser.close();

  // Save full audit results
  const outPath = 'test-results/comprehensive-ui-audit.json';
  fs.writeFileSync(outPath, JSON.stringify(allResults, null, 2));
  console.log(`\nFull audit saved: ${outPath}`);

  // Summary
  const totalPages = allResults.length;
  const withErrors = allResults.filter(r => r.errors > 0);
  const withFailedReqs = allResults.filter(r => r.failedReqs > 0);
  const withBrokenImgs = allResults.filter(r => r.brokenImgs && r.brokenImgs.length > 0);
  const withScroll = allResults.filter(r => r.hScroll);
  const withOverflow = allResults.filter(r => r.overflowElements && r.overflowElements.length > 0);
  const withMissingAlt = allResults.filter(r => r.missingAlt && r.missingAlt.length > 0);
  const withLowContrast = allResults.filter(r => r.lowContrastEls && r.lowContrastEls.length > 0);
  const withFakeButtons = allResults.filter(r => r.fakeButtons && r.fakeButtons.length > 0);
  const withMissingLabels = allResults.filter(r => r.inputsWithoutLabels && r.inputsWithoutLabels.length > 0);
  const withTruncated = allResults.filter(r => r.truncatedText && r.truncatedText.length > 0);

  console.log(`\n${'='.repeat(60)}`);
  console.log(`COMPREHENSIVE UI/UX AUDIT SUMMARY`);
  console.log(`${'='.repeat(60)}`);
  console.log(`Total page-viewport combos audited: ${totalPages}`);
  console.log(`Pages with console errors: ${withErrors.length}`);
  console.log(`Pages with failed requests: ${withFailedReqs.length}`);
  console.log(`Pages with broken images: ${withBrokenImgs.length}`);
  console.log(`Pages with horizontal scroll: ${withScroll.length}`);
  console.log(`Pages with overflow elements: ${withOverflow.length}`);
  console.log(`Pages with missing alt text: ${withMissingAlt.length}`);
  console.log(`Pages with low contrast text: ${withLowContrast.length}`);
  console.log(`Pages with fake buttons: ${withFakeButtons.length}`);
  console.log(`Pages with missing form labels: ${withMissingLabels.length}`);
  console.log(`Pages with truncated text: ${withTruncated.length}`);

  if (withErrors.length > 0) {
    console.log(`\n--- Pages with console errors ---`);
    withErrors.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): ${r.errors} errors`);
      r.errorDetails.slice(0, 3).forEach(e => console.log(`    ERR: ${e.substring(0, 150)}`));
    });
  }

  if (withFailedReqs.length > 0) {
    console.log(`\n--- Pages with failed requests ---`);
    withFailedReqs.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): ${r.failedReqs} failed`);
      r.failedDetails.slice(0, 3).forEach(f => console.log(`    REQ: ${f.substring(0, 150)}`));
    });
  }

  if (withBrokenImgs.length > 0) {
    console.log(`\n--- Pages with broken images ---`);
    withBrokenImgs.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): ${r.brokenImgs.length} broken`);
      r.brokenImgs.slice(0, 3).forEach(src => console.log(`    IMG: ${src.substring(0, 150)}`));
    });
  }

  if (withScroll.length > 0) {
    console.log(`\n--- Pages with horizontal scroll ---`);
    withScroll.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): scrollW=${r.scrollW} clientW=${r.clientW}`);
    });
  }

  if (withOverflow.length > 0) {
    console.log(`\n--- Pages with overflow elements ---`);
    withOverflow.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}):`);
      r.overflowElements.slice(0, 3).forEach(e => console.log(`    OVERFLOW: ${e}`));
    });
  }

  if (withMissingAlt.length > 0) {
    console.log(`\n--- Pages with missing alt text ---`);
    withMissingAlt.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): ${r.missingAlt.length} images missing alt`);
    });
  }

  if (withLowContrast.length > 0) {
    console.log(`\n--- Pages with low contrast text ---`);
    withLowContrast.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): ${r.lowContrastEls.length} elements`);
      r.lowContrastEls.slice(0, 2).forEach(e => console.log(`    LOW CONTRAST: ${e}`));
    });
  }

  if (withFakeButtons.length > 0) {
    console.log(`\n--- Pages with fake buttons (div/span styled as button) ---`);
    withFakeButtons.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): ${r.fakeButtons.length} elements`);
      r.fakeButtons.slice(0, 2).forEach(e => console.log(`    FAKE BTN: ${e}`));
    });
  }

  if (withMissingLabels.length > 0) {
    console.log(`\n--- Pages with inputs missing labels ---`);
    withMissingLabels.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}): ${r.inputsWithoutLabels.length} inputs`);
      r.inputsWithoutLabels.slice(0, 2).forEach(e => console.log(`    NO LABEL: ${e}`));
    });
  }

  if (withTruncated.length > 0) {
    console.log(`\n--- Pages with truncated text ---`);
    withTruncated.forEach(r => {
      console.log(`  ${r.name} (${r.viewport}):`);
      r.truncatedText.slice(0, 2).forEach(e => console.log(`    TRUNCATED: ${e}`));
    });
  }
})();
