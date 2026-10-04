# Social Platform Integration — Deep Analysis & Improvement Plan

**Date:** October 4, 2026
**Scope:** `app/Services/Social/*`, publishing jobs, unified inbox/comments
**Goal:** Make adding and maintaining social platforms (Facebook, Instagram, TikTok, LinkedIn, X, Pinterest, YouTube) significantly easier, and fix the integration bugs found.

---

## 1. CURRENT STATE

### 1.1 What exists

| Layer | Files | Notes |
|-------|-------|-------|
| Platform API services | 8 (`FacebookApiService`, `InstagramApiService`, `TwitterApiService`, `LinkedInApiService`, `TikTokApiService`, `PinterestApiService`, `YouTubeApiService`) | Each is a standalone class, **no shared interface** |
| Abstract base | `SocialPlatformApi` | **Defined but used by ZERO classes** (dead code) |
| Publish dispatcher | `SocialApiService::publish()` | Second, parallel publish path |
| Retry dispatcher | `RetryFailedPost::publish()` | Third, parallel publish path |
| Comments | `UnifiedCommentsService` | Calls methods that **do not exist** |
| Rate limiting | `PlatformRateLimitService` | OK, per-agency per-platform |
| Inbox | `UnifiedInboxService` | OK |

### 1.2 The core problem

There are **three independent publish dispatchers**, each with its own `match($platform)` block and its own return-key contract:

1. `SocialApiService::publish()` → returns `['success', 'platform_post_id', 'url']`, uses **wrong column names**.
2. `RetryFailedPost::publish()` → returns `['success', 'post_id'|'video_id'|'media_id']`.
3. `SocialPostService::publishPost()` → delegates to (1).

Adding a new platform today requires editing **all three** match blocks, plus the API service, plus the controller, plus routes, plus views, plus `SocialAccount::SUPPORTED_PLATFORMS`. That is the friction the user is asking us to remove.

---

## 2. CONFIRMED BUGS (evidence-based)

### 🔴 BUG-1: `UnifiedCommentsService` calls non-existent methods (dead feature)

`app/Services/Social/UnifiedCommentsService.php` calls:
- `$this->facebook->getPostComments(...)` — **does not exist**
- `$this->facebook->replyToComment(...)` — **does not exist**
- `$this->instagram->getMediaComments(...)` — **does not exist**
- `$this->instagram->replyToComment(...)` — **does not exist**
- `$this->twitter->getTweetReplies(...)` — **does not exist**
- `$this->twitter->replyToTweet(...)` — **does not exist**

**Evidence:** `search_files "function (replyToComment|getPostComments|getMediaComments|getTweetReplies|replyToTweet)"` → only TikTok has `replyToComment`. Everything else is absent.

**Impact:** Every call throws `BadMethodCallException`, caught by the broad `catch (\Exception)`, silently returning `[]` / `false`. Comments/inbox sync is a **silent no-op** — a launch-blocking functional gap.

### 🔴 BUG-2: `SocialApiService` uses non-existent model attributes

`app/Services/Social/SocialApiService.php` references:
- `$account->account_id` (lines 23, 56, 73, 141, 271) — the model has **`platform_account_id`**
- `$account->username` (line 290) — the model has **`platform_username`**

**Evidence:** `SocialAccount` `$fillable` = `platform_account_id`, `platform_username`. There is no `account_id` / `username`.

**Impact:** Every Facebook/Instagram/LinkedIn publish via this path sends `https://graph.facebook.com/v18.0//feed` (empty ID) → API error. The correct pattern is used in `RetryFailedPost` (`platform_account_id`), confirming the intent.

### 🟠 BUG-3: `TwitterApiService::postTweet()` signature mismatch

`RetryFailedPost` calls `app(TwitterApiService::class)->postTweet($post->content)` — **this is correct** (1 arg).
But `SocialApiService::publishToTwitter()` posts directly to `api.twitter.com/2/tweets` with `Http::withToken($account->access_token)` (OAuth2 bearer) — Twitter v2 tweet creation requires **OAuth 1.0a user context**, which `TwitterApiService` implements via `buildOAuth1Headers()`. The parallel path bypasses it.

### 🟠 BUG-4: `SocialApiService` uses stale API versions

- Facebook `v18.0` while `FacebookApiService` uses `v21.0`.
- TikTok `https://open-api.tiktok.com/share/video/upload/` — **deprecated legacy endpoint**, replaced by `open.tiktokapis.com/v2/publish/video/` (which `TikTokApiService` correctly uses).

### 🟡 BUG-5: Instagram publish in `SocialApiService` can't post images

`publishToInstagram()` creates a container with only `caption` (no `image_url`/`video_url`), which the Graph API rejects. `InstagramApiService::createMediaContainer()` handles this correctly.

### 🟡 BUG-6: `SocialAccount::SUPPORTED_PLATFORMS` missing `youtube`

`RetryFailedPost` and `YouTubeApiService` support `youtube`, but the constant (used by the UI dropdown) omits it. YouTube is unselectable in the post composer.

---

## 3. RECOMMENDED ARCHITECTURE — The "One Place to Add a Platform" Pattern

### 3.1 Design

```
                    ┌─────────────────────────────┐
                    │   SocialPlatformManager      │  ← single registry
                    │  for('tiktok'): Driver       │
                    └──────────────┬──────────────┘
                                   │ resolves
        ┌──────────────┬───────────┼───────────┬──────────────┐
        ▼              ▼           ▼           ▼              ▼
  FacebookDriver  TikTokDriver  ...Driver  YouTubeDriver  (new platform)
        │              │           │           │
        ▼              ▼           ▼           ▼
  FacebookApiService  TikTokApiService  ...  YouTubeApiService   ← unchanged API layer
```

