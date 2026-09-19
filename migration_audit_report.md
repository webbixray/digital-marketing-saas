# Database Migration Audit Report
## Digital Marketing SaaS — 43 Migrations Analyzed

---

## 🔴 CRITICAL ISSUES (Fix Immediately)

### 1. Missing Foreign Key Constraints (Orphan Records Possible)

| # | File | Line | Table | Column | Issue |
|---|------|------|-------|--------|-------|
| C1 | `2026_09_04_153200_create_automation_and_business_tables.php` | 224 | `invoice_items` | `invoice_id` | `foreignId()->nullable()` but **missing `->constrained()`** — orphan invoice items possible when invoices deleted |
| C2 | `2026_09_04_153100_create_core_business_tables.php` | 193 | `campaign_post` | `social_post_id` | `foreignId()->nullable()` but **missing `->constrained()`** — orphaned pivot records |
| C3 | `2026_09_04_153100_create_core_business_tables.php` | 246 | `content_insights` | `social_post_id` | `foreignId()->nullable()` but **missing `->constrained()`** — orphaned insights |

**Fix:** Add `->constrained('invoices')`, `->constrained('social_posts')`, `->constrained('social_posts')` respectively. For nullable FKs use `->nullOnDelete()`.

---

### 2. Wrong Data Type for Foreign Keys

| # | File | Line | Table | Column | Issue |
|---|------|------|-------|--------|-------|
| C4 | `2026_09_16_000000_add_approval_to_social_posts.php` | 14 | `social_posts` | `approved_by` | Uses `integer()` instead of `foreignId()` — **cannot create FK constraint**, inconsistent with rest of schema |
| C5 | `2026_09_16_000000_add_approval_to_social_posts.php` | 17 | `social_posts` | `client_id` | Uses `integer()` instead of `foreignId()` — **cannot create FK constraint**, inconsistent |

**Fix:** Change to `foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()` and `foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete()`.

---

### 3. Missing agency_id on Multi-Tenant Tables

| # | File | Line | Table | Issue |
|---|------|------|-------|-------|
| C6 | `2026_09_07_110000_create_team_collaboration_tables.php` | 37 | `notifications` | **No `agency_id` column** — cannot scope notifications to agency, breaks multi-tenancy isolation |
| C7 | `2026_09_04_153200_create_automation_and_business_tables.php` | 53 | `workflow_logs` | **No `agency_id` column** — cannot filter by agency without joining through workflow_executions |
| C8 | `2026_09_07_080000_create_workflow_templates_and_versions.php` | 55 | `workflow_webhook_logs` | **No `agency_id` column** |

**Fix:** Add `foreignId('agency_id')->constrained()->cascadeOnDelete()` with appropriate index.

---

## 🟠 HIGH PRIORITY ISSUES

### 4. Missing Indexes on Foreign Key Columns

| # | File | Line | Table | Column | Issue |
|---|------|------|-------|--------|-------|
| H1 | `2026_09_04_153101_create_social_platforms_tables.php` | 84-93 | `social_post_scheduled_logs` | `social_post_id` | FK exists but **no index** on `social_post_id` alone — JOINs and lookups slow |
| H2 | `2026_09_15_000001_create_support_tickets_table.php` | 31-38 | `support_ticket_replies` | `ticket_id` | FK exists but **no index** — fetching replies by ticket requires full scan |
| H3 | `2026_09_15_000001_create_support_tickets_table.php` | 31-38 | `support_ticket_replies` | `user_id` | FK exists but **no index** — user reply lookups slow |
| H4 | `2026_09_07_110000_create_team_collaboration_tables.php` | 25-35 | `activity_feeds` | `user_id` | FK exists but **no index** — filtering by user slow |
| H5 | `2026_09_17_131542_add_ai_credits.php` | 20-28 | `ai_credit_purchases` | `agency_id` | FK exists but **no index** on `agency_id` alone |

---

### 5. Missing Composite Indexes for Common Multi-Tenant Query Patterns

Query pattern: `WHERE agency_id = X AND status = Y` / `ORDER BY created_at DESC`

| # | File | Line | Table | Missing Index | Impact |
|---|------|------|-------|---------------|--------|
| H6 | `2026_09_04_153101_create_social_platforms_tables.php` | 84-93 | `social_post_scheduled_logs` | `['social_post_id', 'status']` | Scheduled post status filtering slow |
| H7 | `2026_09_16_000000_add_approval_to_social_posts.php` | 13 | `social_posts` | `['agency_id', 'approval_status']` | Approval workflow queries slow |
| H8 | `2026_09_16_000000_add_approval_to_social_posts.php` | 17 | `social_posts` | `['agency_id', 'client_id']` | Client-scoped post queries slow |
| H9 | `2026_09_07_130000_create_white_label_tables.php` | 30-38 | `custom_templates` | `['agency_id', 'type']` | Template filtering by type slow |
| H10 | `2026_09_15_000002_create_ab_tests.php` | 41-51 | `ab_test_logs` | `['ab_test_id', 'created_at']` | Log retrieval/sorting slow |
| H11 | `2026_09_05_050000_create_feature_flags_table.php` | 11-25 | `feature_flags` | `['agency_id', 'feature_key']` | Feature lookup by key slow (only has 3-col index) |

---

### 6. Unsafe Migration Operations

