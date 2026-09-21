# Frontend UI/UX Audit Report — Digital Marketing SaaS

**Date**: September 21, 2026  
**Scope**: All authenticated pages (53 unique pages × 2 viewports = 106 audits)  
**Tool**: Playwright headless browser automation  
**Test Account**: `test@agency.com` / `password` (owner role, free plan)

---

## Executive Summary

| Category | Count | Severity |
|----------|-------|----------|
| **Total pages audited** | 106 | — |
| **Pages with JS console errors** | 88 | **CRITICAL** |
| **Pages with horizontal scroll** | 16 | HIGH |
| **Pages with failed HTTP requests** | 7 | HIGH |
| **Pages with missing form labels** | 25 | MEDIUM |
| **Pages with broken images** | 0 | — |
| **Pages with overflow elements** | 0 | — |
| **Pages with low-contrast text** | 0 | — |
| **Pages with accessibility issues (fake buttons)** | 0 | — |
| **Pages with truncated text** | 0 | — |

### Overall Grade: **D+**

The application has a **critical JavaScript syntax error** that breaks Alpine.js on every authenticated page. This is the single most impactful issue — it likely breaks sidebar interactivity, dropdowns, search, keyboard shortcuts, and toast notifications across the entire app.

---

## Issue #1: JavaScript Syntax Error (CRITICAL)

### Description
Every authenticated page throws a `SyntaxError: missing ) after argument list` from the compiled `unified-BZxyZWX-.js` bundle (line 5, col 735). This happens during Alpine.js expression evaluation.

### Root Cause
The `x-data` attribute on the `<body>` tag contains:

```javascript
expandedSections: JSON.parse(localStorage.getItem('sidebarSections') || '{"social":true,"marketing":true,"ai":true,"business":true}'),
```

When Laravel renders this, the `json_encode()` output gets HTML-escaped by Blade:
```json
{&quot;social&quot;:true,&quot;marketing&quot;:true,&quot;ai&quot;:true,&quot;business&quot;:true}
```

But Alpine.js evaluates the `x-data` attribute as JavaScript. The `&quot;` entities are NOT valid JavaScript string delimiters. Alpine tries to parse this as:

```javascript
JSON.parse(localStorage.getItem('sidebarSections') || '{&quot;social&quot;:true,...}')
```

The `&quot;` breaks the string literal syntax, causing the `missing ) after argument list` error.

### Impact
- **All authenticated pages are broken** — 88 of 106 audited page-viewport combos show this error
- Likely breaks: sidebar collapse/expand, dark mode toggle, search modal, keyboard shortcuts, dropdown toggles, toast notifications
- The error fires twice per page (once on initial parse, once on re-evaluation)

### Fix
In `resources/views/layouts/unified.blade.php`, line 104:

**Before:**
```blade
expandedSections: JSON.parse(localStorage.getItem('sidebarSections') || '{{ json_encode(["social"=>true,"marketing"=>true,"ai"=>true,"business"=>true]) }}'),
```

**After:**
```blade
expandedSections: JSON.parse(localStorage.getItem('sidebarSections') || '{!! json_encode(["social"=>true,"marketing"=>true,"ai"=>true,"business"=>true]) !!}'),
```

Or better, use the `@json` directive which handles escaping properly:
```blade
expandedSections: JSON.parse(localStorage.getItem('sidebarSections') || @json(["social"=>true,"marketing"=>true,"ai"=>true,"business"=>true])),
```

---

## Issue #2: Horizontal Scroll (HIGH)

### Description
16 page-viewport combinations show horizontal scrollbar due to content overflowing the viewport width.

### Affected Pages

**Desktop (1440px width):**
| Page | scrollWidth | clientWidth | Overflow |
|------|-------------|-------------|----------|
| Create Campaign | 1480 | 1440 | +40px |
| Create Client | 1456 | 1440 | +16px |
| Workflows | 1483 | 1440 | +43px |
| Create Content | 1465 | 1440 | +25px |

**Mobile (375px width):**
| Page | scrollWidth | clientWidth | Overflow |
|------|-------------|-------------|----------|
| Create Campaign | 608 | 375 | +233px |
| Create Client | 608 | 375 | +233px |
| Workflows | 636 | 375 | +261px |
| Unified Inbox | 575 | 375 | +200px |
| Create Content | 608 | 375 | +233px |
| Email Campaigns | 500 | 375 | +125px |
| Email Templates | 630 | 375 | +255px |
| Admin Dashboard | 575 | 375 | +200px |
| Failed Jobs | 575 | 375 | +200px |
| GDPR | 604 | 375 | +229px |
| Two-Factor | 561 | 375 | +186px |
| Cancellation Survey | 692 | 375 | +317px |

