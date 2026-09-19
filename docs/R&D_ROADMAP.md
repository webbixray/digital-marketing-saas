# R&D Audit Report & Roadmap

**Project:** Digital Marketing SaaS
**Date:** September 20, 2026
**Scope:** Full codebase audit (952 tests, 90+ controllers, 57 models, 42 migrations, 15 jobs)

---

## Executive Summary

| Category | Finding |
|----------|---------|
| **Critical Issues** | 8 (will fail at runtime) |
| **High Issues** | 13 (missing error handling, missing seeders) |
| **Medium Issues** | 18 (missing API routes, missing events) |
| **Low Issues** | 10 (missing tests, optimization) |
| **Features Complete** | 18/24 (75%) |
| **Features Partial** | 3/24 (12%) |
| **Features Missing** | 3/24 (12%) |

---

## Phase 1: Critical Fixes (Week 1)

### 1.1 Fix Broken Routes/HIGH

| Issue | File | Fix |
|-------|------|-----|
| Missing `apiPublish` method | `app/Http/Controllers/InstagramController.php` | Implement Instagram Business API publish |
| Missing `apiInsights` method | `app/Http/Controllers/InstagramController.php` | Implement Instagram Business API insights |
| Validation bug `max-7` | `app/Http/Controllers/LandingPageController.php:110-112` | Change to `max:7` |

### 1.2 Fix Queue Connection/CRITICAL

| Issue | File | Fix |
|-------|------|-----|
| `QUEUE_CONNECTION=sync` | `.env:39` | Change to `QUEUE_CONNECTION=redis` |
| Missing `PlatformSeeder` | `database/seeders/` | Create seeder for platforms table |

### 1.3 Add Missing Database Indexes/HIGH

| Table | Missing Index | Migration |
|-------|---------------|-----------|
| `ai_credit_purchases` | `agency_id`, `created_at`, `status` | New migration |
| `notifications` | `notifiable_type, notifiable_id`, `read_at` | New migration |
| `social_post_scheduled_logs` | `social_post_id, status` | New migration |
| `custom_templates` | `agency_id`, `agency_id, type` | New migration |
| `support_tickets` | `assigned_to` | New migration |
| `ab_tests` | `agency_id, platform, status` | New migration |

### 1.4 Fix Foreign Key Constraints/MEDIUM

| Table:Column | Issue | Fix |
|--------------|-------|-----|
| `campaign_post.social_post_id` | Nullable, no FK | Add `->constrained()->nullOnDelete()` |
| `invoice_items.invoice_id` | Nullable, no FK | Add `->constrained()->nullOnDelete()` |

---

## Phase 2: API Error Handling (Week 1-2)

### 2.1 Add Try-Catch to API Controllers

| Controller | Methods | Risk |
|------------|---------|------|
| `ApiAnalyticsController` | All 5 methods | Returns 500 stack traces |
| `ApiDashboardController` | All 3 methods | Returns raw errors |
| `ApiSocialAccountController` | store, update, destroy | Leaks error details |
| `ApiSocialPostController` | index, show, update, destroy | Unformatted exceptions |
| `ApiReportController` | All 5 methods | Exposes internal errors |
| `ClientReportController` | All methods | No auth/agency middleware |

### 2.2 Add Missing API Resource Controllers

| Model | API Status | Priority |
|-------|------------|----------|
| ActivityFeed | Web-only | Medium |
| ActivityLog | Web-only | Medium |
| MediaAsset | Web-only | Medium |
| ContentTemplate | Web-only | Medium |
| CustomField | Web-only | Medium |
| EmailCampaign | Web-only | Medium |
| EmailTemplate | Web-only | Medium |
| LandingPage | Web-only | Medium |
| SupportTicket | Web-only | Medium |
| InboxMessage | Web-only | Medium |
| WhiteLabelSetting | Web-only | Medium |
| AbTest | Partial | Low |

---

## Phase 3: Background Processing (Week 2)

### 3.1 Fix Queue Jobs Running Synchronously

| Job | Current Issue | Fix |
|-----|---------------|-----|
| `AI/GenerateContent` | Blocks HTTP request | Change to `QUEUE_CONNECTION=redis` |
| `Social/PublishPost` | Dispatched sync | Dispatch to queue |
| `Social/ProcessScheduledPost` | Scheduled but sync | Dispatch to queue |
| `SendEmailCampaign` | Dispatched sync | Dispatch to queue |

### 3.2 Add Missing Queue Jobs

| Job | Trigger | Priority |
|-----|---------|----------|
| `SendWebhookJob` | Webhook event | High |
| `ProcessInboxTriageJob` | New inbox message | Medium |
| `FetchMetricsJob` | Scheduled (hourly) | Medium |
| `GenerateReportJob` | Report generation | Medium |
| `SendNotificationJob` | Notification event | Medium |

### 3.3 Fix Missing Events/Listeners

| Event | Status | Fix |
|-------|--------|-----|
| `AiGenerationCompleted` | No listeners | Register listener |
| `UserCreated` | Missing | Create event + listener |
| `SupportTicketCreated` | Missing | Create event + listener |
| `AbTestCompleted` | Missing | Create event + listener |
| `InboxMessageReceived` | Missing | Create event + listener |
| `MediaAssetUploaded` | Missing | Create event + listener |
| `ReportGenerated` | Missing | Create event + listener |

---

## Phase 4: Feature Completion (Week 2-3)

### 4.1 Analytics Platform API Sync

