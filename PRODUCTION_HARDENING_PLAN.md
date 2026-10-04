# Digital Marketing SaaS - Production Hardening & Maintenance Plan

**Status:** Based on comprehensive analysis (Security Audit, PHPStan L8, Route Analysis, Test Coverage)
**Date:** October 4, 2026
**Current Commit:** cd881db (NVIDIA NIM working)

---

## ✅ STATUS UPDATE — Verified Re-Audit (2026-10-04, later)

Each P0/P1 claim was independently re-verified against the code. Results:

| # | Item | Claimed | **Verified Reality** | Action |
|---|------|---------|----------------------|--------|
| 1 | Authorization gaps (19 controllers) | CRITICAL | Real — 10 controllers patched (`bdc85f7`); rest already had middleware | ✅ FIXED |
| 2 | CSRF missing on 7 forms | HIGH | **FALSE POSITIVE** — all 7 have `@csrf` (5 are GET filters; 2 use `X-CSRF-TOKEN` header on XHR) | ✅ No action |
| 3 | Rate limiting gaps | HIGH | Real — applied `throttle` to all authenticated API groups + platform/webhook/admin routes | ✅ FIXED |
| 4 | HealthCheck/Metrics exposed | HIGH | Already resolved (public `/api/health` by design; web metrics behind `auth`) | ✅ No action |
| 5 | PHPStan L8 (4,803 errors) | P0 | Real — baseline in place | ⏳ PENDING |
| 6 | File upload MIME validation | P1 | **ALREADY DONE** — `MediaUploadService` uses `finfo` + spoof detection; downloads force attachment | ✅ No action |
| — | **Duplicate route shadowing** | *not in original audit* | **NEW CRITICAL** — `api/v1/client-portal/*` had an unauthenticated duplicate group shadowing the authenticated one → public data exposure | ✅ FIXED |

**Key lesson:** the original audit contained false positives; every finding must be verified against code before "fixing".

### New regression guards added
- `tests/Feature/Api/RouteIntegrityTest.php` — asserts (a) zero duplicate method+URI routes, (b) client-portal API requires auth, (c) every authenticated `api/*` route is throttled. This prevents the shadowing class of bug from returning.

---

## 📊 EXECUTIVE SUMMARY

| Area | Current Status | Risk Level | Priority |
|------|---------------|------------|----------|
| **Test Coverage** | 2,600+ tests passing | ✅ LOW | — |
| **Security (Audit)** | 25 issues (4 Critical, 7 High) | 🔴 HIGH | **P0** |
| **Static Analysis (PHPStan L8)** | 4,803 errors | 🟠 MEDIUM | **P0** |
| **Rate Limiting** | Gaps on 20+ routes | 🟠 HIGH | **P0** |
| **Authorization** | 19 controllers missing checks | 🔴 CRITICAL | **P0** |
| **CSRF Protection** | 7 forms missing @csrf | 🟠 HIGH | **P0** |
| **CSP Headers** | unsafe-inline/eval allowed | 🟡 MEDIUM | P1 |
| **File Upload Validation** | Service lacks MIME check | 🟡 MEDIUM | P1 |
| **Performance** | Not load-tested | 🟡 MEDIUM | P1 |
| **Infrastructure** | Docker, CI/CD ready | ✅ LOW | — |

**Overall:** Ready for deployment with **P0 remediation sprint (3-5 days)** before launch.

---

## 🚨 P0 - CRITICAL (Fix Before Launch - 3-5 days)

### 1. Authorization Gaps (19 Controllers Missing Checks)
**Source:** Security Audit Finding 4.1

