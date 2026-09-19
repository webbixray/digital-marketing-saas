# Database Schema, Models, Jobs, Events, Caching & Background Processing Audit

**Project:** Digital Marketing SaaS (Laravel 11, MySQL, Redis)
**Date:** September 19, 2026
**Scope:** 42 migrations, 57 models, 15 queue jobs, 9 events, 12 listeners, 6 seeders

---

## 1. MISSING FOREIGN KEYS & INDEXES

### 🔴 Critical

| Table:Column | Issue | Migration | Recommendation |
|---|---|---|---|
| `campaign_post.social_post_id` | Nullable, no FK constraint | `2026_09_04_153100_create_core_business_tables.php:193` | Add `->constrained('social_posts')->nullOnDelete()` or remove nullable |
| `invoice_items.invoice_id` | Nullable, no FK constraint | `2026_09_04_153200_create_automation_and_business_tables.php:224` | Add `->constrained('invoices')->nullOnDelete()` |
| `ai_credit_purchases` | Missing index on `agency_id`, `created_at`, `status` | `2026_09_17_131542_add_ai_credits.php:20` | Add `$table->index(['agency_id', 'created_at'])` and `$table->index('status')` |
| `notifications` | Missing index on `notifiable_type, notifiable_id` | `2026_09_07_110000_create_team_collaboration_tables.php:37` | Add `$table->index(['notifiable_type', 'notifiable_id'])` for polymorphic lookups |
| `notifications` | Missing index on `read_at` | Same as above | Add `$table->index('read_at')` for filtering unread notifications |

### 🟡 Medium

| Table | Missing Index | Reason |
|---|---|---|
| `social_post_scheduled_logs` | `social_post_id, status` | Frequently queried for post status checks |
| `custom_templates` | `agency_id` | Filtered by agency in every query |
| `custom_templates` | `agency_id, type` | Common filter pattern |
| `ab_tests` | `agency_id, platform, status` | Dashboard filtering |
| `support_tickets` | `assigned_to` | Filter by assignee |
| `support_ticket_replies` | `ticket_id, created_at` | Listing replies chronologically |
| `media_assets` | `agency_id, user_id` | Filter by uploader |
| `workflow_versions` | `workflow_id, created_at` | Listing versions |
| `agent_cost_logs` | `agency_id, task_type, created_at` | Cost reporting by type |
| `agent_performance_logs` | `agent_category, recorded_at` | Category performance reports |

### 🟢 Low (JSON Column Indexes)

| Table:Column | Recommendation |
|---|---|
| `agencies.custom_settings` | Add generated column + index for frequently queried keys |
| `social_posts.metrics` | Add generated column + index for metric keys |
| `campaigns.tags` | Consider separate pivot table for tag filtering |

---

## 2. MISSING MODELS

| Table | Status | Impact |
|---|---|---|
| `custom_templates` | ❌ No Model | Cannot use Eloquent, must use DB::table() |
| `notifications` | ❌ No Model | Cannot use Eloquent, must use DB::table() |
| `social_platform_cache` | ❌ No Model | Table exists but never used in code |

**Recommendation:** Create `CustomTemplate` and `Notification` models. Remove or implement `social_platform_cache`.

---

## 3. MISSING SEEDERS

### 🔴 Critical

| Seeder | Impact | Recommendation |
|---|---|---|
| `PlatformSeeder` | `platforms` table is empty after fresh migration. `Platform::getAllActive()` returns nothing, breaking social account connection flows. | Create dedicated seeder with Facebook, Instagram, Twitter, LinkedIn, TikTok, Pinterest |

### 🟡 Medium

| Seeder | Impact | Recommendation |
|---|---|---|
| `RolePermissionSeeder` | Roles only created in `DemoSeeder`, not in production seed. Spatie permissions will have no roles. | Create dedicated seeder with owner, admin, manager, member roles + permissions |
| `EmailTemplateSeeder` | No default email templates. Users start from scratch. | Create welcome, password reset, invoice templates |
| `EmailCampaignSeeder` | No sample campaigns for onboarding | Create 2-3 sample campaigns |

