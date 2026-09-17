# Implementation Plan — Digital Marketing SaaS Growth Initiatives

## Executive Summary

This plan covers 6 high-impact initiatives to increase revenue, retention, and user acquisition. Estimated timeline: 6 months. Expected revenue impact: +200-300% MRR.

---

## Phase 1: Revenue Optimization (Months 1-2)

### 1.1 Free Tier Optimization

**Goal:** Increase free-to-paid conversion from ~2% to ~8%.

#### Database Migration

```php
// database/migrations/2026_09_17_000001_update_plan_limits.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new plan limit columns to agencies table
        Schema::table('agencies', function (Blueprint $table) {
            $table->unsignedInteger('free_posts_limit')->default(20)->after('subscription_plan');
            $table->unsignedInteger('free_ai_limit')->default(10)->after('free_posts_limit');
            $table->unsignedInteger('free_accounts_limit')->default(1)->after('free_ai_limit');
            $table->unsignedInteger('free_team_limit')->default(1)->after('free_accounts_limit');
            $table->unsignedInteger('free_clients_limit')->default(1)->after('free_team_limit');
        });

        // Update existing free tier agencies
        DB::table('agencies')
            ->where('subscription_plan', 'free')
            ->update([
                'free_posts_limit' => 20,
                'free_ai_limit' => 10,
                'free_accounts_limit' => 1,
                'free_team_limit' => 1,
                'free_clients_limit' => 1,
            ]);
    }

    public function down(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn([
                'free_posts_limit',
                'free_ai_limit',
                'free_accounts_limit',
                'free_team_limit',
                'free_clients_limit',
            ]);
        });
    }
};
```

#### Plan Configuration

```php
// config/plans.php
<?php

return [
    'free' => [
        'name' => 'Free',
        'price' => 0,
        'limits' => [
            'posts_per_month' => 20,
            'ai_generations_per_month' => 10,
            'social_accounts' => 1,
            'team_members' => 1,
            'clients' => 1,
            'storage_mb' => 100,
        ],
        'features' => [
            'ai_content_generation' => true,
            'basic_analytics' => true,
            'email_support' => false,
            'white_label' => false,
            'api_access' => false,
        ],
    ],
    'starter' => [
        'name' => 'Starter',
        'price' => 19,
        'limits' => [
            'posts_per_month' => 100,
            'ai_generations_per_month' => 50,
            'social_accounts' => 3,
            'team_members' => 3,
            'clients' => 5,
            'storage_mb' => 1000,
        ],
        'features' => [
            'ai_content_generation' => true,
            'basic_analytics' => true,
            'email_support' => true,
            'white_label' => false,
            'api_access' => false,
        ],
    ],
    'pro' => [
        'name' => 'Pro',
        'price' => 49,
        'limits' => [
            'posts_per_month' => 500,
            'ai_generations_per_month' => 200,
            'social_accounts' => 10,
            'team_members' => 10,
            'clients' => 25,
            'storage_mb' => 5000,
        ],
        'features' => [
            'ai_content_generation' => true,
            'advanced_analytics' => true,
            'priority_support' => true,
            'white_label' => false,
            'api_access' => true,
        ],
    ],
    'agency' => [
        'name' => 'Agency',
        'price' => 99,
        'limits' => [
            'posts_per_month' => -1, // unlimited
            'ai_generations_per_month' => -1,
            'social_accounts' => 25,
            'team_members' => 25,
            'clients' => 100,
            'storage_mb' => 20000,
        ],
        'features' => [
            'ai_content_generation' => true,
            'advanced_analytics' => true,
            'priority_support' => true,
            'white_label' => true,
            'api_access' => true,
        ],
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 299,
        'limits' => [
            'posts_per_month' => -1,
            'ai_generations_per_month' => -1,
            'social_accounts' => -1,
            'team_members' => -1,
            'clients' => -1,
            'storage_mb' => -1,
        ],
        'features' => [
            'ai_content_generation' => true,
            'advanced_analytics' => true,
            'dedicated_support' => true,
            'white_label' => true,
            'api_access' => true,
            'custom_integrations' => true,
        ],
    ],
];
```

#### Quota Service Update