| Platform | Current | Needed |
|----------|---------|--------|
| Facebook | Local DB only | Real API calls for insights |
| Instagram | Local DB only | Real API calls for media insights |
| Twitter/X | Local DB only | Real API calls for tweet metrics |
| LinkedIn | Local DB only | Real API calls for share stats |
| TikTok | Local DB only | Real API calls for video stats |
| YouTube | Local DB only | Real API calls for analytics |
| Pinterest | Local DB only | Real API calls for pin stats |

### 4.2 Social Media Comment/Reply

| Feature | Status | Needed |
|---------|--------|--------|
| Fetch comments | Partial (`SocialListeningService`) | Full implementation |
| Reply to comments | Missing | Create reply service |
| Comment moderation | Missing | Add moderation queue |
| Sentiment analysis | Missing | AI integration |

### 4.3 Workflow AI Integration

| Feature | Current | Needed |
|---------|---------|--------|
| `actionAiGenerate()` | Returns prompt only | Call `AiContentService` |
| AI-generated content | Missing | Store and use AI output |
| Conditional AI | Missing | AI-based workflow conditions |

### 4.4 YouTube Publishing via API

| Feature | Status | Needed |
|---------|--------|--------|
| Video upload | Exists but not integrated | Wire into `SocialApiService.publish()` |
| Video scheduling | Missing | Add to scheduler |
| Thumbnail upload | Missing | Add thumbnail support |

---

## Phase 5: Database & Model Gaps (Week 3)

### 5.1 Create Missing Models

| Table | Status | Impact |
|-------|--------|--------|
| `custom_templates` | No Model | Use Eloquent instead of DB::table() |
| `notifications` | No Model | Use Eloquent instead of DB::table() |
| `social_platform_cache` | No Model | Remove or implement |

### 5.2 Create Missing Seeders

| Seeder | Impact | Priority |
|--------|--------|----------|
| `PlatformSeeder` | Platforms table empty, breaks social connections | Critical |
| `RolePermissionSeeder` | No roles in production | High |
| `EmailTemplateSeeder` | No default templates | Medium |
| `EmailCampaignSeeder` | No sample campaigns | Low |

### 5.3 Add AuditObserver to Missing Models

| Model | Has Observer | Needed |
|-------|--------------|--------|
| `User` | UserObserver only | Add AuditObserver |
| `SocialAccount` | SocialAccountObserver only | Add AuditObserver |
| `AbTest` | None | Add observers |
| `SupportTicket` | None | Add observers |
| `MediaAsset` | None | Add observers |
| `Report` | None | Add observers |
| `ClientReport` | None | Add observers |
| `InvoiceItem` | None | Add observers |
| `EmailCampaignRecipient` | None | Add observers |

---

## Phase 6: Security & Performance (Week 3-4)

### 6.1 Add Missing Rate Limiting

| Route | Risk | Recommendation |
|-------|------|----------------|
| `api/v1/inbox/*` | Expensive queries | `throttle:30,1` |
| `api/v1/activity-feed/*` | Activity queries | `throttle:30,1` |
| `api/v1/analytics/*` | Heavy queries | `throttle:20,1` |
| `api/v1/webhooks/incoming` | Ingestion | `throttle:100,1` |

### 6.2 Add Missing Tests

| Test Area | Coverage | Needed |
|-----------|----------|--------|
| Instagram apiPublish/apiInsights | None | Full test suite |
| ApiAnalyticsController edge cases | Basic | Error handling tests |
| LandingPageController validation | None | Validation bug test |
| ClientReportController auth | None | Auth middleware test |
| ApiDashboardController cache | None | Cache failure tests |

### 6.3 Add Caching Opportunities

| Data | Current | Needed |
|------|---------|--------|
| `Platform::getAllActive()` | Every request | Cache with tags |
| Workflow templates | Every request | Cache with tags |
| Plans/features | Every request | Cache with tags |
| Agency settings | Every request | Cache with tags |

---

## Phase 7: Developer Experience (Week 4)

### 7.1 Missing API Documentation

| API Section | Status |
|-------------|--------|
| Authentication | Documented |
| Social Posts | Documented |
| Analytics | Partial |
| AI Content | Documented |
| Workflows | Missing |
| Email Campaigns | Missing |
| White Label | Missing |
| Client Reports | Missing |

### 7.2 Missing SDK/Client Libraries

| Language | Status |
|----------|--------|
| PHP/Laravel | Missing |
| JavaScript/TypeScript | Missing |
| Python | Missing |

---

## Implementation Priority

| Phase | Timeline | Effort | Impact |
|-------|----------|--------|--------|
| Phase 1: Critical Fixes | Week 1 | 4 hours | Fixes runtime failures |
| Phase 2: API Error Handling | Week 1-2 | 6 hours | Prevents error leaks |
| Phase 3: Background Processing | Week 2 | 8 hours | Unblocks HTTP requests |
| Phase 4: Feature Completion | Week 2-3 | 16 hours | Completes core features |
| Phase 5: Database & Models | Week 3 | 6 hours | Data integrity |
| Phase 6: Security & Performance | Week 3-4 | 8 hours | Production hardening |
| Phase 7: Developer Experience | Week 4 | 4 hours | API ecosystem |

**Total estimated effort:** ~52 hours (1 week full-time, or 2 weeks part-time)

---

## Next Steps

1. **Approve Phase 1** — Fix critical runtime issues
2. **Schedule Phase 2-3** — API hardening and queue processing
3. **Plan Phase 4** — Feature completion sprint
4. **Execute Phase 5-7** — Polish and optimization

---

**Report generated by:** Hermes Dev Studio
**Audit subagents:** 3 parallel analyzers
**Files analyzed:** 90+ controllers, 57 models, 42 migrations, 15 jobs, 14 notifications, 9 AI providers
