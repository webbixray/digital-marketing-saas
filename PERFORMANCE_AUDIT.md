# Performance Audit Report
## Digital Marketing SaaS — Laravel 13

---

## 1. N+1 Query Patterns in Controllers & Views

### 1.1 ✅ Well-Handled (Eager Loading Present)
| File | Line | Pattern |
|------|------|---------|
| `DashboardController.php` | 113-117 | `ActivityLog::with('user')` |
| `DashboardController.php` | 120-126 | `SocialPost::with('socialAccount')` |
| `CampaignController.php` | 94 | `Campaign::with('client')` |
| `EmailCampaignController.php` | 56 | `$campaign->load('recipients', 'agency')` |
| `ActivityLogController.php` | 33 | `with('user')` |
| `InboxController.php` | 33 | `with('socialAccount')` |
| `CommentController.php` | 27 | `with('user')` |

### 1.2 ⚠️ N+1 in Views (Relationship Access Without Eager Loading)
| File | Line | Issue |
|------|------|-------|
| `billing/invoices.blade.php` | 44 | `$inv->client->name` — no `client` eager loaded in `BillingController` |
| `invoices/show.blade.php` | 14 | `$invoice->items` — no `items` eager loaded in invoice show controller |
| `onboarding/step2_social.blade.php` | 28 | `$connectedAccounts[$key]` — loaded via `get()` then keyBy, acceptable |
| `roles/index.blade.php` | 45 | `$role->permissions->count()` — loaded via `with('permissions')` (OK) |

### 1.3 ⚠️ Missing Eager Loading in Controllers
| File | Line | Issue |
|------|------|-------|
| `BillingController.php` | 27-29 | Invoices returned without `client` relationship |
| `SocialPostController.php` | 61 | `SocialAccount::where(...)->get()` without `agency` scope check in query |
| `WorkflowController.php` | 38 | `WorkflowTemplate::active()->public()->get()` — no eager loading for relations used in view |

---

## 2. Missing Eager Loading (`with`/`load`)

### 2.1 🔴 High Priority
| File | Line | Missing With |
|------|------|-------------|
| `BillingController.php` | 29 | `->with('client')` needed for `billing/invoices.blade.php:44` |
| `Api/ApiAgencyController.php` | 47 | Users loaded without `roles/permissions` relations |
| `Api/ApiClientController.php` | 28 | Clients with subscriptions — view may access more relations |
| `SearchController.php` | 45-95 | All search results loaded without relations used in view |

### 2.2 🟡 Medium Priority
| File | Line | Issue |
|------|------|-------|
| `AnalyticsService.php` | 510-518 | `generateClientReport()` — posts not eager loaded with socialAccount |
| `Api/ApiSocialPostController.php` | 32 | `with('socialAccount')` present — OK |
| `AdminDashboardController.php` | 51-54 | `with('agency')` present — OK |

---

## 3. Unbounded Queries (No Pagination)

### 3.1 🔴 Unbounded Queries Without Pagination
| File | Line | Issue |
|------|------|-------|
| `RoleController.php` | 26-29 | `Role::where(...)->get()` — no pagination (but roles are typically small) |
| `RoleController.php` | 37, 65 | `Permission::all()` — all permissions loaded (cached, acceptable) |
| `OnboardingController.php` | 108 | `$agency->socialAccounts()->get()` — acceptable (one-time onboarding) |
| `OnboardingController.php` | 155 | `$agency->users()->get()` — acceptable (one-time onboarding) |

### 3.2 ✅ Well-Paginated Controllers
- `DashboardController`, `CampaignController`, `ClientController`, `AbTestController`, `ActivityFeedController`, `ActivityLogController`, `AdminDashboardController`, `AgencyController`, `ApprovalController`, `BillingController`, `CommentController`, `ContentLibraryController`, `ContentTemplateController`, `CustomFieldController`, `EmailCampaignController`, `InboxController`, and all `Api/*` controllers — all use `paginate()`.

