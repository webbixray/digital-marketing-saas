# Staging Load Test Execution
# Digital Marketing SaaS Platform
#
# This document describes how to run load tests against the staging environment
# to validate performance before production deployment.

# ============================================================================
# 1. PREREQUISITES
# ============================================================================

- Staging environment deployed and accessible.
- k6 installed locally: https://k6.io/docs/getting-started/install/
- API token for an authenticated test agency.
- BASE_URL pointing to staging environment.

# ============================================================================
# 2. RUN LOAD TESTS LOCALLY
# ============================================================================

## Smoke test (quick validation)
```bash
cd /root/webbixray/projects/digital-marketing-saas

k6 run \
  --env BASE_URL=https://staging.digitalmarketingsaas.com \
  --env API_KEY=pk_test_... \
  --env ENVIRONMENT=staging \
  tests/performance/load-test.js
```

## Full load test (50 → 100 users)
```bash
k6 run \
  --env BASE_URL=https://staging.digitalmarketingsaas.com \
  --env API_KEY=pk_test_... \
  --env ENVIRONMENT=staging \
  tests/performance/load-test.js
```

# ============================================================================
# 3. RUN LOAD TESTS IN CI (GitHub Actions)
# ============================================================================

The load test workflow runs automatically on push to main/develop and on
pull requests. To run manually:

1. Go to GitHub Actions → "Load Testing" workflow.
2. Click "Run workflow".
3. Enter BASE_URL (staging URL) and API_KEY (optional, for authenticated tests).
4. Click "Run workflow".
5. Download results artifact (k6-results) after completion.

# ============================================================================
# 4. PERFORMANCE THRESHOLDS
# ============================================================================

| Metric | Threshold | Status |
|--------|-----------|--------|
| p95 response time | < 500ms | Must pass |
| Error rate | < 1% | Must pass |
| Check pass rate | > 95% | Must pass |

# ============================================================================
# 5. INTERPRETING RESULTS
# ============================================================================

## Pass
- All thresholds met.
- No 5xx errors.
- Check pass rate > 95%.
- Action: Safe to deploy to production.

## Warn
- p95 response time between 500ms-1s.
- Error rate between 1%-3%.
- Action: Investigate before deploying; monitor closely post-deploy.

## Fail
- p95 response time > 1s.
- Error rate > 3%.
- Check pass rate < 95%.
- Action: Do not deploy. Investigate and fix performance issues.

# ============================================================================
# 6. TROUBLESHOOTING
# ============================================================================

| Issue | Cause | Fix |
|-------|-------|-----|
| Connection refused | Staging not accessible | Check VPN/firewall, verify BASE_URL |
| 401 Unauthorized | Invalid API key | Regenerate token, update API_KEY env |
| 429 Rate limited | Too many requests | Reduce VUs, increase ramp time |
| 5xx errors | Staging errors | Check staging logs, Sentry |

# ============================================================================
# 7. PLAYWRIGHT E2E VERIFICATION
# ============================================================================

Playwright E2E tests run automatically on every push and PR via the
"Frontend Tests" GitHub Actions workflow. To run locally:

```bash
# Install Playwright browsers
npx playwright install --with-deps chromium

# Build frontend assets
npm run build

# Run E2E tests
npm run test:e2e
```

To run E2E against staging:
```bash
BASE_URL=https://staging.digitalmarketingsaas.com npm run test:e2e
```