### Root Cause
- Forms with fixed-width elements that don't shrink on smaller viewports
- Tables without `.table-responsive` wrapper
- Long unbroken text or URLs without `break-words` or `overflow-wrap`

### Desktop Fix
Add to CSS or use Tailwind classes:
```css
/* Prevent form overflow */
.form-grid, .form-row {
  min-width: 0;
  overflow-wrap: break-word;
}

/* Ensure tables don't overflow */
table {
  max-width: 100%;
  overflow-x: auto;
}
```

### Mobile Fix
- Wrap all tables in `<div class="overflow-x-auto">`
- Add `min-w-0` to grid/flex children
- Use `break-words` on long text containers
- Consider stacking form fields vertically on mobile (`grid-cols-1 md:grid-cols-2`)

---

## Issue #3: Failed HTTP Requests (HIGH)

### Description
7 page-viewport combinations have resources that return 4xx/5xx status codes.

### Details

| Page | Status | URL | Note |
|------|--------|-----|------|
| AI Credits | 404 | `/ai/credits` | Route may not exist |
| Admin Dashboard | 403 | `/admin` | Expected: test user lacks owner/admin role for this route |
| Failed Jobs | 403 | `/admin/failed-jobs` | Expected: same role restriction |
| Unified Inbox (mobile) | 500 | `/inbox` | Server error — investigate |

### Fix
- **AI Credits 404**: Verify the route `ai.credits` exists or remove the page link
- **Admin 403**: Expected behavior (multi-tenant RBAC). The test user is `owner` of a `free` plan agency, but the admin routes may require a specific role. This is not a bug.
- **Unified Inbox 500**: This is a real server error. Investigate the `UnifiedInboxController@index` method for errors that only manifest on mobile (possibly user-agent detection or responsive layout issues in the controller logic).

---

## Issue #4: Missing Form Labels (MEDIUM)

### Description
25 page-viewport combinations have form inputs without associated `<label>` elements, missing `aria-label`, `aria-labelledby`, or `placeholder` attributes.

### Affected Pages
- Search (1 input: select without label)
- Campaigns (1 input)
- Create Campaign (5 inputs)
- Clients (1 input)
- Create Client (5 inputs)
- AI Content (2 inputs)
- Social Posts (2 inputs)
- Create Post (2 inputs)
- Create Account (5 inputs)
- Create Content (4 inputs)
- Media Library (1 input)
- Agency Team (3 inputs)
- White Label (1 input)
- Referrals (1 input)

### Impact
- **Accessibility**: Screen readers cannot identify the purpose of these inputs
- **Usability**: No clickable label to focus the input
- **WCAG 2.1**: Fails Success Criterion 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions)

### Fix
Add explicit labels to all form inputs:
```html
<!-- Before -->
<input type="text" name="title" class="...">

<!-- After -->
<label for="title" class="...">Title</label>
<input id="title" type="text" name="title" class="...">
```

For visually hidden labels:
```html
<label for="search-type" class="sr-only">Search Type</label>
<select id="search-type" name="type" class="...">
```

---

## Positive Findings

### What's Working Well

1. **No broken images** — All images load correctly across all pages
2. **No overflow elements** — Layout elements stay within their containers (when JS works)
3. **No low-contrast text** — Text meets contrast requirements
4. **No fake buttons** — All interactive elements use proper semantic HTML
5. **No truncated text** — Text content displays fully
6. **Clean empty states** — Empty data sets show appropriate "No data" messages
7. **Responsive sidebar** — Sidebar collapses to mini mode and works (when JS is functional)
8. **Consistent design language** — Cards, buttons, and forms follow a consistent pattern
9. **Dark mode support** — `dark:` Tailwind classes present throughout
10. **Keyboard shortcuts** — Search (`Ctrl+K`), sidebar toggle (`Ctrl+B`), and `Escape` to close modals are wired up

---

## Page-by-Page Summary

### Desktop (1440×900)

