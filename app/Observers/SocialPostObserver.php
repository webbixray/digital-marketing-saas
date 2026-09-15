<?php

namespace App\Observers;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Log;

class SocialPostObserver
{
    public function created(SocialPost $post): void
    {
        $agency = $post->agency;
        if ($agency) {
            try {
                app(\App\Services\QuotaService::class)->incrementPostCount($agency);
                app(\App\Services\Analytics\AnalyticsService::class)->clearCache($agency);
            } catch (\Throwable $e) {
                Log::warning('SocialPostObserver: Failed to update quotas', ['error' => $e->getMessage()]);
            }
        }
    }

    public function deleted(SocialPost $post): void
    {
        $agency = $post->agency;
        if ($agency) {
            try {
                app(\App\Services\QuotaService::class)->decrementPostCount($agency);
                app(\App\Services\Analytics\AnalyticsService::class)->clearCache($agency);
            } catch (\Throwable $e) {
                Log::warning('SocialPostObserver: Failed to update quotas', ['error' => $e->getMessage()]);
            }
        }
    }

    public function updated(SocialPost $post): void
    {
        $agency = $post->agency;

        if ($post->isDirty('status')) {
            $oldStatus = $post->getOriginal('status');
            $newStatus = $post->status;

            if ($oldStatus !== 'published' && $newStatus === 'published') {
                Log::info("Post #{$post->id} published");
            }
        }

        if ($post->isDirty(['status', 'engagement_rate', 'platform', 'agency_id'])) {
            if ($agency) {
                try {
                    app(\App\Services\Analytics\AnalyticsService::class)->clearCache($agency);
                } catch (\Throwable $e) {
                    Log::warning('SocialPostObserver: Failed to clear cache', ['error' => $e->getMessage()]);
                }
            }
        }
    }
}
