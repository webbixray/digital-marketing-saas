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

class SocialMediaAgent extends AbstractAgent
{
    protected string $name = 'social_media_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'post_schedule',
        'post_optimize',
        'engagement_analysis',
        'reply_suggest',
    ];

    /**
     * Engagement thresholds for success scoring.
     */
    private const ENGAGEMENT_THRESHOLD_HIGH = 0.7;

    private const ENGAGEMENT_THRESHOLD_MEDIUM = 0.4;

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
                'post_schedule' => $this->handlePostSchedule($task, $agency, $context),
                'post_optimize' => $this->handlePostOptimize($task, $agency, $context),
                'engagement_analysis' => $this->handleEngagementAnalysis($task, $agency, $context),
                'reply_suggest' => $this->handleReplySuggest($task, $agency, $context),
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
            Log::error("SocialMediaAgent execution failed: {$e->getMessage()}", [
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
     * Get success rate based on engagement improvement.
     * Measures how often the agent's actions lead to improved engagement.
     */
    public function getSuccessRate(): float
    {
        $scores = $this->executionStats['engagement_scores'] ?? [];

        if (empty($scores)) {
            return 0.5;
        }

        $highEngagement = count(array_filter($scores, fn ($s) => $s >= self::ENGAGEMENT_THRESHOLD_HIGH));
        $mediumEngagement = count(array_filter($scores, fn ($s) => $s >= self::ENGAGEMENT_THRESHOLD_MEDIUM));

        // Weight: high engagement counts double, medium counts once
        $weightedSuccesses = ($highEngagement * 2) + $mediumEngagement;

        return min($weightedSuccesses / (count($scores) * 2), 1.0);
    }

    /**
     * Get best posting times learned per platform per agency.
     *
     * @return array<string, array>
     */
    public function getBestPostingTimes(): array
    {
        return $this->executionStats['best_posting_times'] ?? [];
    }

    /**
     * Get engagement trend data.
     *
     * @return array<string, float>
     */
    public function getEngagementTrends(): array
    {
        return $this->executionStats['engagement_trends'] ?? [];
    }

    /**
     * Handle post scheduling with optimal time learning.
     */
    private function handlePostSchedule(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $platform = $task->data['platform'] ?? 'instagram';
        $content = $task->data['content'] ?? '';
        $preferredDate = $task->data['preferred_date'] ?? null;

        // Get learned best times for this platform
        $bestTimes = $this->getBestPostingTimes();
        $platformBestTimes = $bestTimes[$platform] ?? null;

        // Build scheduling prompt
        $prompt = "Determine the optimal posting time for the following content:\n\n";
        $prompt .= "Platform: {$platform}\n";
        $prompt .= "Content: {$content}\n";

        if ($preferredDate) {
            $prompt .= "Preferred date: {$preferredDate}\n";
        }

        if ($platformBestTimes) {
            $prompt .= "\nBased on past performance data:\n";
            $prompt .= '- Best hours: '.implode(', ', $platformBestTimes['best_hours'] ?? [])."\n";
            $prompt .= '- Best days: '.implode(', ', $platformBestTimes['best_days'] ?? [])."\n";
            $prompt .= '- Confidence: '.($platformBestTimes['confidence'] ?? 'low')."\n";
        }

        $prompt .= "\nProvide:\n1. Recommended date and time\n2. Reasoning for the recommendation\n3. Expected engagement level (low/medium/high)";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a social media scheduling expert. You analyze content and historical engagement data to determine optimal posting times.'
        );

        $response = $this->callAi($request, $agency);

        // Parse recommended time from response
        $recommendedTime = $this->parseRecommendedTime($response->content, $preferredDate);

        $engagementScore = $this->predictEngagement($content, $platform);
        $meta = [
            'platform' => $platform,
            'recommended_time' => $recommendedTime,
            'engagement_score' => $engagementScore,
            'used_learned_times' => $platformBestTimes !== null,
        ];

        $this->recordEngagementScore('post_schedule', $engagementScore, $meta);

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
     * Handle post optimization for better engagement.
     */
    private function handlePostOptimize(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $content = $task->data['content'] ?? '';
        $platform = $task->data['platform'] ?? 'instagram';
        $goals = $task->data['goals'] ?? ['engagement'];

        // Get learned patterns from past optimizations
        $pastPatterns = $this->getLearnedPatterns($context, 'post_optimize');
        $topPatterns = $this->extractTopOptimizationPatterns($pastPatterns);

        $prompt = "Optimize the following social media content for {$platform}:\n\n{$content}\n\n";
        $prompt .= 'Goals: '.implode(', ', $goals)."\n";

        if (! empty($topPatterns)) {
            $prompt .= "\nBased on past successful optimizations, these patterns work well:\n";
            foreach ($topPatterns as $pattern) {
                $prompt .= "- {$pattern}\n";
            }
        }

        $prompt .= "\nProvide:\n1. Optimized content\n2. Changes made and why\n3. Expected improvement in engagement";

        $request = AiRequest::creative(
            prompt: $prompt,
            systemPrompt: 'You are a social media content optimizer. You improve content to maximize engagement while maintaining brand voice.'
        );

        $response = $this->callAi($request, $agency);

        $originalEngagement = $this->predictEngagement($content, $platform);
        $optimizedEngagement = $this->predictEngagement($response->content, $platform);
        $engagementScore = max($optimizedEngagement, $originalEngagement);

        $meta = [
            'platform' => $platform,
            'original_engagement' => $originalEngagement,
            'optimized_engagement' => $optimizedEngagement,
            'engagement_score' => $engagementScore,
            'goals' => $goals,
        ];

        $this->recordEngagementScore('post_optimize', $engagementScore, $meta);

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
     * Handle engagement analysis and learning.
     */
    private function handleEngagementAnalysis(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $platform = $task->data['platform'] ?? null;
        $dateRange = $task->data['date_range'] ?? '30 days';

        // Gather engagement metrics
        $metrics = $this->gatherEngagementMetrics($agency, $platform, $dateRange);

        $prompt = "Analyze the following social media engagement data and provide insights:\n\n";
        $prompt .= json_encode($metrics, JSON_PRETTY_PRINT);
        $prompt .= "\n\nProvide:\n1. Engagement trends and patterns\n2. Top performing content characteristics\n3. Optimal posting times based on data\n4. Recommendations for improvement\n5. Predicted engagement for next week";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a social media engagement analyst. You identify patterns in engagement data and provide actionable insights.'
        );

        $response = $this->callAi($request, $agency);

        // Learn from the analysis
        $this->learnFromEngagementData($metrics, $platform);

        $engagementScore = $metrics['average_engagement'] ?? 0;
        $meta = [
            'platform' => $platform,
            'date_range' => $dateRange,
            'metrics_summary' => [
                'total_posts' => $metrics['total_posts'] ?? 0,
                'avg_engagement' => $metrics['average_engagement'] ?? 0,
            ],
            'engagement_score' => $engagementScore,
        ];

        $this->recordEngagementScore('engagement_analysis', $engagementScore, $meta);

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
     * Handle reply suggestions for comments/messages.
     */
    private function handleReplySuggest(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $message = $task->data['message'] ?? '';
        $platform = $task->data['platform'] ?? 'instagram';
        $tone = $task->data['tone'] ?? 'friendly';
        $context_info = $task->data['context'] ?? '';

        // Get past successful replies
        $pastReplies = $this->getLearnedPatterns($context, 'reply_suggest');
        $successfulReplies = array_filter($pastReplies, fn ($r) => ($r['metadata']['engagement_score'] ?? 0) >= self::ENGAGEMENT_THRESHOLD_MEDIUM);

        $prompt = "Suggest a reply to the following {$platform} message:\n\n";
        $prompt .= "Message: {$message}\n";
        $prompt .= "Tone: {$tone}\n";

        if ($context_info) {
            $prompt .= "Context: {$context_info}\n";
        }

        if (! empty($successfulReplies)) {
            $prompt .= "\nBased on past successful replies, these approaches work well:\n";
            $topReplies = array_slice($successfulReplies, 0, 3);
            foreach ($topReplies as $reply) {
                $prompt .= '- Style: '.($reply['metadata']['tone'] ?? 'friendly')."\n";
            }
        }

        $prompt .= "\nProvide:\n1. Suggested reply\n2. Alternative reply options\n3. Tone analysis";

        $request = AiRequest::creative(
            prompt: $prompt,
            systemPrompt: 'You are a social media community manager. You craft engaging, on-brand replies that encourage positive interaction.'
        );

        $response = $this->callAi($request, $agency);

        $engagementScore = 0.6; // Default for replies
        $meta = [
            'platform' => $platform,
            'tone' => $tone,
            'engagement_score' => $engagementScore,
            'message_type' => $this->classifyMessageType($message),
        ];

        $this->recordEngagementScore('reply_suggest', $engagementScore, $meta);

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
     * Gather engagement metrics from the database.
     */
    private function gatherEngagementMetrics(Agency $agency, ?string $platform, string $dateRange): array
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
                'hourly_trends' => [],
                'daily_trends' => [],
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

        // Calculate daily trends
        $dailyTrends = $posts->groupBy(function ($post) {
            return $post->published_at?->format('l') ?? 'unknown';
        })->map(function ($group) {
            return [
                'day' => $group->first()->published_at?->format('l') ?? 'unknown',
                'avg_engagement' => round($group->avg('engagement_rate') ?? 0, 2),
                'count' => $group->count(),
            ];
        })->sortByDesc('avg_engagement')->values();

        return [
            'total_posts' => $posts->count(),
            'average_engagement' => round($posts->avg('engagement_rate') ?? 0, 2),
            'median_engagement' => round($posts->median('engagement_rate') ?? 0, 2),
            'hourly_trends' => $hourlyTrends->toArray(),
            'daily_trends' => $dailyTrends->toArray(),
        ];
    }

    /**
     * Learn from engagement data to improve posting strategies.
     */
    private function learnFromEngagementData(array $metrics, ?string $platform): void
    {
        if (! $platform) {
            return;
        }

        // Learn best posting times
        $hourlyTrends = $metrics['hourly_trends'] ?? [];
        $dailyTrends = $metrics['daily_trends'] ?? [];

        if (! empty($hourlyTrends) || ! empty($dailyTrends)) {
            $bestHours = array_slice(array_column($hourlyTrends, 'hour'), 0, 3);
            $bestDays = array_slice(array_column($dailyTrends, 'day'), 0, 3);
            $this->updateBestPostingTimes($platform, $bestHours, $bestDays);
        }

        // Track engagement trends
        $avgEngagement = $metrics['average_engagement'] ?? 0;
        $trends = $this->executionStats['engagement_trends'] ?? [];
        $trends[$platform] = $avgEngagement;
        $this->executionStats['engagement_trends'] = $trends;

        $this->persistMemory();
    }

    /**
     * Update best posting times for a platform.
     */
    private function updateBestPostingTimes(string $platform, array $bestHours, array $bestDays): void
    {
        $current = $this->executionStats['best_posting_times'] ?? [];

        $current[$platform] = [
            'best_hours' => $bestHours,
            'best_days' => $bestDays,
            'updated_at' => now()->toIso8601String(),
            'confidence' => $this->calculateTimeConfidence($platform, $bestHours, $bestDays),
        ];

        $this->executionStats['best_posting_times'] = $current;
        $this->persistMemory();
    }

    /**
     * Record engagement score and learn from it.
     */
    private function recordEngagementScore(string $taskType, float $engagementScore, array $meta = []): void
    {
        $this->executionStats['engagement_scores'][] = $engagementScore;

        // Track task-type specific engagement
        $taskEngagement = $this->executionStats['task_engagement'] ?? [];
        $taskEngagement[$taskType][] = $engagementScore;
        $this->executionStats['task_engagement'] = $taskEngagement;

        $this->persistMemory();
    }

    /**
     * Predict engagement score for content.
     */
    private function predictEngagement(string $content, string $platform): float
    {
        $score = 0.5;
        $length = strlen($content);

        $optimalLengths = [
            'instagram' => [100, 500],
            'facebook' => [80, 250],
            'twitter' => [70, 280],
            'linkedin' => [150, 800],
            'tiktok' => [50, 150],
        ];

        $optimal = $optimalLengths[$platform] ?? [100, 300];
        if ($length >= $optimal[0] && $length <= $optimal[1]) {
            $score += 0.15;
        } elseif ($length < $optimal[0]) {
            $score += 0.05;
        }

        $hashtagCount = substr_count($content, '#');
        if ($hashtagCount >= 3 && $hashtagCount <= 15) {
            $score += 0.1;
        }

        if (str_contains($content, '?')) {
            $score += 0.08;
        }

        if (preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}]/u', $content)) {
            $score += 0.07;
        }

        $ctaPatterns = ['click', 'link', 'learn more', 'comment', 'share', 'follow', 'discover', 'get'];
        foreach ($ctaPatterns as $cta) {
            if (stripos($content, $cta) !== false) {
                $score += 0.05;
                break;
            }
        }

        return min($score, 1.0);
    }

    /**
     * Parse recommended time from AI response.
     */
    private function parseRecommendedTime(string $content, ?string $preferredDate): ?string
    {
        // Try to extract datetime from response
        if (preg_match('/\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $content, $matches)) {
            return $matches[0];
        }

        if (preg_match('/\d{1,2}:\d{2}\s*(?:AM|PM|am|pm)?/', $content, $matches)) {
            $time = $matches[0];
            $date = $preferredDate ?? now()->addDay()->format('Y-m-d');

            return "{$date} {$time}";
        }

        return $preferredDate ? "{$date} 10:00" : null;
    }

    /**
     * Extract top optimization patterns from past results.
     */
    private function extractTopOptimizationPatterns(array $pastResults): array
    {
        $patterns = [];

        foreach ($pastResults as $result) {
            $meta = $result['metadata'] ?? [];
            $engagement = $meta['engagement_score'] ?? 0;

            if ($engagement >= self::ENGAGEMENT_THRESHOLD_MEDIUM) {
                if (isset($meta['platform'])) {
                    $patterns[] = "Platform: {$meta['platform']}";
                }
            }
        }

        return array_unique(array_slice($patterns, 0, 5));
    }

    /**
     * Classify message type for reply suggestions.
     */
    private function classifyMessageType(string $message): string
    {
        $message = strtolower($message);

        if (str_contains($message, '?')) {
            return 'question';
        }
        if (str_contains($message, '!') || str_contains($message, 'love') || str_contains($message, 'great')) {
            return 'positive';
        }
        if (str_contains($message, 'bad') || str_contains($message, 'hate') || str_contains($message, 'terrible')) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Calculate confidence score for posting times.
     */
    private function calculateTimeConfidence(string $platform, array $bestHours, array $bestDays): float
    {
        $dataPoints = SocialPost::where('agency_id', 0)
            ->where('platform', $platform)
            ->count();

        return min(0.3 + ($dataPoints * 0.01), 0.95);
    }
}
