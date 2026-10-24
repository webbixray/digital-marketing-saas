# Pilot UAT Results — Digital Marketing SaaS

**Run:** 2026-10-24 05:42:41  
**Environment:** dev server :8082, sqlite, 4 workers; AI provider = env-configured (live call)  
**Summary:** 14 PASS / 0 FAIL / 2 REVIEW-SKIP of 16 scenarios

| # | Scenario | Result | Evidence |
|---|----------|--------|----------|
| UAT-01 | Registration + agency creation + auto-login | PASS | register POST=200 -> http://127.0.0.1:8082/email/verify, dashboard=200, logged_in=True, email=uat20261024-054202@brightagency.test |
| UAT-01b | Login session flow | PASS | login=200 -> http://127.0.0.1:8082/dashboard, dashboard=200 |
| UAT-02 | Team & RBAC (viewer blocked from billing) | PASS | roles=200, viewer billing=403 (expect 403/401) |
| UAT-01b | Login session flow | PASS | login=200 -> http://127.0.0.1:8082/dashboard, dashboard=200 |
| UAT-03 | White-label branding (web, owner) | PASS | get=200, post=200, persisted=True |
| UAT-04 | AI content generation (live provider) | REVIEW | status=500, len=0, sample='', err=All AI providers failed. Last error: unknown |
| UAT-05 | Social post scheduling | PASS | status=201, post_id=5, err= |
| UAT-06 | Email campaign create | PASS | status=201, campaign_id=5, err= |
| UAT-07 | A/B test via agent | REVIEW | status=500, resp={'success': False, 'error': 'All AI providers failed. Last error: unknown'} |
| UAT-08 | Client + portal | PASS | client=201 id=5, portal/analytics=200 |
| UAT-09 | Analytics endpoint | PASS | status=200, keys=['overview', 'social', 'email', 'financial', 'ai', 'engagement', 'campaigns', 'clients'] |
| UAT-10 | Report export (CSV) | PASS | status=200, sample="{'message': 'Export ready', 'url': '/storage/exports/agency_9_csv_1792795345.csv'}" |
| UAT-11 | Billing status (owner) | PASS | status=200, plan=invoices-list |
| UAT-12 | Workflow automation create | PASS | status=201, err= |
| UAT-13 | Cross-agency isolation | PASS | agencyB GET agencyA campaign 5 -> 404 (expect 404/403) |
| UAT-14 | Rate limiting + security headers + admin block | PASS | 11th login=429 (expect 429), sec-headers=True, guest /admin=302 (expect 302/403) |
