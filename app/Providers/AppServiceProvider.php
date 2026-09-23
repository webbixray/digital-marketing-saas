<?php

namespace App\Providers;

use App\Models\AiContentLog;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\WebhookLog;
use App\View\Composers\PermissionsComposer;
use App\Observers\AiContentLogObserver;
use App\Observers\CampaignObserver;
use App\Observers\ClientObserver;
use App\Observers\InvoiceObserver;
use App\Observers\SocialAccountObserver;
use App\Observers\SocialCommentObserver;
use App\Observers\SocialPostObserver;
use App\Observers\UserObserver;
use App\Observers\WebhookLogObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // -----------------------------------------------------------------------
        // Observers - auto-register model observers
        // -----------------------------------------------------------------------
        SocialPost::observe(SocialPostObserver::class);
        Client::observe(ClientObserver::class);
        Campaign::observe(CampaignObserver::class);
        AiContentLog::observe(AiContentLogObserver::class);
        Invoice::observe(InvoiceObserver::class);
        User::observe(UserObserver::class);
        SocialAccount::observe(SocialAccountObserver::class);
        SocialComment::observe(SocialCommentObserver::class);
        WebhookLog::observe(WebhookLogObserver::class);

        // View Composer — share roles and permission categories with all views
        View::composer('*', PermissionsComposer::class);

        // -----------------------------------------------------------------------
        // Rate Limiting Configuration
        // -----------------------------------------------------------------------

        // Global API rate limiter: 120 requests per minute per IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Login rate limiter: 5 attempts per minute per email/username
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email') ?? $request->ip();

            return Limit::perMinute(5)->by($email)->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again later.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], 429);
            });
        });

        // Global web rate limiter: 60 requests per minute per IP
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Upload rate limiter: 10 uploads per minute per user ID
        RateLimiter::for('upload', function (Request $request) {
            $userId = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(10)->by($userId);
        });

        // Social post publishing rate limiter: 100 posts per hour per agency
        RateLimiter::for('publish', function (Request $request) {
            $agencyId = $request->user()?->agency_id ?? $request->ip();

            return Limit::perHour(100)->by($agencyId);
        });

        // Email campaign sending rate limiter: 5 campaigns per hour per agency
        RateLimiter::for('email_send', function (Request $request) {
            $agencyId = $request->user()?->agency_id ?? $request->ip();

            return Limit::perHour(5)->by($agencyId);
        });

        // AI content generation rate limiter: 50 generations per hour per agency
        RateLimiter::for('ai_generate', function (Request $request) {
            $agencyId = $request->user()?->agency_id ?? $request->ip();

            return Limit::perHour(50)->by($agencyId);
        });
    }
}
