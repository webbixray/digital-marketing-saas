# Pilot UAT Plan — Digital Marketing SaaS

**Purpose:** Pilot user-acceptance testing with 2-3 simulated pilot agencies before commercial launch.
**Standard:** All UAT-01..UAT-14 scenarios must pass for the pilot to sign off.

## Pilot Agencies (simulated)
- **Pilot A** — "BrightAgency" (reseller/white-label): 4 users (owner, admin, 2 marketers), 3 clients, 2 social accounts (mocked), 1 active campaign
- **Pilot B** — "Nimbus Media": 2 users, email + social mix, Stripe subscription (mocked)

## Scenarios

### Onboarding & Access
- **UAT-01 Registration + agency creation:** register, agency auto-created, onboarding completes, dashboard loads.
- **UAT-02 Team & RBAC:** invite 3 members, assign roles (admin/marketer/viewer); viewer blocked from settings (403).
- **UAT-03 White-label (Pilot A):** configure custom branding/CSS; client portal renders agency branding.

### Content & Campaigns
- **UAT-04 AI content generation:** generate a social caption + blog post (AI gateway mocked with deterministic responses); content saved with audit log.
- **UAT-05 Social post scheduling:** create post for 2 platforms, schedule, confirm status transitions (scheduled → processing → published/failed with retry).
- **UAT-06 Email campaign:** build campaign, add recipients, send (mocked mailer), delivery tracking populated.
- **UAT-07 A/B test:** create A/B test on a post, run analysis endpoint, winner determination logic works.

### Agency Operations
- **UAT-08 Client portal:** client logs in, sees only own agency's approved reports; cross-agency access blocked (404/403).
- **UAT-09 Analytics:** cross-platform analytics dashboard loads with per-platform metrics; date range filter works; zero-metrics platforms render gracefully.
- **UAT-10 Reports & export:** generate report, export CSV/PDF; export file downloadable and valid.
- **UAT-11 Billing (Pilot B):** subscribe to starter plan, usage within quota; exceed quota → feature blocked; upgrade to enterprise → unblocked. Invoices list.
- **UAT-12 Workflow automation:** create simple workflow (trigger: post published → action: log), execution recorded.

### Security & Tenancy
- **UAT-13 Agency isolation:** Pilot B user GETs Pilot A post/client/campaign by id → 404; index lists contain only own agency. API route integrity test (RouteIntegrityTest) green.
- **UAT-14 Rate limiting + headers:** 11 rapid logins → 429; security headers present (CSP, X-Frame-Options, etc.); /api/health public, /admin/* blocked to non-admins.

## Environment
- Fresh MySQL (or sqlite) per pilot run, seeded via factories/seeders
- `php artisan serve` + curl-based execution scripts (scripts under docs/launch/uat/)
- AI/social/email integrations mocked (no external calls, no real costs)
- Results: PASS/FAIL per scenario + evidence

## Sign-off
Pilot UAT passes when 14/14 scenarios PASS, full backend+frontend suites green, no P0/P1 findings open.
