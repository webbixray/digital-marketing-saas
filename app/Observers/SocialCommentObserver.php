<?php

namespace App\Observers;

use App\Models\SocialComment;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Support\Facades\Log;

class SocialCommentObserver
{
    public function created(SocialComment $comment): void
    {
        $agency = $comment->agency;
        if ($agency) {
            try {
                app(AnalyticsService::class)->clearCache($agency);
            } catch (\Throwable $e) {
                Log::warning('SocialCommentObserver: Failed to clear cache', ['error' => $e->getMessage()]);
            }
        }
    }

    public function deleted(SocialComment $comment): void
    {
        $agency = $comment->agency;
        if ($agency) {
            try {
                app(AnalyticsService::class)->clearCache($agency);
            } catch (\Throwable $e) {
                Log::warning('SocialCommentObserver: Failed to clear cache', ['error' => $e->getMessage()]);
            }
        }
    }
}
