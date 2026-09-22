<?php

namespace App\Services\Calendar;

use App\Models\SocialPost;
use App\Models\OptimalPostingTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $query = SocialPost::where('agency_id', $agencyId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with('socialAccount');

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
     * Get posting statistics for a date range.
     */
    public function getStats(int $agencyId, string $startDate, string $endDate): array
    {
        $posts = SocialPost::where('agency_id', $agencyId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->get();

        return [
            'total' => $posts->count(),
            'published' => $posts->where('status', 'published')->count(),
            'scheduled' => $posts->where('status', 'scheduled')->count(),
            'draft' => $posts->where('status', 'draft')->count(),
            'failed' => $posts->where('status', 'failed')->count(),
            'by_platform' => $posts->groupBy('platform')->map->count(),
        ];
    }

    /**
     * Get best posting times from the OptimalPostingTime model.
     * Falls back to historical post analysis when no OptimalPostingTime records exist.
     */
    public function getBestPostingTimes(int $agencyId): array
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

        // Fallback: compute from historical published posts
        $posts = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->get();

        $hourlyPerformance = [];
        foreach ($posts as $post) {
            $hour = $post->published_at->hour;
            $engagement = $post->likes_count + $post->comments_count + $post->shares_count;
            $hourlyPerformance[$hour] = ($hourlyPerformance[$hour] ?? 0) + $engagement;
        }

        arsort($hourlyPerformance);
        $bestHours = array_slice(array_keys($hourlyPerformance), 0, 3);

        return array_map(fn ($hour) => [
            'hour' => $hour,
            'label' => sprintf('%02d:00', $hour),
            'day' => null,
            'score' => $hourlyPerformance[$hour] ?? 0,
            'platform' => null,
        ], $bestHours);
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
    }
}
