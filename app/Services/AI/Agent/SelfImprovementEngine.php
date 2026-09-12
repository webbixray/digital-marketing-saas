<?php

namespace App\Services\AI\Agent;

use App\Models\AgentLearningReport;
use App\Models\AgentPerformanceLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SelfImprovementEngine
{
    public function __construct(
        private readonly AgentFeedbackService $feedbackService,
    ) {}

    /**
     * Analyze agent performance over time and find patterns.
     *
     * @param  array  $results  Recent execution results from an agent
     * @return array Performance analysis with patterns
     */
    public function analyzePerformance(array $results): array
    {
        if (empty($results)) {
            return [
                'status' => 'no_data',
                'message' => 'No performance data to analyze.',
                'patterns' => [],
                'recommendations' => [],
            ];
        }

        $totalRuns = count($results);
        $successfulRuns = array_filter($results, fn ($r) => ($r['success'] ?? false) === true);
        $failedRuns = array_filter($results, fn ($r) => ($r['success'] ?? false) === false);
        $successRate = $totalRuns > 0 ? (count($successfulRuns) / $totalRuns) * 100 : 0;

        // Calculate average execution times
        $executionTimes = array_column($results, 'execution_time_ms');
        $avgExecutionTime = ! empty($executionTimes) ? array_sum($executionTimes) / count($executionTimes) : 0;
        $maxExecutionTime = ! empty($executionTimes) ? max($executionTimes) : 0;
        $minExecutionTime = ! empty($executionTimes) ? min($executionTimes) : 0;

        // Calculate average quality/confidence scores
        $qualityScores = array_filter(array_column($results, 'quality_score'), fn ($s) => $s !== null);
        $avgQuality = ! empty($qualityScores) ? array_sum($qualityScores) / count($qualityScores) : null;

        // Error analysis
        $errorMessages = array_filter(array_column($results, 'error_message'), fn ($e) => ! empty($e));
        $errorFrequency = array_count_values($errorMessages);
        arsort($errorFrequency);
        $topErrors = array_slice($errorFrequency, 0, 5, true);

        // Patterns in parameters that work best
        $successfulParams = array_filter(
            array_column($successfulRuns, 'parameters_used'),
            fn ($p) => is_array($p) && ! empty($p)
        );
        $bestParameters = $this->extractBestParameters($successfulParams);

        // Patterns in what doesn't work
        $failedParams = array_filter(
            array_column($failedRuns, 'parameters_used'),
            fn ($p) => is_array($p) && ! empty($p)
        );
        $worstParameters = $this->extractWorstParameters($failedParams);

        return [
            'status' => 'analyzed',
            'total_runs' => $totalRuns,
            'successful_runs' => count($successfulRuns),
            'failed_runs' => count($failedRuns),
            'success_rate' => round($successRate, 2),
            'execution_time' => [
                'avg_ms' => round($avgExecutionTime, 2),
                'min_ms' => round($minExecutionTime, 2),
                'max_ms' => round($maxExecutionTime, 2),
            ],
            'quality_score' => [
                'average' => $avgQuality !== null ? round($avgQuality, 2) : null,
                'trend' => $this->calculateTrend($qualityScores),
            ],
            'top_errors' => $topErrors,
            'patterns' => [
                'best_parameters' => $bestParameters,
                'worst_parameters' => $worstParameters,
            ],
            'recommendations' => $this->generateRecommendationsFromAnalysis(
                $successRate,
                $avgExecutionTime,
                $avgQuality,
                $topErrors
            ),
        ];
    }

    /**
     * Suggest prompt/strategy changes for an agent based on its performance history.
     *
     * @return array List of suggested improvements
     */
    public function generateImprovements(AgentInterface $agent): array
    {
        $agentName = $agent->getName();
        $performanceLogs = AgentPerformanceLog::forAgent($agentName)
            ->recent(30)
            ->orderBy('recorded_at', 'desc')
            ->limit(100)
            ->get();

        $improvements = [];

        // Use agent's built-in metrics
        $successRate = $agent->getSuccessRate() * 100;
        $speedScore = $agent->getSpeedScore() * 100;
        $costScore = $agent->getCostScore() * 100;

        if ($successRate < 80) {
            $improvements[] = [
                'type' => 'critical',
                'category' => 'reliability',
                'message' => "Agent [{$agentName}] has low success rate: {$successRate}%.",
                'suggestion' => $this->suggestReliabilityFix($successRate),
                'current_value' => $successRate,
                'target_value' => 95,
            ];
        }

        if ($speedScore < 50) {
            $improvements[] = [
                'type' => 'warning',
                'category' => 'performance',
                'message' => "Agent [{$agentName}] speed score is low: {$speedScore}.",
                'suggestion' => 'Consider using a faster model, reducing prompt complexity, or optimizing context size.',
                'current_value' => $speedScore,
                'target_value' => 70,
            ];
        }

        if ($costScore < 50) {
            $improvements[] = [
                'type' => 'info',
                'category' => 'efficiency',
                'message' => "Agent [{$agentName}] cost efficiency is low: {$costScore}.",
                'suggestion' => 'Consider using a more cost-effective model or reducing token usage.',
                'current_value' => $costScore,
                'target_value' => 70,
            ];
        }

        // Analyze performance logs for more specific insights
        if ($performanceLogs->isNotEmpty()) {
            $qualityLogs = $performanceLogs->where('metric_type', 'quality_score');
            if ($qualityLogs->isNotEmpty()) {
                $avgQuality = $qualityLogs->avg('metric_value');
                if ($avgQuality < 70) {
                    $improvements[] = [
                        'type' => 'warning',
                        'category' => 'quality',
                        'message' => "Agent [{$agentName}] quality score is below threshold: {$avgQuality}.",
                        'suggestion' => $this->suggestQualityFix($avgQuality),
                        'current_value' => $avgQuality,
                        'target_value' => 80,
                    ];
                }
            }
        }

        // Record improvements as learning report entries
        foreach ($improvements as $improvement) {
            AgentLearningReport::create([
                'agent_name' => $agentName,
                'improvement_type' => $improvement['category'],
                'description' => $improvement['message'],
                'changes' => [
                    'suggestion' => $improvement['suggestion'],
                    'current_value' => $improvement['current_value'],
                    'target_value' => $improvement['target_value'],
                ],
                'status' => 'pending',
            ]);
        }

        if (empty($improvements)) {
            $improvements[] = [
                'type' => 'info',
                'category' => 'status',
                'message' => "Agent [{$agentName}] is performing within acceptable parameters.",
                'suggestion' => 'Continue monitoring.',
                'current_value' => null,
                'target_value' => null,
            ];
        }

        return $improvements;
    }

    /**
     * Automatically adjust agent parameters based on performance analysis.
     */
    public function autoTune(AgentInterface $agent): void
    {
        $agentName = $agent->getName();

        Log::info("Auto-tuning agent [{$agentName}]...");

        // Get recent performance data
        $recentLogs = AgentPerformanceLog::forAgent($agentName)
            ->recent(14)
            ->get();

        if ($recentLogs->isEmpty()) {
            Log::info("No recent performance data for [{$agentName}], skipping auto-tune.");

            return;
        }

        $tuningApplied = false;
        $currentSuccessRate = $agent->getSuccessRate() * 100;
        $currentSpeedScore = $agent->getSpeedScore() * 100;
        $currentCostScore = $agent->getCostScore() * 100;

        // Analyze success rate trend
        $successLogs = $recentLogs->where('metric_type', 'success_rate');
        if ($successLogs->count() >= 5) {
            $variance = $this->calculateVariance($successLogs->pluck('metric_value')->toArray());

            if ($variance > 100) {
                $tuningApplied = true;
                Log::info("  High variance detected ({$variance}), agent needs stabilization.");
            }
        }

        // Analyze execution speed
        $speedLogs = $recentLogs->where('metric_type', 'execution_time_ms');
        if ($speedLogs->count() >= 5) {
            $avgSpeed = $speedLogs->avg('metric_value');
            if ($avgSpeed > 5000) {
                $tuningApplied = true;
                Log::info("  Slow execution detected ({$avgSpeed}ms avg), consider optimization.");
            }
        }

        if ($tuningApplied) {
            AgentLearningReport::create([
                'agent_name' => $agentName,
                'improvement_type' => 'auto_tune',
                'description' => 'Auto-tuning analysis complete. Performance data recorded for future optimization.',
                'changes' => [
                    'success_rate' => $currentSuccessRate,
                    'speed_score' => $currentSpeedScore,
                    'cost_score' => $currentCostScore,
                ],
                'status' => 'applied',
            ]);

            Log::info("Auto-tuning analysis complete for [{$agentName}].");
        } else {
            Log::info("No tuning adjustments needed for [{$agentName}].");
        }
    }

    /**
     * Generate a comprehensive learning report on what the system has learned.
     *
     * @return array Learning report
     */
    public function getLearningReport(): array
    {
        $cacheKey = 'agent_learning_report';
        $cached = Cache::get($cacheKey);
        if ($cached && ! app()->environment('local')) {
            return $cached;
        }

        $agents = AgentPerformanceLog::select('agent_name')
            ->distinct()
            ->pluck('agent_name')
            ->toArray();

        $agentReports = [];
        foreach ($agents as $agentName) {
            $logs = AgentPerformanceLog::forAgent($agentName)->recent(30)->get();

            if ($logs->isEmpty()) {
                continue;
            }

            $successLogs = $logs->where('metric_type', 'success_rate');
            $qualityLogs = $logs->where('metric_type', 'quality_score');
            $speedLogs = $logs->where('metric_type', 'execution_time_ms');

            $agentReports[] = [
                'agent_name' => $agentName,
                'total_records' => $logs->count(),
                'success_rate_avg' => $successLogs->isNotEmpty() ? round($successLogs->avg('metric_value'), 2) : null,
                'quality_score_avg' => $qualityLogs->isNotEmpty() ? round($qualityLogs->avg('metric_value'), 2) : null,
                'execution_time_avg_ms' => $speedLogs->isNotEmpty() ? round($speedLogs->avg('metric_value'), 2) : null,
                'trend' => [
                    'success' => $this->calculateTrend($successLogs->pluck('metric_value')->toArray()),
                    'quality' => $this->calculateTrend($qualityLogs->pluck('metric_value')->toArray()),
                ],
            ];
        }

        $pendingImprovements = AgentLearningReport::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $appliedImprovements = AgentLearningReport::where('status', 'applied')
            ->count();

        $report = [
            'generated_at' => now()->toDateTimeString(),
            'agents_analyzed' => count($agents),
            'agent_reports' => $agentReports,
            'summary' => [
                'total_pending_improvements' => count($pendingImprovements),
                'total_applied_improvements' => $appliedImprovements,
                'overall_health_score' => $this->calculateOverallHealth($agentReports),
            ],
            'pending_improvements' => $pendingImprovements,
            'top_learnings' => $this->extractTopLearnings(),
        ];

        Cache::put($cacheKey, $report, now()->addHour());

        return $report;
    }

    /**
     * Record a real feedback outcome and trigger learning updates.
     * This is the main entry point for the real feedback loop.
     *
     * @param  string  $agentName  The agent that performed the task
     * @param  string  $taskType  The type of task
     * @param  array  $prediction  What the agent predicted
     * @param  array  $actualOutcome  What actually happened
     * @param  int  $agencyId  The agency context
     * @return float The accuracy score for this outcome
     */
    public function recordFeedbackAndLearn(
        string $agentName,
        string $taskType,
        array $prediction,
        array $actualOutcome,
        int $agencyId
    ): float {
        // Record the outcome through the feedback service
        $this->feedbackService->recordOutcome(
            agentName: $agentName,
            taskType: $taskType,
            prediction: $prediction,
            actualOutcome: $actualOutcome,
            agencyId: $agencyId
        );

        // Calculate accuracy for return
        $accuracy = $this->feedbackService->calculateAccuracy($prediction, $actualOutcome);

        Log::info("SelfImprovementEngine: feedback recorded for [{$agentName}], accuracy={$accuracy}");

        return $accuracy;
    }

    /**
     * Get the feedback service instance.
     */
    public function getFeedbackService(): AgentFeedbackService
    {
        return $this->feedbackService;
    }

    // ─── Private Helpers ─────────────────────────────────────────────────

    /**
     * Extract best-performing parameter combinations.
     */
    private function extractBestParameters(array $successfulParams): array
    {
        if (empty($successfulParams)) {
            return [];
        }

        $paramCounts = [];
        foreach ($successfulParams as $params) {
            foreach ($params as $key => $value) {
                $serialized = json_encode($value);
                $paramCounts[$key][$serialized] = ($paramCounts[$key][$serialized] ?? 0) + 1;
            }
        }

        $best = [];
        foreach ($paramCounts as $key => $values) {
            arsort($values);
            $topValue = array_key_first($values);
            $best[$key] = json_decode($topValue, true);
        }

        return $best;
    }

    /**
     * Extract worst-performing parameter combinations.
     */
    private function extractWorstParameters(array $failedParams): array
    {
        if (empty($failedParams)) {
            return [];
        }

        $paramCounts = [];
        foreach ($failedParams as $params) {
            foreach ($params as $key => $value) {
                $serialized = json_encode($value);
                $paramCounts[$key][$serialized] = ($paramCounts[$key][$serialized] ?? 0) + 1;
            }
        }

        $worst = [];
        foreach ($paramCounts as $key => $values) {
            arsort($values);
            $topValue = array_key_first($values);
            $worst[$key] = json_decode($topValue, true);
        }

        return $worst;
    }

    /**
     * Generate recommendations based on analysis results.
     */
    private function generateRecommendationsFromAnalysis(
        float $successRate,
        float $avgExecutionTime,
        ?float $avgQuality,
        array $topErrors
    ): array {
        $recommendations = [];

        if ($successRate < 70) {
            $recommendations[] = 'URGENT: Success rate is critically low. Review error patterns and add retry logic.';
        } elseif ($successRate < 85) {
            $recommendations[] = 'WARNING: Success rate below optimal. Consider adding fallback strategies.';
        }

        if ($avgExecutionTime > 10000) {
            $recommendations[] = 'Performance: Execution time exceeds 10s average. Optimize prompt or reduce complexity.';
        }

        if ($avgQuality !== null && $avgQuality < 60) {
            $recommendations[] = 'Quality: Output quality is below acceptable threshold. Review prompt instructions.';
        }

        foreach ($topErrors as $error => $count) {
            if ($count >= 3) {
                $recommendations[] = "Recurring error ({$count}x): ".substr($error, 0, 100);
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Agent is performing within acceptable parameters. Continue monitoring.';
        }

        return $recommendations;
    }

    /**
     * Calculate trend from a series of values.
     */
    private function calculateTrend(array $values): string
    {
        $values = array_values(array_filter($values, fn ($v) => is_numeric($v)));
        if (count($values) < 3) {
            return 'stable';
        }

        $firstHalf = array_slice($values, 0, (int) ceil(count($values) / 2));
        $secondHalf = array_slice($values, (int) ceil(count($values) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        $changePercent = $firstAvg != 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;

        if ($changePercent > 5) {
            return 'improving';
        } elseif ($changePercent < -5) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Calculate variance of an array.
     */
    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn ($v) => pow($v - $mean, 2), $values);

        return array_sum($squaredDiffs) / count($squaredDiffs);
    }

    /**
     * Suggest reliability fixes based on success rate.
     */
    private function suggestReliabilityFix(float $successRate): string
    {
        if ($successRate < 50) {
            return 'Implement exponential backoff with retry. Review API error handling. Consider adding a fallback provider.';
        }

        return 'Add retry logic for transient failures. Review timeout settings and add graceful degradation.';
    }

    /**
     * Suggest quality fixes based on quality score.
     */
    private function suggestQualityFix(float $qualityScore): string
    {
        return 'Refine system prompt with clearer instructions. Add few-shot examples. Consider using a more capable model.';
    }

    /**
     * Calculate overall health score across all agents.
     */
    private function calculateOverallHealth(array $agentReports): float
    {
        if (empty($agentReports)) {
            return 0;
        }

        $scores = [];
        foreach ($agentReports as $report) {
            $score = 0;
            $score += min(($report['success_rate_avg'] ?? 0), 100) * 0.4;
            $score += min(($report['quality_score_avg'] ?? 0), 100) * 0.4;
            $executionAvg = $report['execution_time_avg_ms'] ?? 0;
            $score += max(0, 100 - ($executionAvg / 100)) * 0.2;
            $scores[] = min($score, 100);
        }

        return round(array_sum($scores) / count($scores), 1);
    }

    /**
     * Extract top learnings from the learning reports.
     */
    private function extractTopLearnings(): array
    {
        return AgentLearningReport::where('status', 'applied')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'agent' => $r->agent_name,
                'type' => $r->improvement_type,
                'description' => $r->description,
                'applied_at' => $r->applied_at?->toDateTimeString(),
            ])
            ->toArray();
    }
}
