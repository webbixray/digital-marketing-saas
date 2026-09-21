<?php

namespace App\Services\AI;

class PerformanceAnalysis
{
    public function __construct(
        public readonly int $totalPosts,
        public readonly float $avgEngagementRate,
        public readonly int $totalReach,
        public readonly int $totalClicks,
        public readonly float $conversionRate,
        public readonly array $topPerformingPosts,
        public readonly array $worstPerformingPosts,
        public readonly array $engagementByPlatform,
        public readonly array $engagementByDay,
        public readonly array $engagementByHour,
        public readonly array $contentThemes,
        public readonly float $audienceGrowth,
        public readonly float $roi,
    ) {}

    public function toArray(): array
    {
        return [
            'total_posts' => $this->totalPosts,
            'avg_engagement_rate' => $this->avgEngagementRate,
            'total_reach' => $this->totalReach,
            'total_clicks' => $this->totalClicks,
            'conversion_rate' => $this->conversionRate,
            'audience_growth' => $this->audienceGrowth,
            'roi' => $this->roi,
        ];
    }
}
