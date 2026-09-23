<?php

namespace App\Services\Analytics\Predictive;

use App\Models\SocialListening;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TrendDetectionService
{
    private const CACHE_TTL = 600;

    public function detectTrends(int $agencyId, int $days = 30): array
    {
        return Cache::remember("trends:detect:{$agencyId}:{$days}", self::CACHE_TTL, function () use ($agencyId, $days) {
            $topics = $this->getEmergingTopics($agencyId, 20);
            $hashtags = $this->getHashtagTrends($agencyId);
            $velocity = $this->calculateTrendVelocity($agencyId, $days);

            $trending = [];

            foreach ($topics as $topic) {
                if ($topic['growth_rate'] > 0.2) {
                    $trending[] = [
                        'type' => 'topic',
                        'name' => $topic['topic'],
                        'growth_rate' => $topic['growth_rate'],
                        'volume_change' => $topic['growth_rate'] >= 1 ? 'exploding' : 'rising',
                        'confidence' => $topic['confidence'],
                    ];
                }
            }

            foreach ($hashtags as $hashtag) {
                if ($hashtag['growth_rate'] > 0.15) {
                    $trending[] = [
                        'type' => 'hashtag',
                        'name' => $hashtag['hashtag'],
                        'growth_rate' => $hashtag['growth_rate'],
                        'volume_change' => $hashtag['growth_rate'] >= 0.8 ? 'exploding' : 'rising',
                        'confidence' => $hashtag['confidence'],
                    ];
                }
            }

            usort($trending, fn ($a, $b) => $b['growth_rate'] <=> $a['growth_rate']);

            return [
                'period_days' => $days,
                'trending' => array_slice($trending, 0, 15),
                'overall_velocity' => $velocity,
                'detected_at' => now()->toDateTimeString(),
            ];
        });
    }

    public function getEmergingTopics(int $agencyId, int $limit = 10): array
    {
        return Cache::remember("trends:topics:{$agencyId}:{$limit}", self::CACHE_TTL, function () use ($agencyId, $limit) {
            $currentPeriod = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays(14))
                ->whereNotNull('tags')
                ->pluck('tags');

            $previousPeriod = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->whereBetween('published_at', [now()->subDays(28), now()->subDays(14)])
                ->whereNotNull('tags')
                ->pluck('tags');

            $currentTopics = $this->extractAndCount($currentPeriod);
            $previousTopics = $this->extractAndCount($previousPeriod);

            $emerging = [];

            foreach ($currentTopics as $topic => $currentCount) {
                $previousCount = $previousTopics[$topic] ?? 0;
                $growthRate = $previousCount > 0
                    ? round(($currentCount - $previousCount) / $previousCount, 4)
                    : ($currentCount > 0 ? 1.0 : 0.0);

                $emerging[] = [
                    'topic' => $topic,
                    'current_count' => $currentCount,
                    'previous_count' => $previousCount,
                    'growth_rate' => $growthRate,
                    'confidence' => $this->calculateTopicConfidence($currentCount, $previousCount),
                    'trend_direction' => $growthRate > 0 ? 'rising' : ($growthRate < 0 ? 'falling' : 'stable'),
                ];
            }

            usort($emerging, fn ($a, $b) => $b['growth_rate'] <=> $a['growth_rate']);

            return array_slice($emerging, 0, $limit);
        });
    }

    public function getHashtagTrends(int $agencyId): array
    {
        return Cache::remember("trends:hashtags:{$agencyId}", self::CACHE_TTL, function () use ($agencyId) {
            $currentPeriod = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays(14))
                ->whereNotNull('hashtags')
                ->pluck('hashtags');

            $previousPeriod = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->whereBetween('published_at', [now()->subDays(28), now()->subDays(14)])
                ->whereNotNull('hashtags')
                ->pluck('hashtags');

            $currentHashtags = $this->extractAndCount($currentPeriod);
            $previousHashtags = $this->extractAndCount($previousPeriod);

            $trends = [];

            foreach ($currentHashtags as $hashtag => $currentCount) {
                $previousCount = $previousHashtags[$hashtag] ?? 0;
                $growthRate = $previousCount > 0
                    ? round(($currentCount - $previousCount) / $previousCount, 4)
                    : ($currentCount > 2 ? 0.5 : 0.0);

                $trends[] = [
                    'hashtag' => $hashtag,
                    'current_usage' => $currentCount,
                    'previous_usage' => $previousCount,
                    'growth_rate' => $growthRate,
                    'confidence' => $this->calculateTopicConfidence($currentCount, $previousCount),
                ];
            }

            usort($trends, fn ($a, $b) => $b['growth_rate'] <=> $a['growth_rate']);

            return $trends;
        });
    }

    public function getCompetitorTrends(int $agencyId): array
    {
        return Cache::remember("trends:competitor:{$agencyId}", self::CACHE_TTL, function () use ($agencyId) {
            $listenings = SocialListening::where('agency_id', $agencyId)
                ->where('created_at', '>=', now()->subDays(14))
                ->get();

            $trends = [];

            foreach ($listenings as $listening) {
                $mentions = $listening->match_count ?? 0;
                $sentiment = $listening->sentiment_score;

                $trends[] = [
                    'keyword' => $listening->keyword,
                    'platform' => $listening->platform,
                    'mentions' => $mentions,
                    'sentiment_score' => $sentiment,
                    'opportunity_score' => $mentions > 0 ? round($sentiment * log($mentions + 1), 2) : 0,
                ];
            }

            usort($trends, fn ($a, $b) => $b['mentions'] <=> $a['mentions']);

            return [
                'competitor_count' => count($trends),
                'trends' => array_slice($trends, 0, 10),
                'summary' => [
                    'total_mentions' => array_sum(array_column($trends, 'mentions')),
                    'avg_sentiment' => count($trends) > 0
                        ? round(array_sum(array_column($trends, 'sentiment_score')) / count($trends), 2)
                        : 0,
                ],
            ];
        });
    }

    public function getIndustryTrends(int $agencyId): array
    {
        return Cache::remember("trends:industry:{$agencyId}", self::CACHE_TTL, function () use ($agencyId) {
            $agencyPosts = SocialPost::where('agency_id', $agencyId)
                ->where('status', 'published')
                ->where('published_at', '>=', now()->subDays(30))
                ->get();

            $allTags = $agencyPosts->pluck('tags')->flatten()->filter();
            $allHashtags = $agencyPosts->pluck('hashtags')->flatten()->filter();

            $tagCounts = $this->extractAndCount(collect([$allTags->toArray()]));
            $hashtagCounts = $this->extractAndCount(collect([$allHashtags->toArray()]));

            arsort($tagCounts);
            arsort($hashtagCounts);

            $avgEngagement = $agencyPosts->avg('engagement_rate') ?? 0;
            $postingFrequency = $agencyPosts->count() / 30;

            return [
                'top_tags' => array_slice($tagCounts, 0, 10, true),
                'top_hashtags' => array_slice($hashtagCounts, 0, 10, true),
                'avg_engagement_rate' => round((float) $avgEngagement, 2),
                'posting_frequency' => round($postingFrequency, 2),
                'total_posts_analyzed' => $agencyPosts->count(),
                'trend_direction' => $this->determineIndustryDirection($agencyId),
            ];
        });
    }

    private function extractAndCount(Collection $tagCollections): array
    {
        $counts = [];

        foreach ($tagCollections as $tags) {
            if (is_string($tags)) {
                $tags = json_decode($tags, true) ?? [];
            }

            if (! is_array($tags)) {
                continue;
            }

            foreach ($tags as $tag) {
                $normalized = strtolower(trim($tag));
                if ($normalized === '') {
                    continue;
                }
                $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
            }
        }

        arsort($counts);

        return $counts;
    }

    private function calculateTrendVelocity(int $agencyId, int $days): float
    {
        $current = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays($days / 2))
            ->avg('engagement_rate') ?? 0;

        $previous = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->whereBetween('published_at', [
                now()->subDays($days),
                now()->subDays($days / 2),
            ])
            ->avg('engagement_rate') ?? 0;

        if ($previous <= 0) {
            return 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function calculateTopicConfidence(int $currentCount, int $previousCount): float
    {
        $sampleSize = $currentCount + $previousCount;
        $baseConfidence = min(0.95, $sampleSize / 50);
        $stabilityBonus = $previousCount > 0 ? 0.05 : 0;

        return round(min(1.0, $baseConfidence + $stabilityBonus), 2);
    }

    private function determineIndustryDirection(int $agencyId): string
    {
        $velocity = $this->calculateTrendVelocity($agencyId, 30);

        if ($velocity > 10) {
            return 'strongly_rising';
        }
        if ($velocity > 5) {
            return 'rising';
        }
        if ($velocity < -10) {
            return 'strongly_falling';
        }
        if ($velocity < -5) {
            return 'falling';
        }

        return 'stable';
    }
}
