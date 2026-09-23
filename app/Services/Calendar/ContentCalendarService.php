<?php

namespace App\Services\Calendar;

use App\Models\SocialPost;
use App\Models\OptimalPostingTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ContentCalendarService
{
    /**
     * Get calendar events for a date range with optional filters.
     */
    public function getEvents(
        int $agencyId,
        string $startDate,
        string $endDate,
        ?string $platform = null,
        ?int $accountId = null
    ): Collection {
        $cacheKey = "calendar:{$agencyId}:events:" . md5("{$startDate}:{$endDate}:{$platform}:{$accountId}");
        
        return Cache::remember($cacheKey, 300, function () use ($agencyId, $startDate, $endDate, $platform, $accountId) {
            $query = SocialPost::where('agency_id', $agencyId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->with(['socialAccount:id,platform,platform_username,platform_display_name']);

            if ($platform) {
                $query->where('platform', $platform);
            }

            if ($accountId) {
                $query->where('social_account_id', $accountId);
            }

            return $query->get()
                ->map(fn (SocialPost $post) => [
                    'id' => $post->id,
                    'title' => Str::limit($post->content, 50),
                    'content' => $post->content,
                    'start' => $post->scheduled_at?->toISOString(),
                    'end' => $post->scheduled_at?->copy()->addHour()->toISOString(),
                    'platform' => $post->platform,
                    'status' => $post->status,
                    'color' => $this->getStatusColor($post->status),
                    'textColor' => '#ffffff',
                    'editable' => in_array($post->status, ['draft', 'scheduled']),
                    'extendedProps' => [
                        'platform' => $post->platform,
                        'status' => $post->status,
                        'account' => $post->socialAccount?->platform_username,
                        'account_name' => $post->socialAccount?->platform_display_name,
                        'social_account_id' => $post->social_account_id,
                        'scheduled_at' => $post->scheduled_at?->toDateTimeString(),
                        'edit_url' => route('social.posts.edit', $post->id),
                    ],
                ]);
        });
    }

    /**
     * Get events grouped by day for a month view.
     */
    public function getMonthEvents(int $agencyId, int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $this->getEvents($agencyId, $start->toDateTimeString(), $end->toDateTimeString());
    }

    /**
     * Get posting statistics for a date range - optimized with single query.
     */
    public function getStats(int $agencyId, string $startDate, string $endDate): array
    {
        $cacheKey = "calendar:{$agencyId}:stats:" . md5("{$startDate}:{$endDate}");
        
        return Cache::remember($cacheKey, 600, function () use ($agencyId, $startDate, $endDate) {
            $stats = SocialPost::where('agency_id', $agencyId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published,
                    SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
                ')
                ->first();

            $byPlatform = SocialPost::where('agency_id', $agencyId)
                ->whereBetween('scheduled_at', [$startDate, $endDate])
                ->selectRaw('platform, COUNT(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform')
                ->toArray();

            return [
                'total' => (int) $stats->total,
                'published' => (int) $stats->published,
                'scheduled' => (int) $stats->scheduled,
                'draft' => (int) $stats->draft,
                'failed' => (int) $stats->failed,
                'by_platform' => $byPlatform,
            ];
        });
    }

    /**
     * Get best posting times from the OptimalPostingTime model.
     * Falls back to historical post analysis when no OptimalPostingTime records exist.
     */
    public function getBestPostingTimes(int $agencyId): array
    {
        return Cache::remember("calendar:{$agencyId}:best_times", 3600, function () use ($agencyId) {
            return $this->computeBestPostingTimes($agencyId);
        });
    }

    /**
     * Compute best posting times - cache warming support.
     */
    public function computeBestPostingTimes(int $agencyId): array
    {
        $optimalTimes = OptimalPostingTime::where('agency_id', $agencyId)
            ->best()
            ->take(3)
            ->get();

        if ($optimalTimes->isNotEmpty()) {
            return $optimalTimes->map(fn (OptimalPostingTime $slot) => [
                'hour' => $slot->hour,
                'label' => $slot->time_slot,
                'day' => $slot->day_name,
                'score' => $slot->engagement_score,
                'platform' => $slot->platform,
            ])->toArray();
        }

        // Fallback: compute from historical published posts - optimized with single query
        $hourlyPerformance = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->selectRaw('
                strftime("%H", published_at) as hour,
                SUM(likes_count + comments_count + shares_count) as total_engagement
            ')
            ->groupBy('hour')
            ->orderByDesc('total_engagement')
            ->get();

        $bestHours = $hourlyPerformance->take(3)->pluck('hour')->toArray();

        return array_map(fn ($hour) => [
            'hour' => (int) $hour,
            'label' => sprintf('%02d:00', $hour),
            'day' => null,
            'score' => (int) ($hourlyPerformance->firstWhere('hour', $hour)->total_engagement ?? 0),
            'platform' => null,
        ], $bestHours);
    }

    /**
     * Warm cache for an agency - pre-computes all expensive queries.
     */
    public function warmCache(int $agencyId): void
    {
        // Pre-compute best posting times
        $bestTimes = $this->computeBestPostingTimes($agencyId);
        Cache::put("calendar:{$agencyId}:best_times", $bestTimes, 3600);

        // Pre-compute current month stats
        $start = Carbon::now()->startOfMonth()->toDateTimeString();
        $end = Carbon::now()->endOfMonth()->toDateTimeString();
        
        $stats = SocialPost::where('agency_id', $agencyId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        $byPlatform = SocialPost::where('agency_id', $agencyId)
            ->whereBetween('scheduled_at', [$start, $end])
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        Cache::put("calendar:{$agencyId}:stats:" . md5("{$start}:{$end}"), [
            'total' => (int) $stats->total,
            'published' => (int) $stats->published,
            'scheduled' => (int) $stats->scheduled,
            'draft' => (int) $stats->draft,
            'failed' => (int) $stats->failed,
            'by_platform' => $byPlatform,
        ], 600);

        // Pre-compute next month as well
        $nextStart = Carbon::now()->addMonth()->startOfMonth()->toDateTimeString();
        $nextEnd = Carbon::now()->addMonth()->endOfMonth()->toDateTimeString();
        
        $nextStats = SocialPost::where('agency_id', $agencyId)
            ->whereBetween('scheduled_at', [$nextStart, $nextEnd])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = "scheduled" THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        Cache::put("calendar:{$agencyId}:stats:" . md5("{$nextStart}:{$nextEnd}"), [
            'total' => (int) $nextStats->total,
            'published' => (int) $nextStats->published,
            'scheduled' => (int) $nextStats->scheduled,
            'draft' => (int) $nextStats->draft,
            'failed' => (int) $nextStats->failed,
            'by_platform' => $byPlatform,
        ], 600);
    }

    /**
     * Clear all cached calendar data for an agency.
     */
    public function clearCache(int $agencyId): void
    {
        Cache::forget("calendar:{$agencyId}:best_times");
        // Note: For file cache, we can't easily iterate by pattern.
        // In production, use Redis/Memcached for better cache management.
    }

    /**
     * Get status-based color for calendar display.
     * draft=gray, scheduled=blue, published=green, failed=red
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => '#6b7280',      // gray-500
            'scheduled' => '#3b82f6',  // blue-500
            'published' => '#10b981',  // emerald-500
            'failed' => '#ef4444',     // red-500
            'in_queue' => '#8b5cf6',   // violet-500
            'publishing' => '#f59e0b', // amber-500
            'cancelled' => '#6b7280',  // gray-500
            default => '#6c757d',
        };
    }

    /**
     * Get platform color for badge display.
     */
    public function getPlatformColor(string $platform): string
    {
        return match ($platform) {
            'facebook' => '#1877f2',
            'instagram' => '#e4405f',
            'twitter' => '#1da1f2',
            'linkedin' => '#0077b5',
            'tiktok' => '#000000',
            'pinterest' => '#bd081c',
            default => '#6c757d',
        };
    }

    /**
     * Suggest optimal posting slots for a given platform using OptimalPostingTime model.
     * Returns top 3 slots for the next 7 days.
     */
    public function suggestOptimalSlots(int $agencyId, ?string $platform = null): array
    {
        $cacheKey = "calendar:{$agencyId}:slots:" . ($platform ?? 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($agencyId, $platform) {
            $query = OptimalPostingTime::where('agency_id', $agencyId)->best();

            if ($platform) {
                $query->forPlatform($platform);
            }

            $slots = $query->take(5)->get();

            return $slots->map(fn (OptimalPostingTime $slot) => [
                'day_of_week' => $slot->day_of_week,
                'day_name' => $slot->day_name,
                'hour' => $slot->hour,
                'time_slot' => $slot->time_slot,
                'engagement_score' => $slot->engagement_score,
                'platform' => $slot->platform,
            ])->toArray();
        });
    }
}
