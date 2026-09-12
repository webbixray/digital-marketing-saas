<?php

namespace App\Services\AI\Agent\Agents;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialPost;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;

class CampaignAgent extends AbstractAgent
{
    protected string $name = 'campaign_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'campaign_optimize',
        'audience_suggest',
        'budget_allocate',
        'ab_test_design',
    ];

    /**
     * ROI thresholds for success scoring.
     */
    private const ROI_THRESHOLD_HIGH = 2.0;

    private const ROI_THRESHOLD_MEDIUM = 1.0;

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
                'campaign_optimize' => $this->handleCampaignOptimize($task, $agency, $context),
                'audience_suggest' => $this->handleAudienceSuggest($task, $agency, $context),
                'budget_allocate' => $this->handleBudgetAllocate($task, $agency, $context),
                'ab_test_design' => $this->handleAbTestDesign($task, $agency, $context),
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
            Log::error("CampaignAgent execution failed: {$e->getMessage()}", [
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
     * Get success rate based on ROI improvement.
     * Measures how often the agent's optimizations lead to improved ROI.
     */
    public function getSuccessRate(): float
    {
        $roiScores = $this->executionStats['roi_scores'] ?? [];

        if (empty($roiScores)) {
            return 0.5;
        }

        $highRoi = count(array_filter($roiScores, fn ($r) => $r >= self::ROI_THRESHOLD_HIGH));
        $mediumRoi = count(array_filter($roiScores, fn ($r) => $r >= self::ROI_THRESHOLD_MEDIUM));

        // Weight: high ROI counts double, medium counts once
        $weightedSuccesses = ($highRoi * 2) + $mediumRoi;

        return min($weightedSuccesses / (count($roiScores) * 2), 1.0);
    }

    /**
     * Get budget allocation recommendations learned from past campaigns.
     *
     * @return array<string, float>
     */
    public function getBudgetRecommendations(): array
    {
        return $this->executionStats['budget_recommendations'] ?? [];
    }

    /**
     * Get audience segments that perform well.
     *
     * @return array<string, array>
     */
    public function getTopAudienceSegments(): array
    {
        return $this->executionStats['top_audience_segments'] ?? [];
    }

    /**
     * Handle campaign optimization.
     */
    private function handleCampaignOptimize(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $campaignId = $task->data['campaign_id'] ?? null;
        $goals = $task->data['goals'] ?? ['engagement', 'reach'];

        // Get campaign data
        $campaign = $campaignId ? Campaign::find($campaignId) : null;
        $campaignData = $campaign ? $this->getCampaignData($agency, $campaign) : $this->getRecentCampaignsData($agency);

        // Get learned optimization strategies
        $pastOptimizations = $this->getLearnedPatterns($context, 'campaign_optimize');
        $successfulStrategies = $this->extractSuccessfulStrategies($pastOptimizations);

        $prompt = "Analyze and optimize the following campaign data:\n\n";
        $prompt .= json_encode($campaignData, JSON_PRETTY_PRINT);
        $prompt .= "\n\nGoals: ".implode(', ', $goals)."\n";

        if (! empty($successfulStrategies)) {
            $prompt .= "\nBased on past successful optimizations:\n";
            foreach ($successfulStrategies as $strategy) {
                $prompt .= "- {$strategy}\n";
            }
        }

        $prompt .= "\nProvide:\n1. Current performance assessment\n2. Specific optimization recommendations\n3. Expected ROI improvement\n4. Platform-specific tactics\n5. Timeline for implementation";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a digital marketing campaign optimizer. You analyze campaign data and provide data-driven recommendations to improve performance and ROI.'
        );

        $response = $this->callAi($request, $agency);

        // Calculate ROI score
        $roiScore = $this->calculateRoiScore($campaignData);

        $meta = [
            'campaign_id' => $campaignId,
            'goals' => $goals,
            'roi_score' => $roiScore,
            'current_roi' => $campaignData['estimated_roi'] ?? 0,
        ];

        $this->recordRoiScore('campaign_optimize', $roiScore, $meta);

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
     * Handle audience suggestions.
     */
    private function handleAudienceSuggest(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $campaignType = $task->data['campaign_type'] ?? 'general';
        $platform = $task->data['platform'] ?? 'instagram';
        $currentAudience = $task->data['current_audience'] ?? [];

        // Get learned audience segments
        $topSegments = $this->getTopAudienceSegments();
        $platformSegments = $topSegments[$platform] ?? [];

        $prompt = "Suggest target audience segments for the following campaign:\n\n";
        $prompt .= "Campaign type: {$campaignType}\n";
        $prompt .= "Platform: {$platform}\n";

        if (! empty($currentAudience)) {
            $prompt .= 'Current audience: '.json_encode($currentAudience)."\n";
        }

        if (! empty($platformSegments)) {
            $prompt .= "\nBased on past high-performing campaigns, these segments worked well:\n";
            foreach (array_slice($platformSegments, 0, 5) as $segment) {
                $prompt .= "- {$segment['name']}: {$segment['description']}\n";
            }
        }

        $prompt .= "\nProvide:\n1. Recommended audience segments with demographics\n2. Interests and behaviors to target\n3. Lookalike audience suggestions\n4. Platform-specific targeting options\n5. Expected audience size and quality";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a digital marketing audience strategist. You identify high-value audience segments based on campaign objectives and historical performance.'
        );

        $response = $this->callAi($request, $agency);

        // Parse audience suggestions
        $segments = $this->parseAudienceSegments($response->content);

        // Learn from suggestions
        $this->learnAudienceSegments($platform, $segments);

        $roiScore = 0.6;
        $meta = [
            'campaign_type' => $campaignType,
            'platform' => $platform,
            'segments_suggested' => count($segments),
            'roi_score' => $roiScore,
        ];

        $this->recordRoiScore('audience_suggest', $roiScore, $meta);

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
     * Handle budget allocation recommendations.
     */
    private function handleBudgetAllocate(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $totalBudget = $task->data['total_budget'] ?? 0;
        $campaignId = $task->data['campaign_id'] ?? null;
        $platforms = $task->data['platforms'] ?? ['instagram', 'facebook'];

        // Get past budget allocation performance
        $budgetRecs = $this->getBudgetRecommendations();
        $campaignPerformance = $this->getPlatformPerformance($agency, $platforms);

        $prompt = "Recommend budget allocation across platforms:\n\n";
        $prompt .= "Total budget: \${$totalBudget}\n";
        $prompt .= 'Platforms: '.implode(', ', $platforms)."\n\n";
        $prompt .= "Platform performance data:\n".json_encode($campaignPerformance, JSON_PRETTY_PRINT)."\n";

        if (! empty($budgetRecs)) {
            $prompt .= "\nBased on past budget allocations that yielded high ROI:\n";
            foreach ($budgetRecs as $rec) {
                $prompt .= "- {$rec['platform']}: {$rec['percentage']}% (ROI: {$rec['roi']})\n";
            }
        }

        $prompt .= "\nProvide:\n1. Recommended budget allocation per platform (percentage and amount)\n2. Rationale for each allocation\n3. Expected ROI per platform\n4. Risk assessment\n5. Optimization schedule";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a media buying strategist. You allocate campaign budgets across platforms to maximize ROI based on performance data.'
        );

        $response = $this->callAi($request, $agency);

        // Parse allocation recommendations
        $allocations = $this->parseBudgetAllocations($response->content);

        // Learn from allocations
        $this->learnBudgetAllocations($allocations);

        $avgRoi = ! empty($allocations) ? array_sum(array_column($allocations, 'expected_roi')) / count($allocations) : 0;
        $meta = [
            'total_budget' => $totalBudget,
            'platforms' => $platforms,
            'allocations' => $allocations,
            'roi_score' => $avgRoi,
        ];

        $this->recordRoiScore('budget_allocate', $avgRoi, $meta);

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
     * Handle A/B test design.
     */
    private function handleAbTestDesign(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $testType = $task->data['test_type'] ?? 'creative';
        $campaignId = $task->data['campaign_id'] ?? null;
        $hypothesis = $task->data['hypothesis'] ?? '';

        // Get past test results
        $pastTests = $this->getLearnedPatterns($context, 'ab_test_design');
        $successfulTests = $this->extractSuccessfulTestPatterns($pastTests);

        $prompt = "Design an A/B test for the following scenario:\n\n";
        $prompt .= "Test type: {$testType}\n";
        if ($hypothesis) {
            $prompt .= "Hypothesis: {$hypothesis}\n";
        }

        if (! empty($successfulTests)) {
            $prompt .= "\nBased on past successful A/B tests:\n";
            foreach (array_slice($successfulTests, 0, 3) as $test) {
                $prompt .= "- Test variable: {$test['variable']}, Winner: {$test['winner']}, Lift: {$test['lift']}%\n";
            }
        }

        $prompt .= "\nProvide:\n1. Test variables and variants\n2. Control and treatment definitions\n3. Sample size calculation\n4. Test duration recommendation\n5. Success metrics\n6. Statistical significance threshold\n7. Implementation plan";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a conversion rate optimization expert. You design rigorous A/B tests that produce statistically significant, actionable results.'
        );

        $response = $this->callAi($request, $agency);

        $roiScore = 0.5;
        $meta = [
            'test_type' => $testType,
            'campaign_id' => $campaignId,
            'roi_score' => $roiScore,
        ];

        $this->recordRoiScore('ab_test_design', $roiScore, $meta);

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
     * Get campaign data for optimization.
     */
    private function getCampaignData(Agency $agency, Campaign $campaign): array
    {
        $posts = SocialPost::where('agency_id', $agency->id)
            ->whereHas('campaigns', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->get();

        return [
            'campaign_id' => $campaign->id,
            'name' => $campaign->name,
            'type' => $campaign->type,
            'status' => $campaign->status,
            'estimated_roi' => $campaign->estimated_roi,
            'engagement_rate' => $campaign->engagement_rate,
            'posts_count' => $posts->count(),
            'total_views' => $posts->sum('views_count'),
            'total_engagement' => $posts->sum('likes_count') + $posts->sum('comments_count'),
            'platforms' => $posts->pluck('platform')->unique()->toArray(),
            'date_range' => [
                'start' => $campaign->start_date?->format('Y-m-d'),
                'end' => $campaign->end_date?->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get recent campaigns data.
     */
    private function getRecentCampaignsData(Agency $agency): array
    {
        $campaigns = Campaign::where('agency_id', $agency->id)
            ->where('created_at', '>=', now()->subDays(90))
            ->get();

        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'avg_roi' => $campaigns->avg('estimated_roi'),
            'avg_engagement' => $campaigns->avg('engagement_rate'),
            'campaigns' => $campaigns->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'roi' => $c->estimated_roi,
                'type' => $c->type,
            ])->toArray(),
        ];
    }

    /**
     * Get platform performance data.
     */
    private function getPlatformPerformance(Agency $agency, array $platforms): array
    {
        $performance = [];

        foreach ($platforms as $platform) {
            $posts = SocialPost::where('agency_id', $agency->id)
                ->where('platform', $platform)
                ->where('status', 'published')
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            $performance[$platform] = [
                'posts_count' => $posts->count(),
                'avg_engagement' => $posts->avg('engagement_rate') ?? 0,
                'total_reach' => $posts->sum('views_count'),
                'total_clicks' => $posts->sum('clicks_count'),
            ];
        }

        return $performance;
    }

    /**
     * Calculate ROI score for campaign data.
     */
    private function calculateRoiScore(array $campaignData): float
    {
        $estimatedRoi = $campaignData['estimated_roi'] ?? 0;

        // Normalize ROI to 0-1 scale (assuming max reasonable ROI is 5.0)
        return min(max($estimatedRoi / 5.0, 0), 1.0);
    }

    /**
     * Record ROI score and learn from it.
     */
    private function recordRoiScore(string $taskType, float $roiScore, array $meta = []): void
    {
        $this->executionStats['roi_scores'][] = $roiScore;

        // Track task-type specific ROI
        $taskRoi = $this->executionStats['task_roi'] ?? [];
        $taskRoi[$taskType][] = $roiScore;
        $this->executionStats['task_roi'] = $taskRoi;

        $this->persistMemory();
    }

    /**
     * Extract successful strategies from past optimizations.
     */
    private function extractSuccessfulStrategies(array $pastResults): array
    {
        $strategies = [];

        foreach ($pastResults as $result) {
            $meta = $result['metadata'] ?? [];
            $roi = $meta['roi_score'] ?? 0;

            if ($roi >= 0.5) {
                if (isset($meta['goals'])) {
                    $strategies[] = 'Focus on '.implode(', ', $meta['goals']);
                }
            }
        }

        return array_unique(array_slice($strategies, 0, 5));
    }

    /**
     * Parse audience segments from AI response.
     */
    private function parseAudienceSegments(string $content): array
    {
        $segments = [];

        if (preg_match_all('/(?:^|\n)\s*(?:\d+\.|[-*])\s*(.+)/m', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $segments[] = [
                    'name' => trim($match),
                    'description' => '',
                ];
            }
        }

        return $segments;
    }

    /**
     * Learn audience segments that perform well.
     */
    private function learnAudienceSegments(string $platform, array $segments): void
    {
        $topSegments = $this->executionStats['top_audience_segments'] ?? [];
        $platformSegments = $topSegments[$platform] ?? [];

        foreach ($segments as $segment) {
            $found = false;
            foreach ($platformSegments as &$existing) {
                if ($existing['name'] === $segment['name']) {
                    $existing['occurrences'] = ($existing['occurrences'] ?? 0) + 1;
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $platformSegments[] = array_merge($segment, ['occurrences' => 1]);
            }
        }

        // Keep top 20 segments per platform
        usort($platformSegments, fn ($a, $b) => ($b['occurrences'] ?? 0) <=> ($a['occurrences'] ?? 0));
        $topSegments[$platform] = array_slice($platformSegments, 0, 20);

        $this->executionStats['top_audience_segments'] = $topSegments;
        $this->persistMemory();
    }

    /**
     * Parse budget allocations from AI response.
     */
    private function parseBudgetAllocations(string $content): array
    {
        $allocations = [];

        // Match patterns like "Instagram: 40% ($4,000)" or "Facebook - 30%"
        if (preg_match_all('/(\w+)[:\-]\s*(\d+)%\s*(?:\([^)]*\))?/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $allocations[] = [
                    'platform' => strtolower($match[1]),
                    'percentage' => (int) $match[2],
                    'expected_roi' => 0, // Will be updated based on results
                ];
            }
        }

        return $allocations;
    }

    /**
     * Learn from budget allocations.
     */
    private function learnBudgetAllocations(array $allocations): void
    {
        $recs = $this->executionStats['budget_recommendations'] ?? [];

        foreach ($allocations as $allocation) {
            $recs[] = [
                'platform' => $allocation['platform'],
                'percentage' => $allocation['percentage'],
                'roi' => $allocation['expected_roi'],
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Keep last 50 recommendations
        if (count($recs) > 50) {
            $recs = array_slice($recs, -50);
        }

        $this->executionStats['budget_recommendations'] = $recs;
        $this->persistMemory();
    }

    /**
     * Extract successful test patterns from past results.
     */
    private function extractSuccessfulTestPatterns(array $pastResults): array
    {
        $patterns = [];

        foreach ($pastResults as $result) {
            $meta = $result['metadata'] ?? [];
            $roi = $meta['roi_score'] ?? 0;

            if ($roi >= 0.4) {
                $patterns[] = [
                    'variable' => $meta['test_type'] ?? 'unknown',
                    'winner' => 'variant_a',
                    'lift' => round($roi * 100, 1),
                ];
            }
        }

        return $patterns;
    }
}
