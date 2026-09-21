<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Models\SocialPost;
use App\Models\Campaign;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;

class PredictiveAnalyticsEngine
{
    public function __construct(
        private readonly AiGateway $aiGateway,
    ) {}

    /**
     * Predict engagement rate for content before publishing
     */
    public function predictEngagement(SocialPost $post): PredictionResult
    {
        $features = $this->extractFeatures($post);
        
        $response = $this->aiGateway->send(new AiRequest(
            prompt: "Predict the engagement rate for this social media post.\n\n" .
                    "Platform: {$post->platform}\n" .
                    "Content: {$post->content}\n" .
                    "Hashtags: " . implode(', ', json_decode($post->hashtags ?? '[]', true) ?? []) . "\n" .
                    "Media: " . ($post->media_url ? 'Yes' : 'No') . "\n" .
                    "Scheduled time: {$post->scheduled_at}\n\n" .
                    "Historical performance for this account:\n" .
                    "- Avg engagement: {$features['avg_engagement']}%\n" .
                    "- Best posting hour: {$features['best_hour']}\n" .
                    "- Best content type: {$features['best_content_type']}\n\n" .
                    "Respond with JSON: {\"predicted_engagement_rate\": X.X, \"confidence\": 0.0-1.0, \"factors\": {...}, \"improvement_suggestions\": [...]}",
            maxTokens: 1000,
        ));

        $prediction = json_decode($response->content, true);

        return new PredictionResult(
            predictedEngagementRate: $prediction['predicted_engagement_rate'] ?? 0,
            confidence: $prediction['confidence'] ?? 0.5,
            factors: $prediction['factors'] ?? [],
            improvementSuggestions: $prediction['improvement_suggestions'] ?? [],
        );
    }

    /**
     * Forecast campaign performance
     */
    public function forecastCampaign(Campaign $campaign, int $days = 30): CampaignForecast
    {
        $posts = SocialPost::where('campaign_id', $campaign->id)
            ->where('status', 'published')
            ->get();

        $historicalData = [
            'total_posts' => $posts->count(),
            'avg_engagement' => $posts->avg('engagement_rate') ?? 0,
            'total_reach' => $posts->sum('reach') ?? 0,
            'engagement_trend' => $this->calculateTrend($posts),
        ];

        $response = $this->aiGateway->send(new AiRequest(
            prompt: "Forecast campaign performance for the next {$days} days.\n\n" .
                    "Campaign: {$campaign->name}\n" .
                    "Objective: {$campaign->objective}\n" .
                    "Historical data: " . json_encode($historicalData) . "\n\n" .
                    "Respond with JSON: {\"forecast\": [{\"day\": 1, \"predicted_engagement\": X.X, \"predicted_reach\": X, \"confidence\": 0.0-1.0}], \"summary\": \"...\", \"recommendations\": [...]}",
            maxTokens: 2000,
        ));

        $forecast = json_decode($response->content, true);

        return new CampaignForecast(
            campaignId: $campaign->id,
            dailyPredictions: $forecast['forecast'] ?? [],
            summary: $forecast['summary'] ?? '',
            recommendations: $forecast['recommendations'] ?? [],
        );
    }

    /**
     * Predict viral potential of content
     */
    public function predictViralPotential(SocialPost $post): ViralPrediction
    {
        $response = $this->aiGateway->send(new AiRequest(
            prompt: "Analyze this content for viral potential.\n\n" .
                    "Platform: {$post->platform}\n" .
                    "Content: {$post->content}\n" .
                    "Hashtags: " . implode(', ', json_decode($post->hashtags ?? '[]', true) ?? []) . "\n\n" .
                    "Consider: emotional appeal, shareability, timeliness, uniqueness, visual appeal.\n" .
                    "Respond with JSON: {\"viral_score\": 0-100, \"factors\": {...}, \"platform_optimization\": {...}, \"best_time_to_post\": \"...\"}",
            maxTokens: 1000,
        ));

        $prediction = json_decode($response->content, true);

        return new ViralPrediction(
            viralScore: $prediction['viral_score'] ?? 0,
            factors: $prediction['factors'] ?? [],
            platformOptimization: $prediction['platform_optimization'] ?? [],
            bestTimeToPost: $prediction['best_time_to_post'] ?? '',
        );
    }

    /**
     * Extract features from a post for prediction
     */
    private function extractFeatures(SocialPost $post): array
    {
        $account = $post->socialAccount;
        
        $recentPosts = SocialPost::where('social_account_id', $account->id)
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays(30))
            ->get();

        return [
            'avg_engagement' => $recentPosts->avg('engagement_rate') ?? 0,
            'post_count' => $recentPosts->count(),
            'best_hour' => $this->getBestHour($recentPosts),
            'worst_hour' => $this->getWorstHour($recentPosts),
            'best_content_type' => $this->getBestContentType($recentPosts),
            'avg_hashtags' => $recentPosts->avg(fn($p) => count(json_decode($p->hashtags ?? '[]', true) ?? [])),
            'has_media_ratio' => $recentPosts->whereNotNull('media_url')->count() / max($recentPosts->count(), 1),
        ];
    }

    /**
     * Calculate engagement trend
     */
    private function calculateTrend($posts): string
    {
        if ($posts->count() < 5) return 'insufficient_data';

        $firstHalf = $posts->take(floor($posts->count() / 2))->avg('engagement_rate') ?? 0;
        $secondHalf = $posts->skip(floor($posts->count() / 2))->avg('engagement_rate') ?? 0;

        if ($secondHalf > $firstHalf * 1.1) return 'improving';
        if ($secondHalf < $firstHalf * 0.9) return 'declining';
        return 'stable';
    }

    private function getBestHour($posts) {
        return $posts->groupBy(fn($p) => $p->published_at?->hour)
            ->map(fn($g) => $g->avg('engagement_rate'))
            ->sortDesc()
            ->keys()
            ->first() ?? 12;
    }

    private function getWorstHour($posts) {
        return $posts->groupBy(fn($p) => $p->published_at?->hour)
            ->map(fn($g) => $g->avg('engagement_rate'))
            ->sort()
            ->keys()
            ->first() ?? 0;
    }

    private function getBestContentType($posts) {
        $withMedia = $posts->whereNotNull('media_url')->avg('engagement_rate') ?? 0;
        $withoutMedia = $posts->whereNull('media_url')->avg('engagement_rate') ?? 0;
        return $withMedia > $withoutMedia ? 'media' : 'text';
    }
}
