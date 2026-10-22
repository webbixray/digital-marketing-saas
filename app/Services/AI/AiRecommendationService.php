<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Models\SocialPost;

class AiRecommendationService
{
    /**
     * Get content recommendations for an agency.
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getContentRecommendations(Agency $agency, string $platform, int $count = 5): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('platform', $platform)
            ->where('status', 'published')
            ->orderByDesc('engagement_rate')
            ->limit($count * 3)
            ->get();

        $recommendations = [];
        foreach ($posts->take($count) as $post) {
            $recommendations[] = [
                'title' => $post->content ? substr($post->content, 0, 100).'...' : 'Untitled',
                'type' => 'content_recommendation',
                'content' => $post->content ?? '',
                'hashtags' => $this->extractHashtags($post->content ?? ''),
                'best_time' => $post->published_at?->format('H:i') ?? 'N/A',
                'engagement_rate' => $post->engagement_rate ?? 0,
                'scores' => [
                    'engagement' => $post->engagement_rate ?? 0,
                    'reach' => $post->views_count ?? 0,
                    'impressions' => ($post->views_count ?? 0) + ($post->likes_count ?? 0),
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Get optimal posting times for a platform.
     *
     * @return array<string, mixed>
     */
    public function getOptimalPostingTimes(Agency $agency, string $platform): array
    {
        $bestHours = $this->getBestHoursForPlatform($platform);
        $bestDays = $this->getBestDaysForPlatform($platform);

        return [
            'platform' => $platform,
            'best_hours' => $bestHours,
            'best_days' => $bestDays,
            'timezone' => $agency->timezone ?? 'UTC',
        ];
    }

    /**
     * Generate hashtags for a topic.
     */
    /**
     * @return array<int, string>
     */
    public function generateHashtags(Agency $agency, string $topic, string $platform, int $count = 10): array
    {
        $baseHashtags = [
            '#marketing', '#digitalmarketing', '#contentmarketing',
            '#socialmedia', '#branding', '#business', '#entrepreneur',
            '#smallbusiness', '#marketingtips', '#contentcreator',
            '#instagram', '#facebook', '#twitter', '#linkedin',
            '#tiktok', '#pinterest', '#youtube', '#threads',
        ];

        $platformSpecific = [
            'instagram' => ['#instagramreels', '#instagood', '#photooftheday', '#reels'],
            'facebook' => ['#facebookmarketing', '#fb', '#facebookpost'],
            'twitter' => ['#twitter', '#tweet', '#viral', '#trending'],
            'linkedin' => ['#linkedin', '#business', '#professional', '#networking', '#career'],
            'tiktok' => ['#tiktok', '#fyp', '#viral', '#trending', '#dance'],
            'pinterest' => ['#pinterest', '#pinterestideas', '#inspiration', '#diy'],
            'threads' => ['#threads', '#meta', '#conversation', '#discussion'],
        ];

        $hashtags = array_merge($baseHashtags, $platformSpecific[$platform] ?? []);
        $hashtags = array_slice($hashtags, 0, $count);

        return array_values(array_unique($hashtags));
    }