```php
// app/Services/QuotaService.php (add new methods)
<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Support\Facades\Cache;

class QuotaService
{
    /**
     * Check if agency has reached their plan limit
     */
    public function hasReachedLimit(Agency $agency, string $feature): bool
    {
        $plan = config("plans.{$agency->subscription_plan}");
        $limit = $plan['limits'][$feature] ?? 0;

        // -1 means unlimited
        if ($limit === -1) {
            return false;
        }

        $usage = $this->getUsage($agency, $feature);

        return $usage >= $limit;
    }

    /**
     * Get current usage for a feature
     */
    public function getUsage(Agency $agency, string $feature): int
    {
        $cacheKey = "usage:{$agency->id}:{$feature}:" . now()->format('Y-m');

        return Cache::remember($cacheKey, 3600, function () use ($agency, $feature) {
            return match ($feature) {
                'posts_per_month' => $agency->socialPosts()
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'ai_generations_per_month' => $agency->aiContentLogs()
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'social_accounts' => $agency->socialAccounts()->count(),
                'team_members' => $agency->teamMembers()->count(),
                'clients' => $agency->clients()->count(),
                default => 0,
            };
        });
    }

    /**
     * Get usage with limit info for frontend display
     */
    public function getQuotaStatus(Agency $agency): array
    {
        $plan = config("plans.{$agency->subscription_plan}");
        $limits = $plan['limits'];

        $status = [];
        foreach ($limits as $feature => $limit) {
            $usage = $this->getUsage($agency, $feature);
            $status[$feature] = [
                'used' => $usage,
                'limit' => $limit,
                'unlimited' => $limit === -1,
                'percentage' => $limit === -1 ? 0 : min(100, round(($usage / $limit) * 100)),
                'remaining' => $limit === -1 ? -1 : max(0, $limit - $usage),
            ];
        }

        return $status;
    }

    /**
     * Increment usage counter
     */
    public function incrementUsage(Agency $agency, string $feature): void
    {
        $cacheKey = "usage:{$agency->id}:{$feature}:" . now()->format('Y-m');
        Cache::increment($cacheKey);
    }
}
```

#### Upgrade Prompt Component

```php
// app/Http/Controllers/QuotaController.php
<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Services\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuotaController extends Controller
{
    public function __construct(
        private QuotaService $quotaService,
    ) {}

    /**
     * Get current quota status for the agency
     */
    public function status(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        return response()->json([
            'plan' => $agency->subscription_plan,
            'quotas' => $this->quotaService->getQuotaStatus($agency),
            'upgrade_available' => $agency->subscription_plan === 'free',
        ]);
    }

    /**
     * Check if action is allowed
     */
    public function check(Request $request): JsonResponse
    {
        $feature = $request->input('feature');
        $agency = $request->user()->agency;

        $hasReached = $this->quotaService->hasReachedLimit($agency, $feature);

        return response()->json([
            'allowed' => !$hasReached,
            'feature' => $feature,
            'usage' => $this->quotaService->getUsage($agency, $feature),
            'limit' => config("plans.{$agency->subscription_plan}.limits.{$feature}"),
        ]);
    }
}
```

#### Routes

```php
// routes/api.php (add to existing routes)
Route::get('/quota', [QuotaController::class, 'status']);
Route::post('/quota/check', [QuotaController::class, 'check']);
```

---

### 1.2 Usage-Based AI Pricing

**Goal:** Add $9/100 AI generations as an add-on for all plans.

#### Database Migration

```php
// database/migrations/2026_09_17_000002_add_ai_credits.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->unsignedInteger('ai_credits')->default(0)->after('free_clients_limit');
            $table->unsignedInteger('ai_credits_purchased')->default(0)->after('ai_credits');
        });

        // Create AI credit purchase logs
        Schema::create('ai_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('credits');
            $table->decimal('amount', 10, 2);
            $table->string('stripe_payment_id')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_purchases');

        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn(['ai_credits', 'ai_credits_purchased']);
        });
    }
};
```

#### AI Credit Purchase Controller