---

## 4. MISSING QUEUE JOBS

### 🔴 Critical (ShouldQueue but dispatched synchronously)

| Job | Current State | Issue |
|---|---|---|
| `AI/GenerateContent` | `implements ShouldQueue` | Dispatched synchronously via `AgentOrchestrator::dispatch()` — blocks HTTP request until AI responds |
| `Social/PublishPost` | `implements ShouldQueue` | Likely dispatched sync in controllers |
| `Social/ProcessScheduledPost` | `implements ShouldQueue` | Scheduled command dispatches but QUEUE_CONNECTION=sync |

**Root Cause:** `.env` has `QUEUE_CONNECTION=sync` — ALL jobs run synchronously.

**Recommendation:** Change to `QUEUE_CONNECTION=redis` and run `php artisan queue:work`.

### 🟡 Missing Jobs

| Job Needed | Trigger | Priority |
|---|---|---|
| `SendWebhookJob` | Webhook event fired | High — webhooks sent synchronously in `WebhookController` |
| `ProcessInboxTriageJob` | New inbox message | Medium — AI triage runs sync |
| `FetchMetricsJob` | Scheduled (hourly) | Medium — platform metrics fetch |
| `SendEmailBatchJob` | Email campaign send | High — already exists but ensure async |
| `GenerateReportJob` | Report requested | High — already exists |
| `CleanupOldLogsJob` | Scheduled (daily) | Low — prune old activity logs |
| `ProcessReferralCreditsJob` | User signup/payment | Medium |

---

## 5. MISSING EVENTS / LISTENERS

### 🔴 Orphaned Event (No Listeners)

| Event | File | Status |
|---|---|---|
| `AiGenerationCompleted` | `app/Events/AiGenerationCompleted.php` | ❌ Defined but NO listeners registered in `EventServiceProvider` |

**Impact:** Event is never dispatched or listened to. AI completion tracking is broken.

### 🟡 Missing Events

| Event | Needed For | Recommendation |
|---|---|---|
| `UserCreated` | Audit logging, welcome email, referral tracking | Create event + listener |
| `SupportTicketCreated` | Notification to admins, auto-assignment | Create event + listener |
| `AbTestCompleted` | Notification, auto-apply winner | Create event + listener |
| `InboxMessageReceived` | Auto-triage, sentiment analysis | Create event + listener |
| `MediaAssetUploaded` | Thumbnail generation, virus scan | Create event + listener |
| `ReportGenerated` | Email notification, download link | Create event + listener |

### 🟡 Missing Listener Methods

| Event | Missing Listener | Recommendation |
|---|---|---|
| `PostPublished` | Update campaign metrics | Add listener to recalculate campaign stats |
| `PostFailed` | Increment agency failure count | Add listener for failure tracking |
| `InvoicePaid` | Send receipt email, activate features | Add listener |

---

## 6. CACHING STRATEGY GAPS

### 🔴 Critical (Frequently Queried, Never Cached)

| Query | Location | Impact | Recommendation |
|---|---|---|---|
| `Platform::getAllActive()` | `app/Models/Platform.php:33` | Called on every social account page load | `Cache::remember('platforms:active', 3600, fn() => ...)` |
| `WorkflowTemplate::active()->public()` | `app/Http/Controllers/WorkflowController.php:38` | Called on workflow index | `Cache::remember('workflow_templates:public', 3600, ...)` |
| `Feature::all()` | `PlanSeeder`, various | Called on plan management | `Cache::remember('features:all', 3600, ...)` |
| `Plan::all()` | Various | Called on billing/pricing pages | `Cache::remember('plans:active', 3600, ...)` |

### 🟡 Missing Cache Invalidation

| Cache Key | When to Invalidate | Current State |
|---|---|---|
| `platforms:active` | Platform created/updated/deleted | ❌ Never invalidated |
| `workflow_templates:public` | Template created/updated/deleted | ❌ Never invalidated |
| `plans:active` | Plan created/updated/deleted | ❌ Never invalidated |
| `features:all` | Feature created/updated/deleted | ❌ Never invalidated |
| `analytics:{agency_id}:*` | Post published, metrics updated | ✅ Properly invalidated |

