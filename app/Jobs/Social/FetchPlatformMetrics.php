<?php

namespace App\Jobs\Social;

use App\Models\SocialPost;
use App\Services\Social\SocialPostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchPlatformMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    public function __construct(public SocialPost $post) {}

    public function handle(SocialPostService $service): void
    {
        try {
            $stats = $service->getPostStats($this->post);
            $this->post->update([
                'views_count' => $stats['views'],
                'likes_count' => $stats['likes'],
                'comments_count' => $stats['comments'],
                'shares_count' => $stats['shares'],
                'clicks_count' => $stats['clicks'],
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to fetch metrics for post #{$this->post->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
