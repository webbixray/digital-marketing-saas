<?php

namespace App\Services;

use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SmartSchedulingService
{
    private const PLATFORM_DEFAULTS = [
        'facebook' => [9, 12, 15],
        'instagram' => [11, 14, 18],
        'twitter' => [8, 12, 17],
        'linkedin' => [8, 12, 17],
        'tiktok' => [12, 16, 20],
        'pinterest' => [14, 18, 21],
    ];

    private const MIN_HISTORICAL_POSTS = 5;

    public function getOptimalTimes(int $agencyId, string $platform): array
    {
        $publishedCount = SocialPost::where('agency_id', $agencyId)
            ->where('platform', $platform)
            ->where('status', 'published')
            ->count();

        if ($publishedCount >= self::MIN_HISTORICAL_POSTS) {
            return $this->analyzeHistoricalData($agencyId, $platform);
        }

        return self::PLATFORM_DEFAULTS[$platform] ?? [9, 12, 17];
    }

    private function analyzeHistoricalData(int $agencyId, string $platform): array
    {
        $posts = SocialPost::where('agency_id', $agencyId)
            ->where('platform', $platform)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->orderBy('published_at', 'desc')
            ->limit(50)
            ->get();

        $hourlyEngagement = [];

        foreach ($posts as $post) {
            $hour = $post->published_at->hour;
            $engagement = ($post->likes_count ?? 0) + ($post->comments_count ?? 0) + ($post->shares_count ?? 0);

            if (! isset($hourlyEngagement[$hour])) {
                $hourlyEngagement[$hour] = ['total' => 0, 'count' => 0];
            }

            $hourlyEngagement[$hour]['total'] += $engagement;
            $hourlyEngagement[$hour]['count']++;
        }

        $averages = [];
        foreach ($hourlyEngagement as $hour => $data) {
            $averages[$hour] = $data['count'] > 0 ? $data['total'] / $data['count'] : 0;
        }

        arsort($averages);
        $topHours = array_slice(array_keys($averages), 0, 3);
        sort($topHours);

        return ! empty($topHours) ? $topHours : (self::PLATFORM_DEFAULTS[$platform] ?? [9, 12, 17]);
    }

    public function getNextOptimalTime(int $agencyId, string $platform): Carbon
    {
        $optimalHours = $this->getOptimalTimes($agencyId, $platform);
        $now = Carbon::now();

        foreach ($optimalHours as $hour) {
            $candidate = $now->copy()->setHour($hour)->setMinute(0)->setSecond(0);
            if ($candidate->isFuture()) {
                return $candidate;
            }
        }

        return $now->copy()->addDay()->setHour($optimalHours[0])->setMinute(0)->setSecond(0);
    }

    public function scheduleAtOptimalTime(SocialPost $post): Carbon
    {
        $optimalTime = $this->getNextOptimalTime($post->agency_id, $post->platform);

        $post->update([
            'scheduled_at' => $optimalTime,
            'status' => 'scheduled',
        ]);

        Log::info('Post scheduled at optimal time', [
            'post_id' => $post->id,
            'scheduled_at' => $optimalTime->toDateTimeString(),
        ]);

        return $optimalTime;
    }

    public function getRecommendation(int $agencyId, string $platform): array
    {
        return [
            'optimal_hours' => $this->getOptimalTimes($agencyId, $platform),
            'next_optimal_time' => $this->getNextOptimalTime($agencyId, $platform)->toDateTimeString(),
            'platform' => $platform,
        ];
    }
}