### 🟢 Cache Tag Opportunities

| Tag | Keys to Group | Benefit |
|---|---|---|
| `agency:{id}` | All agency-specific caches | Flush entire agency cache on settings change |
| `platforms` | Platform-related caches | Flush when platforms table changes |
| `plans` | Plan-related caches | Flush when plans change |

**Current State:** Only `ClearPostCache` and `SocialAccountObserver` use cache tags. All other caches use manual `Cache::forget()`.

### 🟡 Unused Cache Table

| Table | Status | Recommendation |
|---|---|---|
| `social_platform_cache` | Created in migration, never used in code | Either implement or remove migration |

---

## 7. QUEUE CONFIGURATION ISSUES

### 🔴 Critical

| Issue | Location | Impact | Fix |
|---|---|---|---|
| `QUEUE_CONNECTION=sync` | `.env:39` | ALL jobs run synchronously, blocking HTTP requests | Change to `redis` and run queue worker |
| No queue worker process | Production | Jobs never processed asynchronously | Supervisor config for `php artisan queue:work` |

### 🟡 Missing Queue Configuration

| Setting | Current | Recommended |
|---|---|---|
| Queue connection | `sync` | `redis` |
| Queue worker | Not configured | Supervisor with 4-8 workers |
| Failed job logging | `database-uuids` | ✅ OK |
| Retry after | 90s default | 300s for long-running AI jobs |

---

## 8. AUDIT LOGGING GAPS

### 🔴 Models Missing AuditObserver

| Model | Has Observer | Status |
|---|---|---|
| `User` | `UserObserver` only | ❌ No AuditObserver |
| `SocialAccount` | `SocialAccountObserver` only | ❌ No AuditObserver |
| `AiContentLog` | `AiContentLogObserver` only | ❌ No AuditObserver |
| `AbTest` | None | ❌ No observer at all |
| `SupportTicket` | None | ❌ No observer at all |
| `MediaAsset` | None | ❌ No observer at all |
| `Report` | None | ❌ No observer at all |
| `ScheduledReport` | None | ❌ No observer at all |
| `ClientReport` | None | ❌ No observer at all |
| `InvoiceItem` | None | ❌ No observer at all |
| `EmailCampaignRecipient` | None | ❌ No observer at all |

**Current AuditObserver coverage:** Only 6 of 16 business models (Campaign, Client, Invoice, SocialPost, Workflow, EmailCampaign).

### 🟡 AuditObserver Limitations

| Limitation | Impact | Recommendation |
|---|---|---|
| Uses `Auth::user()` for user ID | Console/queue jobs have no user | Add `user_id` parameter to event |
| Stores `changes` as JSON string | Hard to query | Use JSON column type |
| No IP address for queue jobs | Missing context | Pass IP in event payload |

---

## 9. RATE LIMITING GAPS

### ✅ Well-Protected

| Route | Limit |
|---|---|
| `api/v1/*` (general) | 60/min |
| `api/v1/ai/generate` | 10/min |
| `api/v1/agents/*/dispatch` | 5/min |
| `api/v1/reports/export` | 10/min |
| `web/login` | 10/min |
| `web/register` | 10/min |

### 🟡 Missing Rate Limiting

| Route | Risk | Recommendation |
|---|---|---|
| `api/v1/inbox/*` | Inbox queries can be expensive | Add `throttle:30,1` |
| `api/v1/activity-feed/*` | Activity queries | Add `throttle:30,1` |
| `api/v1/analytics/*` | Analytics queries are heavy | Add `throttle:20,1` |
| `api/v1/webhooks/incoming` | Webhook ingestion | Add `throttle:100,1` |
| `api/v1/media/upload` | File uploads | Add `throttle:10,1` |

---

## 10. DATABASE SCHEMA ISSUES

### 🔴 Nullable Foreign Keys Without Constraints