---

## 4. Missing Cache Usage

### 4.1 ✅ Well-Cached Controllers
| File | Line | Cache |
|------|------|-------|
| `AdminDashboardController.php` | 32, 50 | `Cache::remember` for 300s/60s |
| `AnalyticsController.php` | 32 | `Cache::remember` for 300s |
| `Api/ApiDashboardController.php` | 28-46 | Multiple `Cache::remember` for 300s |
| `DashboardController.php` | 64-68, 104-108 | `Cache::remember` for 300s |
| `InboxController.php` | 35-37 | `Cache::remember` for 60s |
| `AnalyticsService.php` | 29-183 | All stats methods use `Cache::remember` |

### 4.2 🔴 Missing Cache — Repeated Expensive Queries
| File | Line | Issue |
|------|------|-------|
| `DashboardController.php` | 64-70 | `AiContentLog::where(...)->count()` executed TWICE (once for `used`, once for `percentage`) with separate Cache::remember calls — second call may execute before first caches |
| `SearchController.php` | 38-96 | No caching of search results — LIKE queries are expensive |
| `GdprController.php` | 24-26 | No caching of consent/export/deletion data |
| `SocialAccountController.php` | 22 | Social accounts listing not cached |
| `FacebookController.php` | 32 | No caching of Facebook accounts data |
| `AgentController.php` | 72-75 | Agent cost log not cached |

### 4.3 🟡 Redundant Query Execution
| File | Line | Issue |
|------|------|-------|
| `DashboardController.php` | 64-68 | `Cache::remember` called twice for same key — second call with new closure before first caches |
| `Api/ApiDashboardController.php` | 30-44 | `SocialPost::forAgency($agencyId)->count()` called twice (lines 31 and 40) |

---

## 5. Large Collection Operations That Could Be Optimized

### 5.1 🔴 Critical — Collection Operations in Views (PHP-side filtering/sorting)
| File | Line | Issue |
|------|------|-------|
| `billing/invoices.blade.php` | 146 | `$invoices->where('status', 'paid')->count()` — iterates entire paginated collection |
| `billing/invoices.blade.php` | 149 | `$invoices->where('status', 'pending')->count()` — iterates again |
| `billing/invoices.blade.php` | 152 | `$invoices->where('status', 'overdue')->count()` — iterates again |
| `billing/invoices.blade.php` | 155 | `$invoices->sum('total')` — iterates again |
| `media/index.blade.php` | 155 | `$assets->where('file_type', 'image')->count()` |
| `media/index.blade.php` | 164 | `$assets->where('file_type', 'video')->count()` |

> **Impact**: 4+ full iterations over the collection on every page load. Should use SQL aggregation or cache counts.

### 5.2 🟡 Heavy Collection Operations in Services
| File | Line | Issue |
|------|------|-------|
| `AnalyticsService.php` | 521-542 | `generateClientReport()` — `groupBy('platform')->map()->sum()` on potentially large collections |
| `AnalyticsService.php` | 558-578 | Multiple `sortByDesc()`, `map()`, `take()` on collections |
| `AI/Agent/AnalyticsAgent.php` | 444-476 | Multiple `groupBy()->map()->sortByDesc()` |
| `AI/Agent/SocialMediaAgent.php` | 411-430 | Multiple `groupBy()->map()->sortByDesc()` |
| `AI/Agent/ReportAgent.php` | 363-518 | Multiple `groupBy()->map()->sortByDesc()` |
| `AI/AiRecommendationService.php` | 155-156 | `sortByDesc()` and `sortBy()` on collections |
| `AdminDashboardController.php` | 62-65 | `->get()->groupBy('platform')` — could use SQL GROUP BY |

### 5.3 🟡 Inefficient Data Fetching
| File | Line | Issue |
|------|------|-------|
| `AnalyticsService.php` | 339-376 | `getPlatformStats()` — uses 6 cloned queries (`clone $posts`) instead of single SQL aggregation |
| `AnalyticsController.php` | 142-157 | `->get()->map(fn(...))->toArray()` — could use `->get()->toArray()` with selects |

