<?php

namespace App\Services\Calendar;

use App\Models\SocialPost;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class ContentCalendarService
{
    /**
     * Get calendar events for a date range.
     */
    public function getEvents(int $agencyId, string $startDate, string $endDate): Collection
    {
        return SocialPost::where('agency_id', $agencyId)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with('socialAccount')
            ->get()
            ->map(fn (SocialPost $post) => [
                'id' => $post->id,
                'title' => \Illuminate\Support\Str::limit($post->content, 50),
                'content' => $post->content,
                'start' => $post->scheduled_at?->toISOString(),
                'end' => $post->scheduled_at?->addHour()->toISOString(),
                'platform' => $post->platform,
                'status' => $post->status,
                'color' => $this->getPlatformColor($post->platform),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'platform' => $post->platform,
                    'status' => $post->status,
                    'account' => $post->socialAccount?->platform_username,
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
     * Get best posting times based on historical data.
     */
    public function getBestPostingTimes(int $agencyId): array
    {
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
            'score' => $hourlyPerformance[$hour] ?? 0,
        ], $bestHours);
    }

    /**
     * Get platform color for calendar display.
     */
    private function getPlatformColor(string $platform): string
    {
        return match ($platform) {
            'facebook' => '#1877f2',
            'instagram' => '#e4405f',
            'twitter' => '#1da1f2',
            'linkedin' => '#0077b5',
            'tiktok' => '#000000',
            'pinterest' => '#bd081c',
            default: '#6c757d',
        };
    }
}