| Table:Column | Referenced Table | Issue |
|---|---|---|
| `campaign_post.social_post_id` | `social_posts` | Nullable, no `->constrained()` |
| `invoice_items.invoice_id` | `invoices` | Nullable, no `->constrained()` |

### 🟡 Missing Soft Deletes

| Table | Has Soft Deletes | Recommendation |
|---|---|---|
| `platforms` | ❌ | Add if platforms can be deprecated |
| `plan_features` | ❌ | Add for audit trail |
| `agency_features` | ❌ | Add for audit trail |
| `workflow_executions` | ❌ | Add for history |
| `workflow_logs` | ❌ | Add for history |
| `agent_performance_logs` | ❌ | Add for history |
| `agent_cost_logs` | ❌ | Add for history |
| `ab_test_logs` | ❌ | Add for history |

### 🟢 Missing Timestamps

| Table | Has Timestamps | Recommendation |
|---|---|---|
| `campaign_post` | ❌ | Add `timestamps()` for tracking |
| `plan_features` | ✅ | OK |
| `agency_features` | ✅ | OK |

---

## PRIORITIZED ACTION ITEMS

### 🔴 P0 — Critical (Fix Immediately)

1. **Change `QUEUE_CONNECTION=sync` to `redis`** in `.env` — all async jobs are broken
2. **Create `PlatformSeeder`** — platforms table is empty, breaking social account flows
3. **Add FK constraints** to `campaign_post.social_post_id` and `invoice_items.invoice_id`
4. **Add missing indexes** to `ai_credit_purchases` (agency_id, created_at, status)
5. **Cache `Platform::getAllActive()`** — called on every page load, never cached

### 🟡 P1 — High (Fix This Sprint)

6. **Register `AiGenerationCompleted` listeners** or remove unused event
7. **Add `AuditObserver`** to User, SocialAccount, AbTest, SupportTicket, MediaAsset
8. **Create `RolePermissionSeeder`** for production role setup
9. **Add indexes** to `notifications` (notifiable_type, notifiable_id, read_at)
10. **Cache `WorkflowTemplate::active()->public()`** and `Plan::all()` queries
11. **Add rate limiting** to inbox, analytics, and webhook endpoints
12. **Create `CustomTemplate` model** — table exists but no Eloquent model

### 🟢 P2 — Medium (Fix Next Sprint)

13. **Create missing events:** UserCreated, SupportTicketCompleted, AbTestCompleted
14. **Add `SendWebhookJob`** — webhooks sent synchronously
15. **Add `ProcessInboxTriageJob`** — AI triage runs synchronously
16. **Add cache tags** for plans, features, platforms
17. **Add missing indexes** to support_tickets (assigned_to), support_ticket_replies (ticket_id, created_at)
18. **Create `Notification` model** — table exists but no Eloquent model
19. **Remove or implement `social_platform_cache`** table

### 🔵 P3 — Low (Backlog)

20. **Add JSON column indexes** for frequently queried JSON keys
21. **Add soft deletes** to workflow_executions, agent_performance_logs
22. **Add `timestamps()`** to `campaign_post` pivot table
23. **Create `EmailTemplateSeeder`** with default templates
24. **Add `CleanupOldLogsJob`** for pruning old agent logs
25. **Add `ProcessReferralCreditsJob`** for async referral processing

---

## SUMMARY STATISTICS

| Category | Total | Issues Found | Critical |
|---|---|---|---|
| Migrations | 42 | 5 | 2 |
| Models | 57 | 3 missing | 1 |
| Queue Jobs | 15 | 5 missing | 2 |
| Events | 9 | 6 missing | 1 |
| Listeners | 12 | 4 missing | 1 |
| Seeders | 6 | 3 missing | 1 |
| Cache Points | 20+ | 8 gaps | 2 |
| Rate Limits | 15 routes | 5 gaps | 0 |
| Audit Coverage | 16 models | 10 missing | 0 |

**Total Issues Found:** 49
**Critical (P0):** 5
**High (P1):** 7
**Medium (P2):** 7
**Low (P3):** 5
