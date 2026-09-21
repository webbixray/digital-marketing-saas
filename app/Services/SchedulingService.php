<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\OptimalPostingTime;
use App\Models\SocialPost;

class SchedulingService
{
    /**
     * Calculate optimal posting times based on agency's historical data
     */
    public function calculateOptimalTimes(Agency $agency, string $platform = 'all'): array
    {
        // Get posts with engagement data
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->when($platform !== 'all', fn($q) => $q->where('platform', $platform))
            ->get();

        if ($posts->isEmpty()) {
            return $this->getDefaultOptimalTimes();
        }

        // Group by day/hour and calculate average engagement
        $scores = [];
        foreach ($posts as $post) {
            $day = $post->published_at->dayOfWeek;
            $hour = $post->published_at->hour;
            $engagement = $post->engagement_score ?? $this->estimateEngagement($post);

            $key = "{$day}_{$hour}";
            if (!isset($scores[$key])) {
                $scores[$key] = ['total' => 0, 'count' => 0, 'day' => $day, 'hour' => $hour];
            }
            $scores[$key]['total'] += $engagement;
            $scores[$key]['count']++;
        }

        // Calculate averages and sort
        $optimalTimes = [];
        foreach ($scores as $key => $data) {
            $optimalTimes[] = [
                'day_of_week' => $data['day'],
                'hour' => $data['hour'],
                'engagement_score' => round($data['total'] / $data['count'], 2),
                'sample_size' => $data['count'],
            ];
        }

        // Sort by engagement score descending
        usort($optimalTimes, fn($a, $b) => $b['engagement_score'] <=> $a['engagement_score']);

        return array_slice($optimalTimes, 0, 20);
    }

    /**
     * Get best posting times for a specific platform
     */
    public function getBestTimes(Agency $agency, string $platform, int $limit = 5): array
    {
        return OptimalPostingTime::where('agency_id', $agency->id)
            ->forPlatform($platform)
            ->best()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Store calculated optimal times
     */
    public function storeOptimalTimes(Agency $agency, array $times): void
    {
        // Clear existing times for agency
        OptimalPostingTime::where('agency_id', $agency->id)->delete();

        // Insert new times
        foreach ($times as $time) {
            OptimalPostingTime::create([
                'agency_id' => $agency->id,
                'platform' => $time['platform'] ?? 'all',
                'day_of_week' => $time['day_of_week'],
                'hour' => $time['hour'],
                'engagement_score' => $time['engagement_score'],
                'sample_size' => $time['sample_size'] ?? 0,
            ]);
        }
    }

    /**
     * Get recommended time slots for the next 7 days
     */
    public function getRecommendedSlots(Agency $agency, string $platform = 'all', int $slots = 7): array
    {
        $recommendations = [];
        $now = now();

        for ($i = 0; $i < 7; $i++) {
            $date = $now->copy()->addDays($i);
            $dayOfWeek = $date->dayOfWeek;

            $bestHours = OptimalPostingTime::where('agency_id', $agency->id)
                ->forPlatform($platform)
                ->forDay($dayOfWeek)
                ->best()
                ->limit(3)
                ->get();

            if ($bestHours->isNotEmpty()) {
                $recommendations[] = [
                    'date' => $date->toDateString(),
                    'day_name' => $date->format('l'),
                    'slots' => $bestHours->map(fn($t) => [
                        'hour' => $t->hour,
                        'score' => $t->engagement_score,
                        'time' => sprintf('%02d:00', $t->hour),
                    ])->toArray(),
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get default optimal times when no historical data exists
     */
    private function getDefaultOptimalTimes(): array
    {
        // Industry averages based on social media research
        return [
            ['day_of_week' => 1, 'hour' => 9, 'engagement_score' => 7.5],
            ['day_of_week' => 1, 'hour' => 12, 'engagement_score' => 8.2],
            ['day_of_week' => 1, 'hour' => 17, 'engagement_score' => 7.8],
            ['day_of_week' => 2, 'hour' => 8, 'engagement_score' => 7.2],
            ['day_of_week' => 2, 'hour' => 12, 'engagement_score' => 8.5],
            ['day_of_week' => 2, 'hour' => 16, 'engagement_score' => 7.9],
            ['day_of_week' => 3, 'hour' => 9, 'engagement_score' => 7.6],
            ['day_of_week' => 3, 'hour' => 13, 'engagement_score' => 8.1],
            ['day_of_week' => 3, 'hour' => 18, 'engagement_score' => 7.4],
            ['day_of_week' => 4, 'hour' => 8, 'engagement_score' => 7.3],
            ['day_of_week' => 4, 'hour' => 12, 'engagement_score' => 8.4],
            ['day_of_week' => 4, 'hour' => 17, 'engagement_score' => 7.7],
            ['day_of_week' => 5, 'hour' => 9, 'engagement_score' => 7.8],
            ['day_of_week' => 5, 'hour' => 12, 'engagement_score' => 8.0],
            ['day_of_week' => 5, 'hour' => 15, 'engagement_score' => 7.5],
            ['day_of_week' => 6, 'hour' => 10, 'engagement_score' => 6.8],
            ['day_of_week' => 6, 'hour' => 14, 'engagement_score' => 7.1],
            ['day_of_week' => 0, 'hour' => 11, 'engagement_score' => 6.5],
            ['day_of_week' => 0, 'hour' => 15, 'engagement_score' => 6.9],
        ];
    }

    /**
     * Estimate engagement for a post without metrics
     */
    private function estimateEngagement(SocialPost $post): float
    {
        // Base score from platform averages
        $platformScores = [
            'instagram' => 7.5,
            'facebook' => 6.8,
            'twitter' => 6.5,
            'linkedin' => 7.0,
            'tiktok' => 8.0,
            'pinterest' => 6.2,
        ];

        $base = $platformScores[$post->platform] ?? 6.0;

        // Adjust for content length (longer posts tend to perform better)
        $lengthBonus = min(strlen($post->content ?? '') / 100, 2.0);

        // Adjust for media presence
        $mediaBonus = $post->media_url ? 1.5 : 0;

        return min($base + $lengthBonus + $mediaBonus, 10.0);
    }
}