```php
// app/Http/Controllers/AI/AICreditController.php
<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AICreditPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class AICreditController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Purchase AI credits
     */
    public function purchase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'credits' => 'required|integer|in:100,500,1000,5000]',
        ]);

        $credits = $validated['credits'];
        $price = $this->calculatePrice($credits);

        // Create Stripe checkout session
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => "{$credits} AI Credits",
                        'description' => 'Additional AI content generations',
                    ],
                    'unit_amount' => $price * 100, // cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('billing.credits.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.credits.cancel'),
            'metadata' => [
                'agency_id' => $request->user()->agency_id,
                'credits' => $credits,
            ],
        ]);

        return response()->json([
            'checkout_url' => $session->url,
            'credits' => $credits,
            'price' => $price,
        ]);
    }

    /**
     * Handle successful credit purchase
     */
    public function success(Request $request): JsonResponse
    {
        $session = Session::retrieve($request->input('session_id'));

        if ($session->payment_status !== 'paid') {
            return response()->json(['error' => 'Payment not completed'], 400);
        }

        $agency = Agency::find($session->metadata->agency_id);
        $credits = (int) $session->metadata->credits;

        // Add credits to agency
        $agency->increment('ai_credits', $credits);
        $agency->increment('ai_credits_purchased', $credits);

        // Log purchase
        AICreditPurchase::create([
            'agency_id' => $agency->id,
            'credits' => $credits,
            'amount' => $session->amount_total / 100,
            'stripe_payment_id' => $session->payment_intent,
        ]);

        return response()->json([
            'success' => true,
            'credits_added' => $credits,
            'total_credits' => $agency->ai_credits,
        ]);
    }

    /**
     * Calculate price for credits (bulk discount)
     */
    private function calculatePrice(int $credits): float
    {
        $basePrice = 0.09; // $0.09 per credit

        $discount = match (true) {
            $credits >= 5000 => 0.30, // 30% off
            $credits >= 1000 => 0.20, // 20% off
            $credits >= 500 => 0.10,  // 10% off
            default => 0,
        };

        return round($credits * $basePrice * (1 - $discount), 2);
    }

    /**
     * Get current credit balance
     */
    public function balance(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        return response()->json([
            'credits' => $agency->ai_credits,
            'total_purchased' => $agency->ai_credits_purchased,
        ]);
    }
}
```

#### AI Credit Middleware

```php
// app/Http/Middleware/EnforceAICredits.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceAICredits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->agency) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $agency = $user->agency;

        // Check if agency has AI credits or is on a plan with AI included
        $plan = config("plans.{$agency->subscription_plan}");
        $hasUnlimitedAI = $plan['limits']['ai_generations_per_month'] === -1;

        if (! $hasUnlimitedAI && $agency->ai_credits <= 0) {
            return response()->json([
                'error' => 'No AI credits remaining.',
                'upgrade_url' => route('billing.credits.purchase'),
                'credits' => 0,
            ], 402);
        }

        // Deduct credit if not unlimited
        if (! $hasUnlimitedAI) {
            $agency->decrement('ai_credits');
        }

        return $next($request);
    }
}
```

---

## Phase 2: Client-Facing Dashboards (Months 2-4)

### 2.1 White-Label Report System

**Goal:** Agencies can share branded reports with their clients.

#### Database Migrations

```php
// database/migrations/2026_09_17_000003_create_client_reports.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('report_data'); // Cached report data
            $table->string('period'); // monthly, quarterly, yearly
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('draft'); // draft, published, archived
            $table->string('access_token')->unique(); // For public access
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'client_id']);
            $table->index('access_token');
        });

        // White label settings per agency
        Schema::create('white_label_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('brand_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('custom_domain')->nullable();
            $table->string('email_from_name')->nullable();
            $table->string('email_from_address')->nullable();
            $table->json('custom_css')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('white_label_settings');
        Schema::dropIfExists('client_reports');
    }
};
```

#### Client Report Model

```php
// app/Models/ClientReport.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'client_id',
        'title',
        'slug',
        'report_data',
        'period',
        'start_date',
        'end_date',
        'status',
        'access_token',
        'published_at',
    ];

    protected $casts = [
        'report_data' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            $report->access_token = Str::random(32);
            $report->slug = Str::slug($report->title) . '-' . Str::random(6);
        });
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('public.client-report', [
            'slug' => $this->slug,
            'token' => $this->access_token,
        ]);
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
```

#### Client Report Controller

