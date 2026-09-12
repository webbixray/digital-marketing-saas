<?php

namespace App\Services\AI\Agent\Agents;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\SocialPost;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;

class ReportAgent extends AbstractAgent
{
    protected string $name = 'report_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'weekly_report',
        'monthly_report',
        'campaign_report',
        'custom_report',
    ];

    /**
     * Client satisfaction thresholds.
     */
    private const SATISFACTION_THRESHOLD_HIGH = 0.8;

    private const SATISFACTION_THRESHOLD_MEDIUM = 0.5;

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
                'weekly_report' => $this->handleWeeklyReport($task, $agency, $context),
                'monthly_report' => $this->handleMonthlyReport($task, $agency, $context),
                'campaign_report' => $this->handleCampaignReport($task, $agency, $context),
                'custom_report' => $this->handleCustomReport($task, $agency, $context),
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
            Log::error("ReportAgent execution failed: {$e->getMessage()}", [
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
     * {@inheritdoc}
     *
     * Success rate based on report usage and client feedback.
     */
    public function getSuccessRate(): float
    {
        $satisfactionScores = $this->executionStats['satisfaction_scores'] ?? [];

        if (empty($satisfactionScores)) {
            return 0.5;
        }

        $highSatisfaction = count(array_filter($satisfactionScores, fn ($s) => $s >= self::SATISFACTION_THRESHOLD_HIGH));
        $mediumSatisfaction = count(array_filter($satisfactionScores, fn ($s) => $s >= self::SATISFACTION_THRESHOLD_MEDIUM));

        $weightedSuccesses = ($highSatisfaction * 2) + $mediumSatisfaction;

        return min($weightedSuccesses / (count($satisfactionScores) * 2), 1.0);
    }

    /**
     * Get report formats that produce best client satisfaction.
     *
     * @return array<string, float>
     */
    public function getBestReportFormats(): array
    {
        return $this->executionStats['report_format_satisfaction'] ?? [];
    }

    /**
     * Get report generation statistics.
     *
     * @return array<string, int>
     */
    public function getReportStats(): array
    {
        return $this->executionStats['report_type_counts'] ?? [];
    }

    /**
     * Handle weekly report generation.
     */
    private function handleWeeklyReport(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $clientId = $task->data['client_id'] ?? null;
        $platform = $task->data['platform'] ?? null;
        $format = $task->data['format'] ?? 'summary';

        // Gather data from multiple sources
        $socialData = $this->gatherSocialPostData($agency, $clientId, $platform, 7);
        $campaignData = $this->gatherCampaignData($agency, $clientId, 7);
        $clientData = $clientId ? $this->getClientSummary($agency, $clientId) : null;

        // Get learned best formats
        $bestFormats = $this->getBestReportFormats();
        $suggestedFormat = $bestFormats ? array_key_first($bestFormats) : $format;

        $prompt = "Generate a comprehensive weekly report for the following data:\n\n";
        $prompt .= "Report period: Last 7 days\n";
        $prompt .= "Format: {$suggestedFormat}\n\n";

        if ($clientData) {
            $prompt .= "Client: {$clientData['name']}\n";
            $prompt .= "Industry: {$clientData['industry']}\n\n";
        }

        $prompt .= "Social Media Performance:\n".json_encode($socialData, JSON_PRETTY_PRINT)."\n\n";
        $prompt .= "Campaign Performance:\n".json_encode($campaignData, JSON_PRETTY_PRINT)."\n\n";

        $prompt .= "Provide:\n";
        $prompt .= "1. Executive Summary\n";
        $prompt .= "2. Key Metrics & KPIs\n";
        $prompt .= "3. Week-over-Week Comparison\n";
        $prompt .= "4. Top Performing Content\n";
        $prompt .= "5. Areas for Improvement\n";
        $prompt .= "6. Recommendations for Next Week\n";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a digital marketing report specialist. Generate clear, actionable reports that highlight key insights and drive client satisfaction.'
        );

        $response = $this->callAi($request, $agency);

        // Calculate satisfaction score based on report quality indicators
        $satisfactionScore = $this->calculateSatisfactionScore($response->content, $suggestedFormat);

        $meta = [
            'report_type' => 'weekly',
            'client_id' => $clientId,
            'platform' => $platform,
            'format' => $suggestedFormat,
            'satisfaction_score' => $satisfactionScore,
            'data_sources' => ['social_posts', 'campaigns', 'clients'],
        ];

        $this->recordSatisfactionScore('weekly_report', $suggestedFormat, $satisfactionScore, $meta);
        $this->incrementReportCount('weekly_report');

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
     * Handle monthly report generation.
     */
    private function handleMonthlyReport(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $clientId = $task->data['client_id'] ?? null;
        $platform = $task->data['platform'] ?? null;
        $format = $task->data['format'] ?? 'detailed';
        $includeComparison = $task->data['include_comparison'] ?? true;

        // Gather comprehensive monthly data
        $socialData = $this->gatherSocialPostData($agency, $clientId, $platform, 30);
        $campaignData = $this->gatherCampaignData($agency, $clientId, 30);
        $clientData = $clientId ? $this->getClientSummary($agency, $clientId) : null;

        // Get learned best formats
        $bestFormats = $this->getBestReportFormats();
        $suggestedFormat = $bestFormats ? array_key_first($bestFormats) : $format;

        $prompt = "Generate a comprehensive monthly report for the following data:\n\n";
        $prompt .= "Report period: Last 30 days\n";
        $prompt .= "Format: {$suggestedFormat}\n";
        $prompt .= 'Include month-over-month comparison: '.($includeComparison ? 'Yes' : 'No')."\n\n";

        if ($clientData) {
            $prompt .= "Client: {$clientData['name']}\n";
            $prompt .= "Industry: {$clientData['industry']}\n\n";
        }

        $prompt .= "Social Media Performance:\n".json_encode($socialData, JSON_PRETTY_PRINT)."\n\n";
        $prompt .= "Campaign Performance:\n".json_encode($campaignData, JSON_PRETTY_PRINT)."\n\n";

        $prompt .= "Provide:\n";
        $prompt .= "1. Executive Summary\n";
        $prompt .= "2. Monthly Performance Overview\n";
        $prompt .= "3. Key Metrics & KPIs with Trends\n";
        $prompt .= "4. Campaign Performance Breakdown\n";
        $prompt .= "5. Top Performing Content & Insights\n";
        $prompt .= "6. Month-over-Month Comparison\n";
        $prompt .= "7. ROI Analysis\n";
        $prompt .= "8. Strategic Recommendations for Next Month\n";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a senior digital marketing analyst. Create detailed monthly reports that demonstrate value, highlight growth, and provide strategic insights.'
        );

        $response = $this->callAi($request, $agency);

        $satisfactionScore = $this->calculateSatisfactionScore($response->content, $suggestedFormat);

        $meta = [
            'report_type' => 'monthly',
            'client_id' => $clientId,
            'platform' => $platform,
            'format' => $suggestedFormat,
            'satisfaction_score' => $satisfactionScore,
            'include_comparison' => $includeComparison,
            'data_sources' => ['social_posts', 'campaigns', 'clients'],
        ];

        $this->recordSatisfactionScore('monthly_report', $suggestedFormat, $satisfactionScore, $meta);
        $this->incrementReportCount('monthly_report');

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
     * Handle campaign-specific report generation.
     */
    private function handleCampaignReport(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $campaignId = $task->data['campaign_id'] ?? null;
        $format = $task->data['format'] ?? 'detailed';

        if (! $campaignId) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: 'Campaign ID is required for campaign_report task'
            );
        }

        $campaign = Campaign::where('agency_id', $agency->id)->find($campaignId);

        if (! $campaign) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: "Campaign not found: {$campaignId}"
            );
        }

        // Gather campaign-specific data
        $posts = SocialPost::where('agency_id', $agency->id)
            ->whereHas('campaigns', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->get();

        $client = $campaign->client;

        $bestFormats = $this->getBestReportFormats();
        $suggestedFormat = $bestFormats ? array_key_first($bestFormats) : $format;

        $prompt = "Generate a comprehensive campaign performance report:\n\n";
        $prompt .= "Campaign: {$campaign->name}\n";
        $prompt .= "Type: {$campaign->type}\n";
        $prompt .= "Status: {$campaign->status}\n";
        $prompt .= "Duration: {$campaign->start_date?->format('Y-m-d')} to {$campaign->end_date?->format('Y-m-d')}\n";
        $prompt .= "Format: {$suggestedFormat}\n\n";

        if ($client) {
            $prompt .= "Client: {$client->name}\n";
            $prompt .= "Industry: {$client->industry}\n\n";
        }

        $prompt .= "Campaign Metrics:\n";
        $prompt .= "- Posts: {$posts->count()}\n";
        $prompt .= "- Total Views: {$posts->sum('views_count')}\n";
        $prompt .= "- Total Likes: {$posts->sum('likes_count')}\n";
        $prompt .= "- Total Comments: {$posts->sum('comments_count')}\n";
        $prompt .= "- Total Shares: {$posts->sum('shares_count')}\n";
        $prompt .= "- Total Clicks: {$posts->sum('clicks_count')}\n";
        $prompt .= "- Estimated ROI: {$campaign->estimated_roi}\n";
        $prompt .= "- Engagement Rate: {$campaign->engagement_rate}\n\n";

        $prompt .= "Platform Breakdown:\n";
        $platformBreakdown = $posts->groupBy('platform')->map(fn ($group) => [
            'posts' => $group->count(),
            'avg_engagement' => round($group->avg('engagement_rate') ?? 0, 2),
            'total_views' => $group->sum('views_count'),
        ]);
        $prompt .= json_encode($platformBreakdown, JSON_PRETTY_PRINT)."\n\n";

        $prompt .= "Provide:\n";
        $prompt .= "1. Campaign Overview\n";
        $prompt .= "2. Performance Against Objectives\n";
        $prompt .= "3. Platform-by-Platform Analysis\n";
        $prompt .= "4. Top Performing Posts\n";
        $prompt .= "5. Audience Engagement Analysis\n";
        $prompt .= "6. ROI Assessment\n";
        $prompt .= "7. Lessons Learned\n";
        $prompt .= "8. Recommendations for Future Campaigns\n";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a campaign performance analyst. Generate insightful campaign reports that demonstrate impact and provide actionable learnings.'
        );

        $response = $this->callAi($request, $agency);

        $satisfactionScore = $this->calculateSatisfactionScore($response->content, $suggestedFormat);

        $meta = [
            'report_type' => 'campaign',
            'campaign_id' => $campaignId,
            'campaign_name' => $campaign->name,
            'format' => $suggestedFormat,
            'satisfaction_score' => $satisfactionScore,
            'data_sources' => ['campaign', 'social_posts', 'client'],
        ];

        $this->recordSatisfactionScore('campaign_report', $suggestedFormat, $satisfactionScore, $meta);
        $this->incrementReportCount('campaign_report');

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
     * Handle custom report generation.
     */
    private function handleCustomReport(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $reportConfig = $task->data['config'] ?? [];
        $metrics = $reportConfig['metrics'] ?? ['engagement', 'reach', 'clicks'];
        $dateRange = $reportConfig['date_range'] ?? '30 days';
        $sections = $reportConfig['sections'] ?? ['summary', 'metrics', 'recommendations'];
        $format = $reportConfig['format'] ?? 'summary';

        $days = (int) filter_var($dateRange, FILTER_SANITIZE_NUMBER_INT) ?: 30;

        // Gather data based on requested metrics
        $socialData = $this->gatherSocialPostData($agency, null, null, $days);
        $campaignData = $this->gatherCampaignData($agency, null, $days);

        $bestFormats = $this->getBestReportFormats();
        $suggestedFormat = $bestFormats ? array_key_first($bestFormats) : $format;

        $prompt = "Generate a custom report based on the following configuration:\n\n";
        $prompt .= "Date Range: {$dateRange}\n";
        $prompt .= 'Metrics: '.implode(', ', $metrics)."\n";
        $prompt .= 'Sections: '.implode(', ', $sections)."\n";
        $prompt .= "Format: {$suggestedFormat}\n\n";

        $prompt .= "Social Media Data:\n".json_encode($socialData, JSON_PRETTY_PRINT)."\n\n";
        $prompt .= "Campaign Data:\n".json_encode($campaignData, JSON_PRETTY_PRINT)."\n\n";

        $prompt .= "Include the following sections:\n";
        foreach ($sections as $section) {
            $prompt .= '- '.ucfirst($section)."\n";
        }

        $prompt .= "\nProvide a well-structured report with the requested metrics and insights.";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a flexible report generation specialist. Create custom reports tailored to specific client needs and configurations.'
        );

        $response = $this->callAi($request, $agency);

        $satisfactionScore = $this->calculateSatisfactionScore($response->content, $suggestedFormat);

        $meta = [
            'report_type' => 'custom',
            'format' => $suggestedFormat,
            'satisfaction_score' => $satisfactionScore,
            'metrics' => $metrics,
            'sections' => $sections,
            'date_range' => $dateRange,
            'data_sources' => ['social_posts', 'campaigns'],
        ];

        $this->recordSatisfactionScore('custom_report', $suggestedFormat, $satisfactionScore, $meta);
        $this->incrementReportCount('custom_report');

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
     * Gather social post data for reports.
     */
    private function gatherSocialPostData(Agency $agency, ?int $clientId, ?string $platform, int $days): array
    {
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
                'platforms' => [],
                'top_content' => [],
            ];
        }

        return [
            'total_posts' => $posts->count(),
            'average_engagement' => round($posts->avg('engagement_rate') ?? 0, 2),
            'total_views' => $posts->sum('views_count'),
            'total_likes' => $posts->sum('likes_count'),
            'total_comments' => $posts->sum('comments_count'),
            'total_shares' => $posts->sum('shares_count'),
            'total_clicks' => $posts->sum('clicks_count'),
            'platforms' => $posts->groupBy('platform')->map(fn ($g) => [
                'count' => $g->count(),
                'avg_engagement' => round($g->avg('engagement_rate') ?? 0, 2),
            ])->toArray(),
            'top_content' => $posts->sortByDesc('engagement_rate')->take(5)->map(fn ($p) => [
                'content_preview' => substr($p->content ?? '', 0, 100),
                'engagement_rate' => $p->engagement_rate,
                'platform' => $p->platform,
            ])->values()->toArray(),
        ];
    }

    /**
     * Gather campaign data for reports.
     */
    private function gatherCampaignData(Agency $agency, ?int $clientId, int $days): array
    {
        $since = now()->subDays($days);

        $query = Campaign::where('agency_id', $agency->id)
            ->where('created_at', '>=', $since);

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            return [
                'total_campaigns' => 0,
                'active_campaigns' => 0,
                'avg_roi' => 0,
                'avg_engagement' => 0,
            ];
        }

        return [
            'total_campaigns' => $campaigns->count(),
            'active_campaigns' => $campaigns->where('status', 'active')->count(),
            'avg_roi' => round($campaigns->avg('estimated_roi') ?? 0, 2),
            'avg_engagement' => round($campaigns->avg('engagement_rate') ?? 0, 2),
            'total_views' => $campaigns->sum('views_count'),
            'total_clicks' => $campaigns->sum('clicks_count'),
            'campaigns' => $campaigns->map(fn ($c) => [
                'name' => $c->name,
                'type' => $c->type,
                'status' => $c->status,
                'roi' => $c->estimated_roi,
                'engagement' => $c->engagement_rate,
            ])->toArray(),
        ];
    }

    /**
     * Get client summary data.
     */
    private function getClientSummary(Agency $agency, int $clientId): ?array
    {
        $client = Client::where('agency_id', $agency->id)->find($clientId);

        if (! $client) {
            return null;
        }

        return [
            'name' => $client->name,
            'industry' => $client->industry,
            'company' => $client->company,
            'status' => $client->status,
            'posts_count' => $client->posts_count,
            'campaigns_count' => $client->campaigns_count,
        ];
    }

    /**
     * Calculate satisfaction score based on report quality indicators.
     */
    private function calculateSatisfactionScore(string $content, string $format): float
    {
        $score = 0.5;

        // Longer, more detailed reports score higher
        $wordCount = str_word_count($content);
        if ($wordCount > 500) {
            $score += 0.15;
        } elseif ($wordCount > 200) {
            $score += 0.08;
        }

        // Reports with structured sections score higher
        if (preg_match('/#{1,3}\s+\w+/', $content)) {
            $score += 0.1;
        }

        // Reports with data/numbers score higher
        if (preg_match('/\d+[\.%]?/', $content)) {
            $score += 0.08;
        }

        // Reports with recommendations score higher
        if (stripos($content, 'recommend') !== false || stripos($content, 'suggest') !== false) {
            $score += 0.07;
        }

        // Format bonus: detailed formats tend to score higher
        if ($format === 'detailed') {
            $score += 0.05;
        }

        return min($score, 1.0);
    }

    /**
     * Record satisfaction score for learning.
     */
    private function recordSatisfactionScore(string $taskType, string $format, float $score, array $meta = []): void
    {
        $this->executionStats['satisfaction_scores'][] = $score;

        // Track format-specific satisfaction
        $formatSatisfaction = $this->executionStats['report_format_satisfaction'] ?? [];
        if (! isset($formatSatisfaction[$format])) {
            $formatSatisfaction[$format] = ['total' => 0, 'sum' => 0, 'avg' => 0];
        }
        $formatSatisfaction[$format]['total']++;
        $formatSatisfaction[$format]['sum'] += $score;
        $formatSatisfaction[$format]['avg'] = $formatSatisfaction[$format]['sum'] / $formatSatisfaction[$format]['total'];

        // Sort by average satisfaction descending
        uasort($formatSatisfaction, fn ($a, $b) => $b['avg'] <=> $a['avg']);
        $this->executionStats['report_format_satisfaction'] = $formatSatisfaction;

        $this->persistMemory();
    }

    /**
     * Increment report type count.
     */
    private function incrementReportCount(string $reportType): void
    {
        $counts = $this->executionStats['report_type_counts'] ?? [];
        $counts[$reportType] = ($counts[$reportType] ?? 0) + 1;
        $this->executionStats['report_type_counts'] = $counts;
        $this->persistMemory();
    }
}