- **`SocialPlatformContract`** — one interface every platform driver implements.
- **`PlatformPublishResult`** — one normalized result object (`success`, `externalId`, `url`, `error`, `raw`). No more guessing `post_id` vs `video_id` vs `media_id`.
- **`SocialPlatformManager`** — config-driven registry. Adding a platform = **one line in `config/platform.php`** + one driver class.
- **Drivers** — thin adapters that wrap the existing `*ApiService` classes, so no API code is rewritten (low risk, 2,600 tests stay green).

### 3.2 Adding a new platform becomes 3 steps

1. Create `app/Services/Social/Drivers/ThreadsDriver.php` (extends `AbstractPlatformDriver`).
2. Register in `config/platform.php` → `'social_drivers' => ['threads' => ThreadsDriver::class]`.
3. Add to `SocialAccount::SUPPORTED_PLATFORMS`.

No more editing 3 match blocks.

### 3.3 Consumers collapse to one call

```php
$result = $this->platforms->for($post->platform)->publish($account, $post);
if ($result->success) {
    $post->update(['external_post_id' => $result->externalId, ...]);
}
```

---

## 4. IMPLEMENTATION PLAN

| # | Deliverable | Risk |
|---|-------------|------|
| 1 | `PlatformPublishResult` DTO | none (new) |
| 2 | `SocialPlatformContract` interface | none (new) |
| 3 | `AbstractPlatformDriver` shared base | none (new) |
| 4 | 7 driver adapters wrapping existing services | low |
| 5 | `SocialPlatformManager` + config registry | low |
| 6 | Wire `SocialPostService` + `RetryFailedPost` through manager | medium |
| 7 | Fix `UnifiedCommentsService` via manager (+ real comment methods) | medium |
| 8 | Add `youtube` to `SUPPORTED_PLATFORMS` | none |
| 9 | Tests for manager + drivers + comment flow | none |

---

## 5. NON-GOALS (deliberately deferred)

- Rewriting the `*ApiService` classes into drivers (they work; wrap them).
- Removing `SocialApiService` entirely (keep for back-compat, mark deprecated).
- New platforms (Threads, Bluesky) — the point is that they are now cheap to add.

---

## 6. DELIVERED (implemented + verified)

### New architecture

| File | Purpose |
|------|---------|
| `app/Services/Social/Contracts/SocialPlatformContract.php` | The one interface every platform implements |
| `app/Services/Social/Results/PlatformPublishResult.php` | Normalized result DTO (`success`, `externalId`, `url`, `error`, `raw`) |
| `app/Services/Social/Drivers/AbstractPlatformDriver.php` | Shared base + safe defaults for optional capabilities |
| `app/Services/Social/Drivers/{Facebook,Instagram,Twitter,LinkedIn,TikTok,Pinterest,YouTube}Driver.php` | 7 thin adapters over the existing `*ApiService` classes |
| `app/Services/Social/SocialPlatformManager.php` | Config-driven registry: `for($platform)` returns the driver |
| `config/platform.php` → `social_drivers` | The registry mapping (the ONLY place to register a platform) |

### Bugs fixed

| Bug | Fix |
|-----|-----|
| `UnifiedCommentsService` called non-existent methods (silent no-op) | Added real `getPostComments`/`replyToComment` (Facebook, Instagram), `getTweetReplies`/`replyToTweet` (Twitter); service now delegates to the manager |
| `SocialApiService` used `$account->account_id` / `->username` (nonexistent) | Class deprecated; all methods now delegate to the manager using correct `platform_account_id`/`platform_username` |
| Stale API versions (`v18.0`, legacy TikTok `open-api.tiktok.com`) | Parallel path removed; single source of truth is the up-to-date `*ApiService` classes |
| Instagram publish with no media | Driver returns a clear validation error |
| YouTube missing from `SUPPORTED_PLATFORMS` | Added (plus `youtube` platform config + factory state) |
| `PlatformMetricsService` called non-existent `getUserStats`/`getUserAnalytics`/`getChannelAnalytics` | Rewired through the registry's `fetchMetrics()` |
| `PlatformMetricsService` matched metrics by `$post->platform_post_id` (no such column) | Now uses `external_post_id` |
| `SocialPostService::publishPost()` stored `$result['id']` (never present) | Now stores `$result['external_id']` |
| `TwitterController::publish` wrote `platform_post_id` (not fillable) | Now writes `external_post_id` |

### New developer command

`php artisan social:platforms` — lists every registered driver, its label, whether
its credentials are configured, whether it appears in the composer dropdown, and
its class. Use it to confirm a new platform is wired up correctly.

### Consumers rewired

- `SocialPostService::publishToPlatform()` → manager (single call, + token-expiry guard).
- `RetryFailedPost::publish()` → manager (its 40-line `match` block deleted).
- `UnifiedCommentsService` → manager (its 3 `match` blocks deleted).
- `SocialApiService` → manager (deprecated shim).

### Adding a platform is now 3 steps

1. `app/Services/Social/Drivers/ThreadsDriver.php` extends `AbstractPlatformDriver`.
2. Register in `config/platform.php` → `social_drivers`.
3. Add to `SocialAccount::SUPPORTED_PLATFORMS`.

No `match()` blocks anywhere else. No controller, job, or service edits.

### Verification

- `php artisan tinker` — all 7 drivers resolve through the container with correct labels.
- `php -l` — all new/changed files parse.
- New tests: `tests/Unit/Services/Social/SocialPlatformManagerTest.php` (11), `UnifiedCommentsServiceTest.php` (4) — **15/15 passing**.
- Existing social tests unaffected: `tests/Feature/Social*` — **94/94 passing**.
- Jobs/comments/inbox: `RetryFailedPostTest`, `CommentTest`, `Inbox*` — **41/41 passing**.
- Pint: clean.