```php
// app/Http/Controllers/ClientReportController.php
<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientReport;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientReportController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics,
    ) {}

    /**
     * Generate a new client report
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $agency = $request->user()->agency;

        // Verify client belongs to agency
        $client = Client::where('agency_id', $agency->id)
            ->where('id', $validated['client_id'])
            ->firstOrFail();

        // Generate report data
        $reportData = $this->analytics->generateClientReport(
            $agency,
            $client,
            $validated['start_date'],
            $validated['end_date']
        );

        $report = ClientReport::create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'title' => $validated['title'],
            'report_data' => $reportData,
            'period' => $validated['period'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'draft',
        ]);

        return response()->json([
            'report' => $report,
            'public_url' => $report->public_url,
        ], 201);
    }

    /**
     * Publish report (make it publicly accessible)
     */
    public function publish(Request $request, ClientReport $report): JsonResponse
    {
        $this->authorize('update', $report);

        $report->publish();

        return response()->json([
            'success' => true,
            'public_url' => $report->public_url,
        ]);
    }

    /**
     * Get report preview data
     */
    public function preview(Request $request, ClientReport $report): JsonResponse
    {
        $this->authorize('view', $report);

        return response()->json([
            'report' => $report,
            'data' => $report->report_data,
        ]);
    }

    /**
     * List all reports for agency
     */
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $reports = ClientReport::where('agency_id', $agency->id)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reports);
    }
}
```

#### Public Report Controller (No Auth)

```php
// app/Http/Controllers/PublicClientReportController.php
<?php

namespace App\Http\Controllers;

use App\Models\ClientReport;
use App\Models\WhiteLabelSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicClientReportController extends Controller
{
    /**
     * Display public client report (no authentication required)
     */
    public function show(Request $request, string $slug, string $token): View
    {
        $report = ClientReport::where('slug', $slug)
            ->where('access_token', $token)
            ->where('status', 'published')
            ->firstOrFail();

        $whiteLabel = WhiteLabelSetting::where('agency_id', $report->agency_id)->first();

        return view('reports.client-public', [
            'report' => $report,
            'whiteLabel' => $whiteLabel,
            'agency' => $report->agency,
            'client' => $report->client,
        ]);
    }
}
```

#### Routes

```php
// routes/api.php
Route::apiResource('client-reports', ClientReportController::class);
Route::post('client-reports/{report}/publish', [ClientReportController::class, 'publish']);

// routes/web.php (public, no auth)
Route::get('/reports/{slug}/{token}', [PublicClientReportController::class, 'show'])
    ->name('public.client-report');
```

---

## Phase 3: Referral Program (Month 3)

### 3.1 Referral System

#### Database Migration

```php
// database/migrations/2026_09_17_000004_create_referrals.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('users')->nullable()->onDelete('set null');
            $table->string('referee_email');
            $table->string('referral_code')->unique();
            $table->string('status')->default('pending'); // pending, signed_up, converted, rewarded
            $table->decimal('reward_amount', 10, 2)->default(0);
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index('referral_code');
            $table->index(['referrer_id', 'status']);
        });

        // Add referral tracking to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('agency_id');
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referred_by']);
        });

        Schema::dropIfExists('referrals');
    }
};
```

#### Referral Service

```php
// app/Services/ReferralService.php
<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * Generate unique referral code for user
     */
    public function generateCode(User $user): string
    {
        $code = strtoupper(substr(md5($user->id . $user->email), 0, 8));

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Track referral signup
     */
    public function trackSignup(string $referralCode, User $newUser): ?Referral
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (! $referrer || $referrer->id === $newUser->id) {
            return null;
        }

        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referee_id' => $newUser->id,
            'referee_email' => $newUser->email,
            'referral_code' => $referralCode,
            'status' => 'signed_up',
        ]);

        $newUser->update(['referred_by' => $referrer->id]);

        return $referral;
    }

    /**
     * Convert referral when user subscribes to paid plan
     */
    public function convertReferral(User $user): void
    {
        $referral = Referral::where('referee_id', $user->id)
            ->where('status', 'signed_up')
            ->first();

        if (! $referral) {
            return;
        }

        $referral->update([
            'status' => 'converted',
            'converted_at' => now(),
            'reward_amount' => 25.00,
        ]);

        // Credit referrer
        $this->creditReferrer($referral->referrer, 25.00);
    }

    /**
     * Credit referrer with reward
     */
    private function creditReferrer(User $referrer, float $amount): void
    {
        // Add credit to agency or send via Stripe
        $agency = $referrer->agency;

        if ($agency) {
            // Option 1: Add account credit
            $agency->increment('account_credit', $amount);

            // Option 2: Create Stripe credit (more complex)
            // $this->createStripeCredit($referrer, $amount);
        }

        // Mark referral as rewarded
        Referral::where('referrer_id', $referrer->id)
            ->where('status', 'converted')
            ->update([
                'status' => 'rewarded',
                'rewarded_at' => now(),
            ]);
    }

    /**
     * Get referral stats for user
     */
    public function getStats(User $user): array
    {
        $referrals = Referral::where('referrer_id', $user->id);

        return [
            'total_referrals' => $referrals->count(),
            'signed_up' => $referrals->where('status', 'signed_up')->count(),
            'converted' => $referrals->where('status', 'converted')->count(),
            'rewarded' => $referrals->where('status', 'rewarded')->count(),
            'total_earned' => $referrals->where('status', 'rewarded')->sum('reward_amount'),
            'referral_code' => $user->referral_code,
            'referral_url' => route('register', ['ref' => $user->referral_code]),
        ];
    }
}
```