---

## 6. Frontend: Bundle Size & Code Splitting

### 6.1 🔴 No Code Splitting / Dynamic Imports
| File | Issue |
|------|-------|
| `vite.config.js` | 3 entry points (`unified.js`, `components.js`, `unified.css`) — all loaded on every page |
| `resources/js/unified.js` | Alpine.js + plugins bundled together (6.5KB) |
| `resources/js/components.js` | All components registered upfront (10.8KB) |

> **Impact**: Every page loads the entire JS bundle regardless of which components are needed.

### 6.2 🟡 Missing Optimizations
| Issue | Details |
|-------|---------|
| No dynamic `import()` | Heavy components (charts, editors, media library) not lazy-loaded |
| No route-based splitting | All JS loaded on dashboard, settings, onboarding, etc. |
| Alpine.js plugins | `@alpinejs/focus` loaded globally even if only used in modals |
| Font Awesome | Full FA kit loaded (not tree-shaken) |
| Tailwind CSS | `unified.css` (8.9KB) — Tailwind 4 should be purged but verify config |

### 6.3 Recommendations
- Use `import()` for heavy components (charts, rich text editor, media uploader)
- Split by route: `dashboard.js`, `analytics.js`, `settings.js`
- Use Alpine.js `x-intersect` or `x-cloak` for deferred loading
- Consider replacing full Font Awesome with individual icon imports

---

## 7. Database Queries Without Proper Indexes

### 7.1 ✅ Existing Indexes (from migrations)
| Migration | Tables Covered |
|-----------|---------------|
| `2026_09_05_070000_add_performance_indexes.php` | social_posts, email_campaigns, invoices, ai_content_logs, activity_logs, clients, campaigns, users, social_accounts |
| `2026_09_11_000001_add_missing_performance_indexes.php` | social_accounts, media_assets, content_assets, content_templates, landing_pages, forms, email_campaigns, inbox_messages, workflows, invoices, clients, campaigns, social_posts |
| `2026_09_15_000003_add_missing_composite_indexes.php` | social_posts, social_accounts, users |

### 7.2 🔴 Missing Indexes — Query Patterns Without Support
| Table | Query Pattern | Missing Index |
|-------|--------------|---------------|
| `social_posts` | `WHERE status = ? AND platform = ? ORDER BY created_at DESC` | Composite `(status, platform, created_at)` |
| `social_posts` | `WHERE agency_id = ? AND status = ? AND scheduled_at > ?` | Composite `(agency_id, status, scheduled_at)` |
| `email_campaigns` | `WHERE agency_id = ? AND status = ? ORDER BY created_at DESC` | Composite `(agency_id, status, created_at)` |
| `invoices` | `WHERE agency_id = ? AND status = ? AND due_date < NOW()` | Composite `(agency_id, status, due_date)` |
| `invoices` | `WHERE agency_id = ? ORDER BY created_at DESC` | Already covered |
| `workflows` | `WHERE agency_id = ? AND status = ?` | Composite `(agency_id, status)` |
| `workflows` | `WHERE category = ? AND is_active = ?` | Composite `(category, is_active)` |
| `media_assets` | `WHERE agency_id = ? AND file_type = ?` | Composite `(agency_id, file_type)` |
| `email_campaign_recipients` | `WHERE campaign_id = ? AND status = ?` | Composite `(campaign_id, status)` |
| `activity_logs` | `WHERE agency_id = ? AND action = ? AND created_at > ?` | Composite `(agency_id, action, created_at)` |
| `users` | `WHERE agency_id = ? AND role = ? AND is_active = ?` | Composite `(agency_id, role, is_active)` |

### 7.3 🔴 No Fulltext Indexes for Search
| File | Line | Issue |
|------|------|-------|
| `SearchController.php` | 41-95 | All search queries use `LIKE '%...%'` which cannot use indexes |
| | | Tables: `social_posts.content`, `campaigns.name/description`, `clients.name/email/company`, `content_assets.name/content`, `invoices.invoice_number`, `workflows.name` |

