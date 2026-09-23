# Test Coverage & E2E Improvement Plan — Digital Marketing SaaS

## Current State Assessment

| Suite | Files | Methods | Lines |
|-------|-------|---------|-------|
| PHPUnit Feature | 128 | ~1,134 | ~50,000+ |
| PHPUnit Unit | 49 | ~295 | ~8,000+ |
| E2E (Playwright) | 6 specs | ~130+ test cases | ~4,525 |
| Frontend Unit (Vitest) | 6 files | ~412 | ~6,500+ |

**Overall Test Coverage: ~21%** (per audit)

## Critical Gaps (from security audit)

### Untested Controllers (High Priority)
- `AuthController`, `LoginController` — security critical
- `BillingController`, `BillingHealthController` — revenue critical
- `AgencyController` — core multi-tenancy
- `WebhookController` (all 8 platforms) — external integration
- `AiProviderController` — BYOK security
- `ClientPortal2Controller` — client-facing
- `ApprovalController`, `CampaignController` — business logic

### Untested Models (High Priority)
- `Agency`, `AgencySetting`, `User` — core auth
- `Campaign`, `Client` — business core
- `ChatChannel`, `ChatMessage` — communication
- `AiProviderKey` — BYOK security

### Untested Services (High Priority)
- `AiGateway`, `AiProviderManager` — AI core
- `GDPRComplianceService` — compliance
- `CostOptimizationEngine` — cost control
- `AgentOrchestrator`, `AgentMemory` — AI agents

## 4-Sprint Improvement Plan

### Sprint 1: Security-Critical Tests (Week 1-2)

**Goal:** Cover all auth, billing, webhook, and authorization tests.

| Test | File | Tests |
|------|------|-------|
| Auth (login, register, OAuth, 2FA, password reset) | `tests/Feature/Auth/AuthTest.php` | 25 |
| Billing (health, plans, invoices) | `tests/Feature/Billing/BillingTest.php` | 20 |
| Webhook (all 8 platforms) | `tests/Feature/Webhook/WebhookTest.php` | 30 |
| Agency CRUD + branding | `tests/Feature/Agency/AgencyCrudTest.php` | 15 |
| AI Provider BYOK CRUD | `tests/Feature/AI/AiProviderControllerTest.php` | 18 |

### Sprint 2: Business-Critical Tests (Week 3-4)

**Goal:** Cover campaigns, clients, analytics, and client portal.

| Test | File | Tests |
|------|------|-------|
| Campaign CRUD + scheduling | `tests/Feature/Campaign/CampaignTest.php` | 22 |
| Client CRUD + relationships | `tests/Feature/Client/ClientTest.php` | 18 |
| Analytics + cross-platform | `tests/Feature/Analytics/AnalyticsV2Test.php` | 20 |
| Client Portal 2.0 | `tests/Feature/ClientPortal/ClientPortal2Test.php` | 25 |
| AI Cache + Optimization | `tests/Feature/AI/AiCacheServiceTest.php` (extend) | 12 |

### Sprint 3: Service & Model Tests (Week 5-6)

**Goal:** Cover all critical services and models.

| Test | File | Tests |
|------|------|-------|
| AiGateway + SmartRouting | `tests/Feature/AI/AiGatewayTest.php` | 20 |
| AgentOrchestrator + Memory | `tests/Feature/AI/AgentOrchestratorTest.php` | 18 |
| GDPR Compliance | `tests/Feature/GDPR/GDPRServiceTest.php` | 15 |
| CostOptimizationEngine | `tests/Feature/AI/CostOptimizationEngineTest.php` | 14 |
| AgencySetting + AiProviderKey | `tests/Feature/Models/CoreModelsTest.php` | 20 |

### Sprint 4: E2E Expansion (Week 7-8)

**Goal:** Comprehensive browser-based end-to-end coverage.

| Spec | File | Tests |
|------|------|-------|
| AI Provider Settings | `tests/frontend/e2e/ai-providers.spec.js` | 15 |
| AB Testing | `tests/frontend/e2e/ab-testing.spec.js` | 12 |
| Analytics Dashboard | `tests/frontend/e2e/analytics.spec.js` | 18 |
| Client Portal 2.0 | `tests/frontend/e2e/client-portal.spec.js` | 14 |
| Visual Regression | `tests/frontend/e2e/visual-regression.spec.js` | 10 |

---

## Immediate Actions (Today)

1. Create `tests/TestCase.php` with shared helpers
2. Create `tests/Concerns/` with test traits for common patterns
3. Write `AuthTest.php` — highest priority
4. Write `AiProviderControllerTest.php` — new feature needs tests
5. Fix E2E spec structure (ensure `test()` vs `test.describe()` consistency)

---

## Key Test Infrastructure Improvements

1. **Shared TestCase helpers:**
   - `createAgency()` — factory-based agency creation
   - `createAuthenticatedUser($agency)` — authenticated user for actingAs
   - `assertAgencyScoped($model)` — verify agency_id scoping
   - `assertAuthorized($response)` — verify 200 vs 403

2. **Factory improvements:**
   - Add missing factories for new models
   - Add state methods (e.g., `AiProviderKey::factory()->active()->forProvider('openai')`)

3. **Parallel testing:**
   - Configure `phpunit.xml` for parallel runs
   - Use `DatabaseTransactions` instead of `RefreshDatabase` where safe

4. **CI integration:**
   - Add coverage gate (min 80%)
   - Add mutation testing (Infection)
   - Add visual regression tests

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Test Coverage | >85% |
| PHPUnit Tests | >2,000 |
| E2E Test Cases | >200 |
| Frontend Unit Tests | >500 |
| Test Execution Time | <5 minutes |
| Mutation Score | >70% |

---

*Generated: 2026-09-23*