#### Referral Controller

```php
// app/Http/Controllers/ReferralController.php
<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(
        private ReferralService $referralService,
    ) {}

    /**
     * Get referral stats and link
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->referral_code) {
            $this->referralService->generateCode($user);
            $user->refresh();
        }

        return response()->json($this->referralService->getStats($user));
    }

    /**
     * Generate new referral code
     */
    public function generate(Request $request): JsonResponse
    {
        $user = $request->user();
        $code = $this->referralService->generateCode($user);

        return response()->json([
            'referral_code' => $code,
            'referral_url' => route('register', ['ref' => $code]),
        ]);
    }
}
```

---

## Phase 4: Metrics & Analytics (Month 4)

### 4.1 SaaS Metrics Tracking

#### Metrics Controller

```php
// app/Http/Controllers/MetricsController.php
<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MetricsController extends Controller
{
    /**
     * Get key SaaS metrics (admin only)
     */
    public function index(): JsonResponse
    {
        $cacheKey = 'saas_metrics:' . now()->format('Y-m-d');

        return response()->json(Cache::remember($cacheKey, 3600, function () {
            return [
                'users' => $this->getUserMetrics(),
                'revenue' => $this->getRevenueMetrics(),
                'engagement' => $this->getEngagementMetrics(),
                'conversion' => $this->getConversionMetrics(),
            ];
        }));
    }

    private function getUserMetrics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('last_active_at', '>=', now()->subDays(30))->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)->count();

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'new_this_month' => $newUsersThisMonth,
            'activation_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0,
        ];
    }

    private function getRevenueMetrics(): array
    {
        $plans = Agency::select('subscription_plan', DB::raw('count(*) as count'))
            ->groupBy('subscription_plan')
            ->pluck('count', 'subscription_plan');

        $mrr = $this->calculateMRR($plans);

        return [
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'plan_distribution' => $plans,
            'arpu' => $plans->sum() > 0 ? round($mrr / $plans->sum(), 2) : 0,
        ];
    }

    private function calculateMRR($plans): float
    {
        $prices = [
            'free' => 0,
            'starter' => 19,
            'pro' => 49,
            'agency' => 99,
            'enterprise' => 299,
        ];

        $mrr = 0;
        foreach ($plans as $plan => $count) {
            $mrr += ($prices[$plan] ?? 0) * $count;
        }

        return $mrr;
    }

    private function getEngagementMetrics(): array
    {
        return [
            'total_posts' => \App\Models\SocialPost::count(),
            'posts_this_month' => \App\Models\SocialPost::whereMonth('created_at', now()->month)->count(),
            'total_campaigns' => \App\Models\Campaign::count(),
            'active_workflows' => \App\Models\Workflow::where('status', 'active')->count(),
        ];
    }

    private function getConversionMetrics(): array
    {
        $freeUsers = Agency::where('subscription_plan', 'free')->count();
        $paidUsers = Agency::where('subscription_plan', '!=', 'free')->count();
        $total = $freeUsers + $paidUsers;

        return [
            'free_to_paid_rate' => $total > 0 ? round(($paidUsers / $total) * 100, 2) : 0,
            'free_users' => $freeUsers,
            'paid_users' => $paidUsers,
            'trial_conversion_rate' => 0, // TODO: Implement trial tracking
        ];
    }
}
```

---

## Phase 5: UX Improvements (Months 4-6)

### 5.1 AI-Powered Dashboard Insights

