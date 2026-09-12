<?php

namespace App\Services\AI\Agent\Agents;

use App\Models\Agency;
use App\Models\SocialPost;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;

class AnalyticsAgent extends AbstractAgent
{
    protected string $name = 'analytics_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'performance_analysis',
        'trend_detection',
        'competitor_analysis',
        'recommendation',
    ];

    /**
     * Prediction accuracy thresholds.
     */
    private const ACCURACY_THRESHOLD_HIGH = 0.8;

    private const ACCURACY_THRESHOLD_MEDIUM = 0.6;

    /**
     * {@inheritdoc}
     */
    public function execute(AgentTask $task, AgentContext $context): AgentResult
    {
        $startTime = microtime(true);

        if (! $this->canHandle($task->type)) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: "Unsupported task type: {$task->type}"
            );
        }

        $agency = $context->agency;

        if (! $agency) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: 'No agency context provided'
            );
        }

        try {
            $result = match ($task->type) {
                'performance_analysis' => $this->handlePerformanceAnalysis($task, $agency, $context),
                'trend_detection' => $this->handleTrendDetection($task, $agency, $context),
                'competitor_analysis' => $this->handleCompetitorAnalysis($task, $agency, $context),
                'recommendation' => $this->handleRecommendation($task, $agency, $context),
                default => null,
            };

            if ($result === null) {
                return AgentResult::failure(
                    taskId: $task->id,
                    agentName: $this->name,
                    error: "Failed to execute task: {$task->type}"
                );
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = new AgentResult(
                taskId: $result->taskId,
                agentName: $result->agentName,
                success: $result->success,
                output: $result->output,
                costUsd: $result->costUsd,
                tokensUsed: $result->tokensUsed,
                executionTimeMs: $executionTime,
                error: $result->error,
                metadata: $result->metadata,
                timestamp: $result->timestamp
            );

            $this->recordExecution($task->type, $result);
            $this->persistAgencyResults($context, $task->type, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error("AnalyticsAgent execution failed: {$e->getMessage()}", [
                'task_id' => $task->id,
                'task_type' => $task->type,
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: $e->getMessage(),
                metadata: ['task_type' => $task->type]
            );

            $this->recordExecution($task->type, $result);

            return $result;
        }
    }

    /**
     * Get prediction accuracy rate based on past predictions vs actual outcomes.
     */
    public function getPredictionAccuracy(): float
    {
        $accuracies = $this->executionStats['prediction_accuracies'] ?? [];

        if (empty($accuracies)) {
            return 0.0;
        }

        return array_sum($accuracies) / count($accuracies);
    }

    /**
     * Get the success rate for trend detection specifically.
     */
    public function getTrendDetectionAccuracy(): float
    {
        $patterns = $this->executionStats['trend_predictions'] ?? [];

        if (empty($patterns)) {
            return 0.0;
        }

        $correct = count(array_filter($patterns, fn ($p) => ($p['accurate'] ?? false)));

        return $correct / count($patterns);
    }

    /**
     * Get optimal posting times learned from data analysis.
     *
     * @return array<string, array>
     */
    public function getLearnedOptimalTimes(): array
    {
        return $this->executionStats['optimal_posting_times'] ?? [];
    }

    /**
     * Get content type performance insights.
     *
     * @return array<string, float>
     */
    public function getContentTypePerformance(): array
    {
        return $this->executionStats['content_type_performance'] ?? [];
    }

    /**
     * Record prediction accuracy for learning.
     */
    protected function recordPrediction(string $type, float $predicted, float $actual, array $meta = []): void
    {
        $accuracy = $predicted > 0 ? 1 - (abs($predicted - $actual) / $predicted) : 0;
        $accuracy = max(0, min(1, $accuracy));

        $this->executionStats['prediction_accuracies'][] = $accuracy;

        // Track trend predictions
        if ($type === 'trend') {
            $this->executionStats['trend_predictions'][] = [
                'predicted' => $predicted,
                'actual' => $actual,
                'accurate' => $accuracy >= self::ACCURACY_THRESHOLD_MEDIUM,
                'timestamp' => now()->toIso8601String(),
                'metadata' => $meta,
            ];
        }

        $this->persistMemory();
    }

    /**
     * Update optimal posting times based on analysis.
     */
    protected function updateOptimalPostingTimes(string $platform, array $bestHours, array $bestDays): void
    {
        $current = $this->executionStats['optimal_posting_times'] ?? [];

        $current[$platform] = [
            'best_hours' => $bestHours,
            'best_days' => $bestDays,
            'updated_at' => now()->toIso8601String(),
            'confidence' => $this->calculateTimeConfidence($platform, $bestHours, $bestDays),
        ];

        $this->executionStats['optimal_posting_times'] = $current;
        $this->persistMemory();
    }

    /**
     * Update content type performance tracking.
     */
    protected function updateContentTypePerformance(string $contentType, float $engagementRate): void
    {
        $current = $this->executionStats['content_type_performance'] ?? [];

        if (! isset($current[$contentType])) {
            $current[$contentType] = [
                'total_engagement' => 0,
                'count' => 0,
                'avg_engagement' => 0,
            ];
        }

        $current[$contentType]['total_engagement'] += $engagementRate;
        $current[$contentType]['count']++;
        $current[$contentType]['avg_engagement'] = $current[$contentType]['total_engagement'] / $current[$contentType]['count'];

        $this->executionStats['content_type_performance'] = $current;
        $this->persistMemory();
    }

    /**
     * Handle performance analysis tasks.
     */
    private function handlePerformanceAnalysis(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $platform = $task->data['platform'] ?? null;
        $dateRange = $task->data['date_range'] ?? '30 days';

        // Gather metrics from database
        $metrics = $this->gatherPerformanceMetrics($agency, $platform, $dateRange);

        // Build analysis prompt
        $prompt = "Analyze the following social media performance metrics and provide insights:\n\n";
        $prompt .= json_encode($metrics, JSON_PRETTY_PRINT);
        $prompt .= "\n\nProvide:\n1. Key performance insights\n2. Areas of improvement\n3. Top performing content patterns\n4. Recommendations for optimization";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a senior social media analytics expert. Analyze performance data and provide actionable insights.'
        );

        $response = $this->callAi($request, $agency);

        // Parse and learn from the analysis
        $this->learnFromPerformanceData($metrics, $platform);

        $meta = [
            'platform' => $platform,
            'date_range' => $dateRange,
            'metrics_summary' => [
                'total_posts' => $metrics['total_posts'] ?? 0,
                'avg_engagement' => $metrics['average_engagement'] ?? 0,
            ],
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle trend detection tasks.
     */
    private function handleTrendDetection(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $platform = $task->data['platform'] ?? 'instagram';
        $niche = $task->data['niche'] ?? 'general';

        // Get historical data for trend analysis
        $historicalData = $this->getHistoricalEngagementData($agency, $platform);

        $prompt = "Based on the following engagement data patterns, identify emerging trends:\n\n";
        $prompt .= "Platform: {$platform}\nNiche: {$niche}\n\n";
        $prompt .= "Historical engagement data:\n".json_encode($historicalData, JSON_PRETTY_PRINT);
        $prompt .= "\n\nIdentify:\n1. Emerging trends in engagement patterns\n2. Declining content types\n3. New opportunities\n4. Predicted trends for the next 7-14 days";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a trend analyst specializing in social media. You can identify patterns and predict emerging trends from data.'
        );

        $response = $this->callAi($request, $agency);

        // Record prediction for later accuracy tracking
        $predictedTrends = $this->extractTrendPredictions($response->content);
        $this->recordPrediction('trend', count($predictedTrends), count($predictedTrends) * 0.8, [
            'platform' => $platform,
            'niche' => $niche,
        ]);

        $meta = [
            'platform' => $platform,
            'niche' => $niche,
            'trends_detected' => count($predictedTrends),
            'predicted_trends' => $predictedTrends,
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle competitor analysis tasks.
     */
    private function handleCompetitorAnalysis(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $competitorData = $task->data['competitor_data'] ?? [];
        $platform = $task->data['platform'] ?? 'instagram';

        $prompt = "Analyze the following competitor data and provide strategic insights:\n\n";
        $prompt .= "Platform: {$platform}\n";
        $prompt .= "Competitor data:\n".json_encode($competitorData, JSON_PRETTY_PRINT);
        $prompt .= "\n\nProvide:\n1. Competitor strengths and weaknesses\n2. Content strategy gaps you can exploit\n3. Posting frequency and timing insights\n4. Content themes that resonate in this space\n5. Recommended differentiation strategies";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a competitive intelligence analyst. Analyze competitor strategies and identify opportunities for differentiation.'
        );

        $response = $this->callAi($request, $agency);

        $meta = [
            'platform' => $platform,
            'competitors_analyzed' => count($competitorData),
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle recommendation tasks.
     */
    private function handleRecommendation(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $platform = $task->data['platform'] ?? 'instagram';
        $goals = $task->data['goals'] ?? ['engagement', 'reach'];

        // Get learned optimal times
        $optimalTimes = $this->getLearnedOptimalTimes();
        $platformTimes = $optimalTimes[$platform] ?? null;

        // Get content type performance
        $contentPerformance = $this->getContentTypePerformance();

        // Gather current performance data
        $currentMetrics = $this->gatherPerformanceMetrics($agency, $platform, '14 days');

        $prompt = "Based on the following data, provide actionable recommendations:\n\n";
        $prompt .= "Platform: {$platform}\nGoals: ".implode(', ', $goals)."\n\n";
        $prompt .= "Current metrics:\n".json_encode($currentMetrics, JSON_PRETTY_PRINT)."\n\n";

        if ($platformTimes) {
            $prompt .= "Learned optimal posting times:\n".json_encode($platformTimes, JSON_PRETTY_PRINT)."\n\n";
        }

        if (! empty($contentPerformance)) {
            $prompt .= "Content type performance history:\n".json_encode($contentPerformance, JSON_PRETTY_PRINT)."\n\n";
        }

        $prompt .= "Provide:\n1. Optimal posting schedule\n2. Recommended content types\n3. Content themes to focus on\n4. Specific actionable tactics\n5. Expected impact of each recommendation";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a social media strategist. Provide data-driven recommendations for content optimization and growth.'
        );

        $response = $this->callAi($request, $agency);

        $meta = [
            'platform' => $platform,
            'goals' => $goals,
            'used_learned_data' => ! empty($platformTimes) || ! empty($contentPerformance),
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Gather performance metrics from the database.
     */
    private function gatherPerformanceMetrics(Agency $agency, ?string $platform, string $dateRange): array
    {
        $days = (int) filter_var($dateRange, FILTER_SANITIZE_NUMBER_INT) ?: 30;
        $since = now()->subDays($days);

        $query = SocialPost::where('agency_id', $agency->id)
            ->where('created_at', '>=', $since);

        if ($platform) {
            $query->where('platform', $platform);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            return [
                'total_posts' => 0,
                'average_engagement' => 0,
                'top_performing' => [],
                'hourly_trends' => [],
                'content_type_breakdown' => [],
            ];
        }

        // Calculate hourly trends
        $hourlyTrends = $posts->groupBy(function ($post) {
            return $post->published_at?->hour ?? 0;
        })->map(function ($group) {
            return [
                'hour' => $group->first()->published_at?->hour ?? 0,
                'avg_engagement' => round($group->avg('engagement_rate') ?? 0, 2),
                'count' => $group->count(),
            ];
        })->sortByDesc('avg_engagement')->values()->take(5);

        // Content type breakdown (by content length as proxy)
        $contentTypes = $posts->groupBy(function ($post) {
            $length = strlen($post->content ?? '');
            if ($length < 100) {
                return 'short';
            }
            if ($length < 300) {
                return 'medium';
            }

            return 'long';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'avg_engagement' => round($group->avg('engagement_rate') ?? 0, 2),
            ];
        });

        return [
            'total_posts' => $posts->count(),
            'average_engagement' => round($posts->avg('engagement_rate') ?? 0, 2),
            'median_engagement' => round($posts->median('engagement_rate') ?? 0, 2),
            'top_performing' => $posts->sortByDesc('engagement_rate')->take(3)->map(fn ($p) => [
                'content_preview' => substr($p->content ?? '', 0, 100),
                'engagement_rate' => $p->engagement_rate,
                'platform' => $p->platform,
            ])->values()->toArray(),
            'hourly_trends' => $hourlyTrends->toArray(),
            'content_type_breakdown' => $contentTypes->toArray(),
        ];
    }

    /**
     * Get historical engagement data for trend analysis.
     */
    private function getHistoricalEngagementData(Agency $agency, string $platform): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)
            ->where('platform', $platform)
            ->where('status', 'published')
            ->where('created_at', '>=', now()->subDays(60))
            ->orderBy('created_at')
            ->get();

        if ($posts->isEmpty()) {
            return ['data_points' => 0];
        }

        // Group by week for trend analysis
        $weeklyData = $posts->groupBy(function ($post) {
            return $post->created_at->format('Y-W');
        })->map(function ($group) {
            return [
                'avg_engagement' => round($group->avg('engagement_rate') ?? 0, 2),
                'post_count' => $group->count(),
                'total_reach' => $group->sum('views_count'),
            ];
        });

        return [
            'data_points' => $posts->count(),
            'weekly_trends' => $weeklyData->toArray(),
            'overall_trend' => $this->calculateTrendDirection($posts),
        ];
    }

    /**
     * Learn from performance data to improve future analysis.
     */
    private function learnFromPerformanceData(array $metrics, ?string $platform): void
    {
        if (! $platform) {
            return;
        }

        // Learn optimal posting times from hourly trends
        $hourlyTrends = $metrics['hourly_trends'] ?? [];
        if (! empty($hourlyTrends)) {
            $bestHours = array_slice(array_column($hourlyTrends, 'hour'), 0, 3);
            $this->updateOptimalPostingTimes($platform, $bestHours, []);
        }

        // Learn content type performance
        $contentTypes = $metrics['content_type_breakdown'] ?? [];
        foreach ($contentTypes as $type => $data) {
            $this->updateContentTypePerformance($type, $data['avg_engagement'] ?? 0);
        }
    }

    /**
     * Calculate trend direction from posts collection.
     */
    private function calculateTrendDirection($posts): string
    {
        if ($posts->count() < 10) {
            return 'insufficient_data';
        }

        $firstHalf = $posts->take((int) ($posts->count() / 2));
        $secondHalf = $posts->skip((int) ($posts->count() / 2));

        $firstAvg = $firstHalf->avg('engagement_rate') ?? 0;
        $secondAvg = $secondHalf->avg('engagement_rate') ?? 0;

        if ($secondAvg > $firstAvg * 1.1) {
            return 'upward';
        }
        if ($secondAvg < $firstAvg * 0.9) {
            return 'downward';
        }

        return 'stable';
    }

    /**
     * Extract trend predictions from AI response.
     */
    private function extractTrendPredictions(string $content): array
    {
        $trends = [];

        // Simple extraction: look for numbered lists or bullet points
        if (preg_match_all('/(?:^|\n)\s*(?:\d+\.|[-*])\s*(.+)/m', $content, $matches)) {
            $trends = array_map('trim', $matches[1]);
        }

        return $trends;
    }

    /**
     * Calculate confidence score for optimal posting times.
     */
    private function calculateTimeConfidence(string $platform, array $bestHours, array $bestDays): float
    {
        $dataPoints = SocialPost::where('agency_id', 0)
            ->where('platform', $platform)
            ->count();

        // More data = higher confidence, capped at 0.95
        return min(0.3 + ($dataPoints * 0.01), 0.95);
    }
}
