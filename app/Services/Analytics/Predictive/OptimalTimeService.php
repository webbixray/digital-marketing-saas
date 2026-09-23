<?php

namespace App\Services\Analytics\Predictive;

use App\Models\SocialPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimalTimeService
{
    private const CACHE_TTL = 600;

    private const PLATFORMS = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'];

    public function getBestPostingTimes(int $agencyId, ?string $platform = null): array
    {
        $platforms = $platform ? [$platform] : self::PLATFORMS;
        $results = [];

        foreach ($platforms as $p) {
            $results[$p] = $this->getBestTimesForPlatform($agencyId, $p);
        }

        if ($platform) {
            return $results[$platform];
        }

        return $results;
    }

    public function getEngagementHeatmap(int $agencyId, string $platform): array
    {
        return Cache::remember("optimal:heatmap:{$agencyId}:{$platform}", self::CACHE_TTL, function () use ($agencyId, $platform) {
            $heatmap = [];
            $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

            foreach ($days as $dayIndex => $dayName) {
                $heatmap[$dayName] = array_fill(0, 24, 0);
            }

            $driver = DB::connection()->getDriverName();
            $hourExpr = $driver === 'sqlite' ? "strftime('%H', published_at)" : 'HOUR(published_at)';
            $dayOfWeekExpr = $driver === 'sqlite' ? "strftime('%w', published_at)" : 'DAYOFWEEK(published_at)';

            $data = SocialPost::where('agency_id', $agencyId)
                ->where('platform', $platform)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays(90))
                ->selectRaw("
                    {$dayOfWeekExpr} as day_of_week,
                    {$hourExpr} as hour,
                    AVG(engagement_rate) as avg_engagement
                ")
                ->groupBy('day_of_week', 'hour')
                ->get();

            // SQLite strftime('%w') returns 0=Sunday...6=Saturday
            // MySQL DAYOFWEEK returns 1=Sunday...7=Saturday
            $dayMap = $driver === 'sqlite'
                ? [
                    0 => 'Sun',
                    1 => 'Mon',
                    2 => 'Tue',
                    3 => 'Wed',
                    4 => 'Thu',
                    5 => 'Fri',
                    6 => 'Sat',
                ]
                : [
                    1 => 'Sun',
                    2 => 'Mon',
                    3 => 'Tue',
                    4 => 'Wed',
                    5 => 'Thu',
                    6 => 'Fri',
                    7 => 'Sat',
                ];

            foreach ($data as $row) {
                $dayKey = (int) $row->day_of_week;
                $dayName = $dayMap[$dayKey] ?? 'Mon';
                $heatmap[$dayName][(int) $row->hour] = round((float) $row->avg_engagement, 2);
            }

            $peakDay = '';
            $peakHour = 0;
            $peakValue = 0;

            foreach ($heatmap as $dayName => $hours) {
                foreach ($hours as $hour => $value) {
                    if ($value > $peakValue) {
                        $peakValue = $value;
                        $peakDay = $dayName;
                        $peakHour = $hour;
                    }
                }
            }

            return [
                'platform' => $platform,
                'heatmap' => $heatmap,
                'peak_day' => $peakDay,
                'peak_hour' => $peakHour,
                'peak_engagement' => $peakValue,
                'formatted_peak' => "{$peakDay} " . sprintf('%02d:00', $peakHour),
            ];
        });
    }

    public function predictBestTime(int $agencyId, string $platform, string $contentType): array
    {
        return Cache::remember("optimal:predict:{$agencyId}:{$platform}:{$contentType}", self::CACHE_TTL, function () use ($agencyId, $platform, $contentType) {
            $driver = DB::connection()->getDriverName();
            $hourExpr = $driver === 'sqlite' ? "strftime('%H', published_at)" : 'HOUR(published_at)';

            $posts = SocialPost::where('agency_id', $agencyId)
                ->where('platform', $platform)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays(90))
                ->where(function ($query) use ($contentType) {
                    $query->where('content', 'LIKE', "%{$contentType}%")
                        ->orWhereJsonContains('tags', $contentType);
                })
                ->selectRaw("
                    {$hourExpr} as hour,
                    AVG(engagement_rate) as avg_engagement,
                    COUNT(*) as post_count
                ")
                ->groupBy('hour')
                ->orderByDesc('avg_engagement')
                ->get();

            $bestHour = $posts->first();
            $topHours = $posts->take(3)->map(fn ($p) => [
                'hour' => (int) $p->hour,
                'avg_engagement' => round((float) $p->avg_engagement, 2),
                'sample_size' => (int) $p->post_count,
            ])->toArray();

            return [
                'platform' => $platform,
                'content_type' => $contentType,
                'best_hour' => $bestHour ? (int) $bestHour->hour : null,
                'best_engagement' => $bestHour ? round((float) $bestHour->avg_engagement, 2) : 0,
                'top_hours' => $topHours,
                'confidence' => $this->calculateTimeConfidence($posts->count(), $bestHour->post_count ?? 0),
            ];
        });
    }

    public function getAudienceActivity(int $agencyId, string $platform): array
    {
        return Cache::remember("optimal:activity:{$agencyId}:{$platform}", self::CACHE_TTL, function () use ($agencyId, $platform) {
            $driver = DB::connection()->getDriverName();
            $hourExpr = $driver === 'sqlite' ? "strftime('%H', published_at)" : 'HOUR(published_at)';

            $hourlyActivity = SocialPost::where('agency_id', $agencyId)
                ->where('platform', $platform)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays(30))
                ->selectRaw("
                    {$hourExpr} as hour,
                    SUM(views_count) as total_impressions,
                    AVG(likes_count) as avg_likes,
                    AVG(comments_count) as avg_comments,
                    AVG(shares_count) as avg_shares,
                    COUNT(*) as post_count
                ")
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->map(fn ($row) => [
                    'hour' => (int) $row->hour,
                    'hour_formatted' => sprintf('%02d:00', $row->hour),
                    'total_impressions' => (int) $row->total_impressions,
                    'avg_likes' => round((float) ($row->avg_likes ?? 0), 1),
                    'avg_comments' => round((float) ($row->avg_comments ?? 0), 1),
                    'avg_shares' => round((float) ($row->avg_shares ?? 0), 1),
                    'post_count' => (int) $row->post_count,
                    'activity_score' => $this->calculateActivityScore($row),
                ])
                ->toArray();

            $sorted = collect($hourlyActivity)->sortByDesc('activity_score');
            $peakHours = $sorted->take(3)->values()->toArray();

            return [
                'platform' => $platform,
                'hourly_activity' => $hourlyActivity,
                'peak_hours' => $peakHours,
                'audience_size_estimate' => array_sum(array_column($hourlyActivity, 'total_impressions')),
            ];
        });
    }

    private function getBestTimesForPlatform(int $agencyId, string $platform): array
    {
        return Cache::remember("optimal:best:{$agencyId}:{$platform}", self::CACHE_TTL, function () use ($agencyId, $platform) {
            $driver = DB::connection()->getDriverName();
            $hourExpr = $driver === 'sqlite' ? "strftime('%H', published_at)" : 'HOUR(published_at)';

            $bestTimes = SocialPost::where('agency_id', $agencyId)
                ->where('platform', $platform)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays(60))
                ->selectRaw("
                    {$hourExpr} as hour,
                    AVG(engagement_rate) as avg_engagement,
                    COUNT(*) as post_count
                ")
                ->groupBy('hour')
                ->orderByDesc('avg_engagement')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'hour' => (int) $row->hour,
                    'formatted' => sprintf('%02d:00', $row->hour),
                    'avg_engagement' => round((float) $row->avg_engagement, 2),
                    'post_count' => (int) $row->post_count,
                ])
                ->toArray();

            return [
                'platform' => $platform,
                'best_times' => $bestTimes,
                'top_hour' => $bestTimes[0]['hour'] ?? null,
                'top_engagement' => $bestTimes[0]['avg_engagement'] ?? 0,
            ];
        });
    }

    private function calculateActivityScore($row): float
    {
        $impressions = (int) ($row->total_impressions ?? 0);
        $likes = (float) ($row->avg_likes ?? 0);
        $comments = (float) ($row->avg_comments ?? 0);
        $shares = (float) ($row->avg_shares ?? 0);

        return round($impressions * 0.5 + $likes * 2 + $comments * 3 + $shares * 4, 2);
    }

    private function calculateTimeConfidence(int $uniqueHours, int $sampleSize): float
    {
        $baseConfidence = min(0.95, $sampleSize / 100);
        $coverageBonus = min(0.1, $uniqueHours / 24);

        return round(min(1.0, $baseConfidence + $coverageBonus), 2);
    }
}