```php
// app/Services/DashboardInsightsService.php
<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\SocialPost;
use Carbon\Carbon;

class DashboardInsightsService
{
    /**
     * Generate AI-powered insights for dashboard
     */
    public function generateInsights(Agency $agency): array
    {
        $insights = [];

        // Best posting time
        $bestTime = $this->getBestPostingTime($agency);
        if ($bestTime) {
            $insights[] = [
                'type' => 'timing',
                'icon' => 'clock',
                'title' => 'Optimal Posting Time',
                'message' => "Your audience is most active at {$bestTime}. Schedule posts accordingly.",
                'action' => [
                    'label' => 'View Analytics',
                    'url' => route('analytics.index'),
                ],
            ];
        }

        // Performance trend
        $trend = $this->getPerformanceTrend($agency);
        if ($trend['direction'] === 'up') {
            $insights[] = [
                'type' => 'success',
                'icon' => 'trending-up',
                'title' => 'Engagement Trending Up',
                'message' => "Last 7 days: {$trend['platform']} engagement is up {$trend['percentage']}%.",
                'action' => [
                    'label' => 'View Report',
                    'url' => route('analytics.index'),
                ],
            ];
        }

        // Upcoming posts
        $scheduledCount = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();

        if ($scheduledCount > 0) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'calendar',
                'title' => 'Scheduled Content',
                'message' => "{$scheduledCount} posts are scheduled for the next 7 days.",
                'action' => [
                    'label' => 'Review',
                    'url' => route('social.posts.index', ['status' => 'scheduled']),
                ],
            ];
        }

        // Campaign ending soon
        $endingCampaign = \App\Models\Campaign::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addDays(5))
            ->first();

        if ($endingCampaign) {
            $insights[] = [
                'type' => 'warning',
                'icon' => 'alert',
                'title' => 'Campaign Ending Soon',
                'message' => "Campaign '{$endingCampaign->name}' ends in {$endingCampaign->end_date->diffForHumans()}.",
                'action' => [
                    'label' => 'Review Performance',
                    'url' => route('campaigns.show', $endingCampaign),
                ],
            ];
        }

        return $insights;
    }

    private function getBestPostingTime(Agency $agency): ?string
    {
        // Analyze historical engagement data
        // This is a simplified version - real implementation would use ML
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays(30))
            ->get();

        if ($posts->isEmpty()) {
            return null;
        }

        // Group by hour and find best performing
        $hourPerformance = [];
        foreach ($posts as $post) {
            $hour = $post->published_at->hour;
            $hourPerformance[$hour] = ($hourPerformance[$hour] ?? 0) + 1;
        }

        arsort($hourPerformance);
        $bestHour = array_key_first($hourPerformance);

        return date('g A', strtotime("{$bestHour}:00"));
    }

    private function getPerformanceTrend(Agency $agency): array
    {
        $lastWeek = SocialPost::where('agency_id', $agency->id)
            ->where('published_at', '>=', now()->subDays(7))
            ->count();

        $previousWeek = SocialPost::where('agency_id', $agency->id)
            ->where('published_at', '>=', now()->subDays(14))
            ->where('published_at', '<', now()->subDays(7))
            ->count();

        if ($previousWeek === 0) {
            return ['direction' => 'neutral', 'percentage' => 0, 'platform' => ''];
        }

        $change = round((($lastWeek - $previousWeek) / $previousWeek) * 100);

        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
            'percentage' => abs($change),
            'platform' => 'Overall',
        ];
    }
}
```

---

## Implementation Timeline

| Phase | Timeline | Deliverables | Expected Impact |
|-------|----------|--------------|-----------------|
| **Phase 1** | Months 1-2 | Free tier optimization, usage-based AI pricing | +40% conversion, +25% ARPU |
| **Phase 2** | Months 2-4 | Client-facing dashboards, white-label | +30% Enterprise tier |
| **Phase 3** | Month 3 | Referral program | +20% signups |
| **Phase 4** | Month 4 | Metrics tracking | Data-driven decisions |
| **Phase 5** | Months 4-6 | UX improvements, AI insights | +15% retention |

---

## Revenue Impact Estimate

| Initiative | Current | Projected | Impact |
|------------|---------|-----------|--------|
| MRR | $5,000 | $15,000 | +200% |
| Free-to-paid conversion | 2% | 8% | +400% |
| ARPU | $45 | $75 | +67% |
| Churn rate | 8%/mo | 4%/mo | -50% |
| LTV | $540 | $1,800 | +233% |

---

## Next Steps

1. **Immediate (Week 1):** Run migrations, update plan limits, test quota enforcement
2. **Week 2-3:** Implement AI credit purchase flow with Stripe
3. **Week 4-6:** Build client report generation and white-label system
4. **Week 7-8:** Launch referral program with tracking
5. **Week 9-12:** Deploy metrics dashboard and AI insights

---

**Ready to implement any of these phases. Which one should I start building first?**