    /**
     * Analyze content quality.
     */
    /**
     * @return array<string, mixed>
     */
    public function analyzeContentQuality(string $content, string $platform): array
    {
        $length = strlen($content);
        $wordCount = str_word_count($content);
        $hashtagCount = substr_count($content, '#');
        $mentionCount = substr_count($content, '@');
        $hasEmoji = preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}]/u', $content) ? 1 : 0;
        $hasQuestion = substr_count($content, '?');
        $hasCallToAction = stripos($content, 'click') !== false || stripos($content, 'link') !== false || stripos($content, 'learn more') !== false;

        // Score calculation (0-100)
        $score = 0;
        $score += min($length / 10, 20);
        $score += min($wordCount / 2, 20);
        $score += $hashtagCount * 5;
        $score += $mentionCount * 3;
        $score += $hasEmoji * 10;
        $score += $hasQuestion * 10;
        $score += $hasCallToAction * 15;
        $score = min($score, 100);

        // Engagement prediction (0-100)
        $engagementPrediction = $score * 0.7 + ($hasQuestion ? 15 : 0) + ($hasCallToAction ? 15 : 0);

        return [
            'score' => round($score),
            'engagement_prediction' => round($engagementPrediction),
            'metrics' => [
                'length' => $length,
                'word_count' => $wordCount,
                'hashtags' => $hashtagCount,
                'mentions' => $mentionCount,
                'has_emoji' => (bool) $hasEmoji,
                'has_question' => $hasQuestion > 0,
                'has_cta' => $hasCallToAction,
            ],
            'suggestions' => $this->generateSuggestions($content, $platform, $score, $hashtagCount, $wordCount),
            'platform_best_practices' => $this->getPlatformBestPractices($platform),
        ];
    }

    /**
     * Get post performance analytics.
     */
    /**
     * @return array<string, mixed>
     */
    public function getPostPerformanceAnalytics(Agency $agency, ?string $platform = null): array
    {
        $query = SocialPost::where('agency_id', $agency->id);

        if ($platform) {
            $query->where('platform', $platform);
        }

        $posts = $query->where('status', 'published')->get();

        if ($posts->isEmpty()) {
            return [
                'total_posts' => 0,
                'average_engagement' => 0,
                'top_performing' => [],
                'worst_performing' => [],
                'trends' => [],
            ];
        }

        $avgEngagement = $posts->avg('engagement_rate') ?? 0;
        $topPosts = $posts->sortByDesc('engagement_rate')->take(5)->values();
        $worstPosts = $posts->sortBy('engagement_rate')->take(5)->values();

        // Hourly trends
        $hourlyTrends = $posts->groupBy(function ($post) {
            return $post->published_at?->hour ?? 0;
        })->map(function ($group) {
            return [
                'hour' => $group->first()->published_at?->hour ?? 0,
                'avg_engagement' => $group->avg('engagement_rate') ?? 0,
                'count' => $group->count(),
            ];
        })->sortBy('hour')->values();

        return [
            'total_posts' => $posts->count(),
            'average_engagement' => round($avgEngagement, 2),
            'median_engagement' => round($posts->median('engagement_rate') ?? 0, 2),
            'max_engagement' => round($posts->max('engagement_rate') ?? 0, 2),
            'min_engagement' => round($posts->min('engagement_rate') ?? 0, 2),
            'top_performing' => $topPosts->map(fn ($p) => [
                'id' => $p->id,
                'content' => substr($p->content, 0, 100),
                'engagement_rate' => $p->engagement_rate,
                'published_at' => $p->published_at?->toDateTimeString(),
            ])->toArray(),
            'worst_performing' => $worstPosts->map(fn ($p) => [
                'id' => $p->id,
                'content' => substr($p->content, 0, 100),
                'engagement_rate' => $p->engagement_rate,
                'published_at' => $p->published_at?->toDateTimeString(),
            ])->toArray(),
            'hourly_trends' => $hourlyTrends->toArray(),
        ];
    }

    /**
     * Extract hashtags from content.
     */
    /**
     * @return array<int, string>
     */
    protected function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);

        return $matches[1] ?? [];
    }

    /**
     * Get best hours for a platform.
     *
     * @return list<string>
     */
    protected function getBestHoursForPlatform(string $platform): array
    {
        $defaults = [
            'instagram' => ['11:00', '13:00', '17:00', '19:00'],
            'facebook' => ['09:00', '13:00', '15:00', '19:00'],
            'twitter' => ['08:00', '12:00', '17:00', '20:00'],
            'linkedin' => ['08:00', '12:00', '17:00'],
            'tiktok' => ['11:00', '15:00', '19:00', '21:00'],
            'pinterest' => ['14:00', '18:00', '21:00'],
            'threads' => ['10:00', '14:00', '18:00'],
        ];

        return $defaults[$platform] ?? ['10:00', '14:00', '18:00'];
    }

    /**
     * Get best days for a platform.
     *
     * @return list<string>
     */
    protected function getBestDaysForPlatform(string $platform): array
    {
        $defaults = [
            'instagram' => ['Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'facebook' => ['Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'twitter' => ['Tuesday', 'Wednesday', 'Thursday'],
            'linkedin' => ['Tuesday', 'Wednesday', 'Thursday'],
            'tiktok' => ['Tuesday', 'Thursday', 'Friday'],
            'pinterest' => ['Saturday', 'Sunday', 'Friday'],
            'threads' => ['Monday', 'Tuesday', 'Wednesday'],
        ];

        return $defaults[$platform] ?? ['Tuesday', 'Wednesday', 'Thursday'];
    }

    /**
     * Generate content improvement suggestions.
     */
    /**
     * @return array<int, string>
     */
    protected function generateSuggestions(string $content, string $platform, float $score, int $hashtagCount, int $wordCount): array
    {
        $suggestions = [];

        if ($score < 50) {
            $suggestions[] = 'Consider adding more engaging content elements like questions or calls-to-action.';
        }

        if ($hashtagCount < 3) {
            $suggestions[] = 'Add more hashtags to increase discoverability (aim for 5-10).';
        } elseif ($hashtagCount > 15) {
            $suggestions[] = 'Too many hashtags may look spammy. Consider reducing to 10-15.';
        }

        if ($wordCount < 20) {
            $suggestions[] = 'Content may be too short. Consider adding more context or value.';
        }

        if (strlen($content) > 2200 && $platform === 'instagram') {
            $suggestions[] = 'Instagram captions over 2200 characters get truncated. Consider shortening.';
        }

        if (strlen($content) > 280 && $platform === 'twitter') {
            $suggestions[] = 'Content exceeds Twitter\'s 280 character limit. Consider shortening.';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Content looks good! Consider A/B testing different variations.';
        }

        return $suggestions;
    }

    /**
     * Get platform best practices.
     */
    /**
     * @return array<int, string>
     */
    protected function getPlatformBestPractices(string $platform): array
    {
        $practices = [
            'instagram' => [
                'Use high-quality visuals',
                'Include 5-10 relevant hashtags',
                'Post Reels for higher reach',
                'Use Stories for engagement',
            ],
            'facebook' => [
                'Use native video content',
                'Ask questions to drive comments',
                'Post at peak hours (9am-3pm)',
                'Use Facebook Live for engagement',
            ],
            'twitter' => [
                'Keep tweets concise and punchy',
                'Use 1-2 hashtags maximum',
                'Include images for higher engagement',
                'Engage in conversations',
            ],
            'linkedin' => [
                'Share professional insights',
                'Use a conversational tone',
                'Include industry hashtags',
                'Tag relevant connections',
            ],
            'tiktok' => [
                'Hook viewers in first 3 seconds',
                'Use trending sounds',
                'Keep videos short and engaging',
                'Post consistently',
            ],
            'pinterest' => [
                'Use vertical images (2:3 ratio)',
                'Write descriptive pin titles',
                'Include keywords in descriptions',
                'Link back to your website',
            ],
            'threads' => [
                'Start conversations',
                'Share behind-the-scenes content',
                'Use a casual, authentic tone',
                'Engage with replies',
            ],
        ];

        return $practices[$platform] ?? ['Consistency is key — post regularly and engage with your audience.'];
    }
}