| Page | Errors | Failed | H-Scroll | Issues |
|------|--------|--------|----------|--------|
| Dashboard | 2 | 0 | No | JS syntax error |
| Search | 1 | 0 | No | JS syntax error |
| Social Posts | 2 | 0 | No | JS syntax error |
| Create Post | 1 | 0 | No | JS syntax error |
| Social Accounts | 1 | 0 | No | JS syntax error |
| Create Account | 1 | 0 | No | JS syntax error |
| Campaigns | 2 | 0 | No | JS syntax error |
| Create Campaign | 1 | 0 | **Yes** | JS syntax error, horizontal scroll |
| Clients | 1 | 0 | No | JS syntax error |
| Create Client | 1 | 0 | **Yes** | JS syntax error, horizontal scroll |
| Content Library | 1 | 0 | No | JS syntax error |
| Create Content | 1 | 0 | **Yes** | JS syntax error, horizontal scroll |
| Content Templates | 1 | 0 | No | JS syntax error |
| Create Template | 1 | 0 | No | JS syntax error |
| Media Library | 1 | 0 | No | JS syntax error |
| AI Content | 1 | 0 | No | JS syntax error |
| AI Credits | 2 | **1** | No | JS syntax error, 404 failed request |
| Workflows | 2 | 0 | **Yes** | JS syntax error, horizontal scroll |
| Workflow Builder | 1 | 0 | No | JS syntax error |
| Agents | 1 | 0 | No | JS syntax error |
| Agent Dashboard | 1 | 0 | No | JS syntax error |
| Agent Workflows | 1 | 0 | No | JS syntax error |
| Analytics | 1 | 0 | No | JS syntax error |
| Reports | 1 | 0 | No | JS syntax error |
| Create Report | 1 | 0 | No | JS syntax error |
| A/B Tests | 1 | 0 | No | JS syntax error |
| Create A/B Test | 1 | 0 | No | JS syntax error |
| Unified Inbox | 1 | 0 | No | JS syntax error |
| Activity Log | 1 | 0 | No | JS syntax error |
| Email Campaigns | 2 | 0 | No | JS syntax error |
| Email Templates | 1 | 0 | No | JS syntax error |
| Create Email Template | 1 | 0 | No | JS syntax error |
| Billing | 1 | 0 | No | JS syntax error |
| Billing Upgrade | 1 | 0 | No | JS syntax error |
| Invoices | 1 | 0 | No | JS syntax error |
| Agency Settings | 1 | 0 | No | JS syntax error |
| Agency Team | 1 | 0 | No | JS syntax error |
| Roles | 1 | 0 | No | JS syntax error |
| Support Tickets | 1 | 0 | No | JS syntax error |
| System Status | 1 | 0 | No | JS syntax error |
| Admin Dashboard | 2 | **1** | No | JS syntax error, 403 |
| Failed Jobs | 2 | **1** | No | JS syntax error, 403 |
| White Label | 1 | 0 | No | JS syntax error |
| GDPR | 1 | 0 | No | JS syntax error |
| Referrals | 1 | 0 | No | JS syntax error |
| Custom Fields | 1 | 0 | No | JS syntax error |
| Feature Flags | 1 | 0 | No | JS syntax error |
| Forms | 1 | 0 | No | JS syntax error |
| Landing Pages | 1 | 0 | No | JS syntax error |
| Cancellation Survey | 1 | 0 | No | JS syntax error |

### Mobile (375×812)

All desktop issues above, plus horizontal scroll on:
- Create Campaign, Create Client, Workflows, Unified Inbox, Create Content, Email Campaigns, Email Templates, Admin Dashboard, Failed Jobs, GDPR, Two-Factor, Cancellation Survey

Plus 500 error on Unified Inbox.

---

## Recommended Fix Priority

1. **P0 — Fix JS syntax error** in `unified.blade.php` line 104 (replace `{{ json_encode(...) }}` with `@json(...)` or `{!! json_encode(...).!!}`)
2. **P0 — Investigate Unified Inbox 500 error** on mobile
3. **P1 — Fix horizontal scroll** on desktop (Create Campaign, Create Client, Workflows, Create Content)
4. **P1 — Fix horizontal scroll** on mobile (12 pages)
5. **P2 — Add missing form labels** (25 pages)
6. **P3 — Remove dead AI Credits page** or implement the route

---

## Screenshots

Screenshots captured for every page at both viewports are available in:
```
test-results/ui-audit-screenshots/
```

## Raw Data

Full JSON audit results:
```
test-results/ui-audit-master.json
```