**Affected Controllers:**
- `AI/AICreditController` — No authz on purchase flow
- `AiProviderController` — Stores API keys, no ownership check
- `ClientPortal2Controller` / `ClientPortalController` — All query scoping but no `$this->authorize()`
- `Chat2Controller` / `ChatController` — Manual agency_id check, no policy
- `MetricsController` / `DashboardInsightsController` — No authz
- `SocialListeningController` / `QuotaController` — No authz
- `HealthCheckController` — Info disclosure risk
- `DocsController` — Internal docs exposed
- `Api/ApiChatController` / `Api/OnboardingController` — No middleware in constructor

**Fix:**
```php
// Add to each controller constructor
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('agency');
    // Or use authorizeAgencyResource() for resource controllers
}

// For API controllers using Sanctum
public function __construct()
{
    $this->middleware(['auth:sanctum', 'agency']);
}
```

**Verification:** Add test asserting 403/401 for unauthenticated access.

---

### 2. CSRF Missing on 7 Forms
**Source:** Security Audit Finding 3.1

**Affected Views:**
- `resources/views/ab-testing/index.blade.php`
- `resources/views/activity/index.blade.php`
- `resources/views/ai-providers/index.blade.php`
- `resources/views/client-portal/campaigns.blade.php`
- `resources/views/client-portal/invoices.blade.php`
- `resources/views/gdpr/admin/audit-log.blade.php`
- `resources/views/search/index.blade.php`

**Fix:** Add `@csrf` to each form:
```blade
<form method="POST" action="{{ route('...') }}">
    @csrf
    <!-- form fields -->
</form>
```

---

### 3. Rate Limiting Gaps (20+ Routes Unprotected)
**Source:** Security Audit Finding 10.2

**Missing Rate Limits:**

| Route Category | Routes | Recommended Limit |
|---------------|--------|-------------------|
| OAuth redirect/callback | 2 | `throttle:10,1` |
| Platform toggle/refresh (FB, IG, LI, TT, PT, YT, TG) | 12 | `throttle:30,1` |
| Zapier actions | 3 | `throttle:10,1` |
| Onboarding complete/auto-detect | 2 | `throttle:60,1` |
| Chat channels/messages | 8 | `throttle:30,1` |
| Admin failed job retry | 1 | `throttle:10,1` |
| Quota check API | 1 | `throttle:60,1` |

**Fix Pattern (in routes/web.php or routes/api.php):**
```php
Route::post('/oauth/redirect', [OAuthController::class, 'redirect'])
    ->middleware('throttle:10,1');

Route::post('/instagram/toggle', [InstagramController::class, 'toggle'])
    ->middleware('throttle:30,1');
```

---

### 4. HealthCheckController & MetricsController Exposed
**Source:** Security Audit Finding 4.1

**Risk:** Information disclosure (version, DB status, queue status, metrics)

**Fix:**
```php
// HealthCheckController
public function __construct()
{
    $this->middleware('auth:sanctum');
    $this->middleware('role:owner|admin');
}

// MetricsController
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('role:owner|admin');
}
```

---

### 5. PHPStan Level 8 Compliance (4,803 Errors)
**Source:** Static Analysis

**Error Categories by Priority:**

| Priority | Error Type | Count | Fix Approach |
|----------|------------|-------|--------------|
| 1 | `missingType.return` | ~500+ | Add return types + PHPDoc array shapes |
| 2 | `property.nonObject` | ~800+ | Null-safe access (`$user?->id ?? 0`) |
| 3 | `argument.type` | ~600+ | Fix type hints, add generics |
| 4 | `missingType.iterableValue` | ~400+ | Add `@var` array shapes |
| 5 | `property.notFound` | ~300+ | Add relationship return types |
| 6 | `method.notFound` | ~200+ | Add scope `@method` tags |

**Incremental Strategy:**
```bash
# 1. Run with baseline
php -d memory_limit=512M vendor/bin/phpstan analyse

# 2. Fix highest-impact first (missingType.return, property.nonObject)
# 3. Regenerate baseline after significant progress
php -d memory_limit=512M vendor/bin/phpstan analyse --generate-baseline=phpstan-baseline.neon
```