> **Recommendation**: Add FULLTEXT indexes for searchable columns or use Laravel Scout with Meilisearch/Algolia.

### 7.4 🟡 Counter Cache Columns Without Triggers
| Table | Column | Issue |
|-------|--------|-------|
| `agencies` | `posts_count`, `campaigns_count`, `clients_count`, `users_count`, `social_accounts_count`, `landing_pages_count`, `forms_count` | Updated via `increment()`/`decrement()` in controllers — risk of drift if any code path bypasses this |
| `campaigns` | `posts_count` | Same concern |
| `clients` | `campaigns_count`, `posts_count` | Same concern |
| `email_campaigns` | `recipients_count` | Same concern |

> **Recommendation**: Consider database triggers or periodic reconciliation job.

---

## 8. Configuration Issues

### 8.1 🔴 Queue Driver = `sync`
```
QUEUE_CONNECTION=sync
```
> **Impact**: All jobs (email sending, workflow execution, webhooks) run synchronously, blocking HTTP requests.

### 8.2 🟡 Cache Driver = `file`
```
CACHE_DRIVER=file
```
> **Impact**: File-based cache is slower than Redis, doesn't support tagging, and doesn't work well with multiple servers. Redis is configured but not used.

### 8.3 ✅ Redis Available
```
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```
> Redis is configured but `CACHE_DRIVER` and `QUEUE_CONNECTION` should use it.

---

## 9. Summary of Critical Issues (Priority Order)

| # | Priority | Category | Issue | File:Line |
|---|----------|----------|-------|-----------|
| 1 | 🔴 Critical | Config | Queue driver = `sync` blocks all jobs | `.env` |
| 2 | 🔴 Critical | Config | Cache driver = `file` instead of Redis | `.env` |
| 3 | 🔴 Critical | Collection | 4+ collection iterations on invoices page | `billing/invoices.blade.php:146-155` |
| 4 | 🔴 Critical | N+1 | Missing `with('client')` on invoices | `BillingController.php:29` |
| 5 | 🔴 Critical | Query | `getPlatformStats()` uses 6 cloned queries | `AnalyticsService.php:339-376` |
| 6 | 🔴 Critical | Search | LIKE '%...%' with no fulltext index | `SearchController.php:41-95` |
| 7 | 🟡 High | Frontend | No code splitting — all JS on every page | `vite.config.js` |
| 8 | 🟡 High | Cache | Duplicate `Cache::remember` calls for same key | `DashboardController.php:64-70` |
| 9 | 🟡 High | Cache | API dashboard counts same data twice | `Api/ApiDashboardController.php:30-44` |
| 10 | 🟡 Medium | Index | Missing composite indexes for common filters | Multiple tables |
| 11 | 🟡 Medium | Collection | Heavy `groupBy/map/sortBy` in AI agents | `AI/Agent/*.php` |
| 12 | 🟡 Medium | Counter | Manual increment/decrement risk of drift | Multiple controllers |

---

## 10. Quick Wins (Implement First)

1. **Switch to Redis**: Set `CACHE_DRIVER=redis` and `QUEUE_CONNECTION=redis` in `.env`
2. **Fix duplicate cache calls**: In `DashboardController.php:64-70`, store the count in a variable first
3. **Add eager loading**: `BillingController.php:29` — add `->with('client')`
4. **Optimize invoice summary**: Use SQL `SUM(CASE WHEN...)` instead of collection `where()->count()`
5. **Add composite indexes**: For `(status, platform, created_at)` on `social_posts`
6. **Lazy-load heavy JS**: Use dynamic imports for charts, editors, media library
7. **Cache search results**: Add `Cache::remember` to `SearchController`
8. **Fix `getPlatformStats()`**: Replace 6 cloned queries with single SQL aggregation