| # | File | Line | Issue |
|---|------|------|-------|
| U1 | `2026_09_17_131540_update_plan_limits.php` | 24-32 | **Unbatched `UPDATE` on `agencies` table** — `DB::table('agencies')->where('subscription_plan', 'free')->update(...)` with no chunking or transaction. On production with thousands of agencies, this locks all free-tier rows for the duration of the update |
| U2 | `2026_09_15_000003_add_missing_composite_indexes.php` | 40-49 | **Silently swallows ALL exceptions** — `catch (Exception $e)` with no logging. Index creation failures (e.g., duplicate index, disk full) are invisible |
| U3 | `2026_09_05_070000_add_performance_indexes.php` | 63-72 | Same silent exception swallowing pattern — no visibility into failures |
| U4 | `2026_09_05_030000_add_missing_columns_to_tables.php` | 92 | **`down()` method is empty** — migration cannot be rolled back. Columns added in `up()` are permanent |

---

## 🟡 MEDIUM PRIORITY ISSUES

### 7. Data Type Inconsistencies

| # | File | Line | Table | Column | Issue |
|---|------|------|-------|--------|-------|
| M1 | `2026_09_04_153101_create_social_platforms_tables.php` | 36 | `social_accounts` | `scope` | Uses `integer()` — OAuth scopes are **strings** (space-separated like `"read write"`). Should be `text()` or `string()` |
| M2 | `2026_09_04_153101_create_social_platforms_tables.php` | 71 | `social_posts` | `engagement_rate` | `decimal(5,2)->default(0)->nullable()` — **redundant**: has both `default(0)` AND `nullable()`. Pick one |
| M3 | `2026_09_10_223916_add_agency_id_to_roles_table.php` | 12 | `roles` | `agency_id` | Uses `unsignedBigInteger()` instead of `foreignId()` — inconsistent with all other agency_id columns. Also **missing `->constrained()`** FK |
| M4 | `2026_09_07_100000_create_media_assets_table.php` | 14 | `media_assets` | `user_id` | `foreignId('user_id')->constrained()->cascadeOnDelete()` — **cascade on user delete will delete media**. Consider `nullOnDelete()` to preserve assets |
| M5 | `2026_09_15_000001_create_support_tickets_table.php` | 27 | `support_tickets` | `assigned_to` | Has FK but **no index** on `assigned_to` — finding tickets assigned to a user requires full scan |

---

### 8. Missing Indexes for Common WHERE Clauses

| # | File | Line | Table | Missing Index | Query Pattern |
|---|------|------|-------|---------------|---------------|
| M6 | `2026_09_04_153100_create_core_business_tables.php` | 298-311 | `activity_logs` | `['agency_id', 'action']` | Filter logs by action type within agency |
| M7 | `2026_09_04_153101_create_social_platforms_tables.php` | 95-101 | `social_platform_cache` | `['expires_at']` | Cache cleanup queries |
| M8 | `2026_09_04_153200_create_automation_and_business_tables.php` | 109-139 | `email_campaigns` | `['agency_id', 'type', 'status']` | Dashboard filtering |
| M9 | `2026_09_04_153200_create_automation_and_business_tables.php` | 64-91 | `inbox_messages` | `['agency_id', 'status', 'received_at']` | Inbox listing with sort |
| M10 | `2026_09_07_100000_create_media_assets_table.php` | 11-34 | `media_assets` | `['agency_id', 'mime_type']` | Filter by file type |

---

### 9. Missing Composite Unique Constraints

| # | File | Line | Table | Issue |
|---|------|------|-------|-------|
| U5 | `2026_09_07_130000_create_white_label_tables.php` | 30-38 | `custom_templates` | No unique constraint on `['agency_id', 'type', 'name']` — duplicate template names possible per agency |
| U6 | `2026_09_08_000000_create_email_templates_table.php` | 11-24 | `email_templates` | No unique constraint on `['agency_id', 'slug']` — slug unique is global, not per-agency |

---

## 🔵 LOW PRIORITY / OBSERVATIONS

| # | File | Line | Observation |
|---|------|------|-------------|
| L1 | `2026_09_04_152628_create_permission_tables.php` | 38-52 | `roles.team_foreign_key` uses `unsignedBigInteger` without FK constraint to agencies — intentional for Spatie Permission, but means no referential integrity |
| L2 | `2026_09_05_040000_add_telegram_fields_to_users_table.php` | 12 | `telegram_chat_id` has `->unique()->nullable()` — multiple NULLs allowed in MySQL but not in all DBs. Consider partial index if using PostgreSQL |
| L3 | `2026_09_14_200321_create_telescope_entries_table.php` | 24-39 | Telescope entries table is fine but consider adding `agency_id` for multi-tenant debugging |
| L4 | `2026_09_04_153100_create_core_business_tables.php` | 190-196 | `campaign_post` pivot has `$table->id()` — consider using the unique composite as primary key instead to save space |
| L5 | `2026_09_17_134135_create_client_reports_table.php` | 25 | `access_token` has `->unique()` — ensure this is a proper random token, not sequential |

---

## Summary Statistics

| Priority | Count |
|----------|-------|
| 🔴 CRITICAL | 8 |
| 🟠 HIGH | 12 |
| 🟡 MEDIUM | 10 |
| 🔵 LOW | 5 |
| **Total** | **35** |

---

## Top 5 Immediate Actions

1. **Fix C1-C3**: Add `->constrained()` to `invoice_items.invoice_id`, `campaign_post.social_post_id`, `content_insights.social_post_id`
2. **Fix C4-C5**: Change `approved_by` and `client_id` in `social_posts` from `integer()` to `foreignId()` with proper constraints
3. **Fix C6**: Add `agency_id` to `notifications` table — this is a multi-tenancy gap
4. **Fix U1**: Add chunking or transaction to `update_plan_limits.php` raw UPDATE
5. **Fix H1-H5**: Add indexes to FK columns that lack them (`social_post_scheduled_logs`, `support_ticket_replies`, `activity_feeds`, `ai_credit_purchases`)