**Target:** Reduce to < 500 errors (manageable baseline) before launch.

---

## 🟠 P1 - HIGH (Fix Within 1 Week Post-Launch)

### 6. CSP Header Hardening
**Source:** Security Audit Finding 2.1, 2.2

**Current:** `SecurityHeaders.php` allows `'unsafe-inline'` and `'unsafe-eval'`

**Fix:**
```php
// app/Http/Middleware/SecurityHeaders.php
$cspNonce = base64_encode(random_bytes(16));

$response->headers->set('Content-Security-Policy', 
    "default-src 'self'; " .
    "script-src 'self' 'nonce-{$cspNonce}' https://cdn.jsdelivr.net; " .
    "style-src 'self' 'nonce-{$cspNonce}' https://fonts.googleapis.com; " .
    "font-src 'self' https://fonts.gstatic.com; " .
    "img-src 'self' data: https:; " .
    "connect-src 'self' https://api.nvidia.com; " .
    "frame-ancestors 'none'; " .
    "base-uri 'self'; " .
    "form-action 'self';"
);

// Share nonce with all views
View::share('cspNonce', $cspNonce);
```

**Update views:** Replace inline `<script>` with `<script nonce="{{ $cspNonce }}">`

---

### 7. File Upload Validation in MediaUploadService
**Source:** Security Audit Finding 7.1

**Current:** Service doesn't validate MIME — only controller does

**Fix:**
```php
// app/Services/Media/MediaUploadService.php
public function upload(UploadedFile $file, string $directory = 'uploads'): string
{
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 
                     'video/mp4', 'video/quicktime', 'video/x-msvideo',
                     'application/pdf'];
    
    if (! in_array($file->getMimeType(), $allowedMimes)) {
        throw new \InvalidArgumentException('Invalid file type');
    }
    
    // For images, verify actual content
    if (str_starts_with($file->getMimeType(), 'image/')) {
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('Invalid image file');
        }
    }
    
    return $file->store($directory, 'public');
}
```

---

### 8. Production Load Testing
**Source:** Production Readiness Checklist

**Target Metrics:**
- API p95 < 500ms
- Queue latency < 30s
- 100 concurrent users
- 1000 req/min sustained

**Tools:** k6 or Artillery
```yaml
# k6 script example
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '2m', target: 50 },
        { duration: '5m', target: 100 },
        { duration: '2m', target: 200 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<500'],
        http_req_failed: ['rate<0.01'],
    },
};
```

---

## 🟡 P2 - MEDIUM (Fix Within 30 Days)

### 9. Laravel Policies for Key Models
**Source:** Security Audit Finding 4.4

**Models Needing Policies:**
- `Client` — View/update/delete own clients
- `Campaign` — View/update/delete own campaigns
- `Invoice` — View/download own invoices
- `SocialPost` — CRUD own posts
- `Report` — View/generate own reports

**Pattern:**
```php
// app/Policies/ClientPolicy.php
class ClientPolicy
{
    public function view(User $user, Client $client): bool
    {
        return $user->agency_id === $client->agency_id;
    }
    
    public function update(User $user, Client $client): bool
    {
        return $user->agency_id === $client->agency_id && 
               in_array($user->role, ['owner', 'admin']);
    }
    
    public function delete(User $user, Client $client): bool
    {
        return $user->agency_id === $client->agency_id && 
               $user->role === 'owner';
    }
}
```

**Register in `AuthServiceProvider`:**
```php
protected $policies = [
    Client::class => ClientPolicy::class,
    Campaign::class => CampaignPolicy::class,
    // ...
];
```

---

### 10. Standardize Agency Scoping
**Source:** Security Audit Finding 4.3

**Current:** Mix of `forAgency()`, `forCurrentAgency()`, manual `where('agency_id', ...)`

**Fix:** Use `forAgency()` scope consistently across all models with `HasAgency` trait.

---

### 11. API Documentation (OpenAPI)
**Source:** Post-Launch Sprint

**Add:** Scribe or OpenAPI annotations for all API endpoints.

---

## 🔵 P3 - LOW (Best Practice Improvements)

### 12. Security Headers Test Suite
- Automated tests for CSP, HSTS, X-Frame-Options, etc.

### 13. CI Check for `env()` Outside Config
```yaml
# .github/workflows/security.yml
- name: Check env() usage
  run: |
    if grep -r "env(" --include="*.php" app/ routes/ database/; then
      echo "ERROR: env() found outside config/"; exit 1; fi
```

### 14. ClamAV Integration for Uploads
- Scan uploaded files in queue job

### 15. Automated Dependency Scanning
- Dependabot + composer audit in CI

---

## 🔧 ONGOING MAINTENANCE SCHEDULE

### Daily
- [ ] Monitor Sentry for new errors
- [ ] Check Horizon queue health
- [ ] Verify backup completion

### Weekly
- [ ] Run `composer audit` (check for vulnerabilities)
- [ ] Review PHPStan baseline (check for new errors)
- [ ] Analyze slow query log
- [ ] Check SSL certificate expiry

### Monthly
- [ ] Rotate API keys (Stripe, social platforms, AI providers)
- [ ] Update dependencies (`composer update`, `npm update`)
- [ ] Run full test suite including Playwright E2E
- [ ] Review and update runbooks
- [ ] Capacity planning (DB size, Redis memory, disk usage)

### Quarterly
- [ ] Full penetration test
- [ ] Disaster recovery drill
- [ ] Security audit review
- [ ] Architecture review for scaling

---

## 📋 PRE-LAUNCH CHECKLIST (Final)

### Code Quality
- [ ] All P0 issues resolved
- [ ] PHPStan baseline updated (< 500 errors)
- [ ] Pint clean
- [ ] All tests passing (including new security tests)

### Security
- [ ] Authorization on all controllers
- [ ] CSRF on all forms
- [ ] Rate limiting on all routes
- [ ] CSP hardened (no unsafe-inline/eval)
- [ ] File upload validation in service layer
- [ ] HealthCheck/Metrics restricted to admin

### Infrastructure
- [ ] Production server provisioned (2+ CPU, 4GB+ RAM)
- [ ] MySQL configured (innodb_buffer_pool_size, max_connections)
- [ ] Redis configured (maxmemory, eviction policy)
- [ ] SSL certificates installed
- [ ] S3 bucket for backups/files
- [ ] Sentry project configured
- [ ] DNS configured

### Configuration
- [ ] `.env` fully populated (no CHANGE_ME values)
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Queue workers configured (Supervisor)
- [ ] Scheduler cron running
- [ ] Log rotation configured

### Verification
- [ ] `./deploy.sh` completes successfully
- [ ] `/up` returns 200
- [ ] `/api/health` returns all green
- [ ] Smoke tests pass (register, connect social, create post, bill)
- [ ] Horizon accessible (IP restricted)
- [ ] Sentry receiving events

---

## 🎯 SUCCESS CRITERIA FOR LAUNCH

| Metric | Target |
|--------|--------|
| Test pass rate | 100% |
| PHPStan errors | < 500 (baseline) |
| Security critical issues | 0 |
| Security high issues | 0 |
| API response time (p95) | < 500ms |
| Error rate | < 0.1% |
| Deployment time | < 10 minutes |
| Rollback time | < 5 minutes |

---

## 📞 ESCALATION CONTACTS

| Issue Type | Contact | SLA |
|------------|---------|-----|
| Production outage | DevOps Team | 15 min |
| Security incident | Security Team | 30 min |
| Data loss | DBA | 1 hour |
| Billing issues | Stripe Support | 4 hours |
| AI provider issues | NVIDIA/Provider Support | 4 hours |

---

**Approval:** This plan must be reviewed and approved by Technical Lead and Security Team before production deployment.