<?php

namespace App\Console\Commands;

use App\Models\AgentLearningReport;
use App\Models\AgentPerformanceLog;
use App\Services\AI\Agent\AgentFeedbackService;
use App\Services\AI\Agent\AgentMemory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AgentLearningReportCommand extends Command
{
    protected $signature = 'agents:learning-report
                            {--agent= : Show report for a specific agent}
                            {--days=30 : Number of days of data to analyze}
                            {--patterns : Show learned patterns}
                            {--accuracy : Show prediction accuracy trends}
                            {--export : Export report as JSON}';

    protected $description = 'Show AI agent learning progress and feedback loop results';

    public function handle(
        AgentMemory $memory,
        AgentFeedbackService $feedbackService,
    ): int {
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  AI AGENT LEARNING PROGRESS REPORT');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $agentName = $this->option('agent');
        $days = (int) $this->option('days');
        $showPatterns = $this->option('patterns');
        $showAccuracy = $this->option('accuracy');
        $export = $this->option('export');

        $report = [];

        // Section 1: Learning Overview
        $report['overview'] = $this->getLearningOverview($days);
        $this->displayOverview($report['overview']);

        // Section 2: Agent-specific details
        if ($agentName) {
            $report['agent_details'] = $this->getAgentDetails($agentName, $days);
            $this->displayAgentDetails($report['agent_details']);
        }

        // Section 3: Learned Patterns
        if ($showPatterns || $showAccuracy) {
            if ($showPatterns) {
                $report['learned_patterns'] = $this->getLearnedPatternsReport($memory, $agentName);
                $this->displayLearnedPatterns($report['learned_patterns']);
            }

            if ($showAccuracy) {
                $report['accuracy_trends'] = $this->getAccuracyTrends($agentName, $days);
                $this->displayAccuracyTrends($report['accuracy_trends']);
            }
        } else {
            // Show both by default
            $report['learned_patterns'] = $this->getLearnedPatternsReport($memory, $agentName);
            $report['accuracy_trends'] = $this->getAccuracyTrends($agentName, $days);
            $this->displayLearnedPatterns($report['learned_patterns']);
            $this->displayAccuracyTrends($report['accuracy_trends']);
        }

        // Section 4: Recent Feedback Outcomes
        $report['recent_outcomes'] = $this->getRecentOutcomes($agentName, $days);
        $this->displayRecentOutcomes($report['recent_outcomes']);

        // Section 5: Recommendations
        $report['recommendations'] = $this->generateRecommendations($report);
        $this->displayRecommendations($report['recommendations']);

        // Export if requested
        if ($export) {
            $this->exportReport($report);
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('  Report generated: '.now()->toDateTimeString());
        $this->info('═══════════════════════════════════════════════════════');

        return self::SUCCESS;
    }

    /**
     * Get high-level learning overview.
     */
    private function getLearningOverview(int $days): array
    {
        $since = now()->subDays($days);

        $totalOutcomes = AgentPerformanceLog::where('recorded_at', '>=', $since)->count();
        $totalAgents = AgentPerformanceLog::where('recorded_at', '>=', $since)
            ->distinct('agent_name')
            ->count('agent_name');

        $avgAccuracy = AgentPerformanceLog::where('metric_type', 'prediction_accuracy')
            ->where('recorded_at', '>=', $since)
            ->avg('metric_value') ?? 0;

        $pendingImprovements = AgentLearningReport::where('status', 'pending')
            ->where('created_at', '>=', $since)
            ->count();

        $appliedImprovements = AgentLearningReport::where('status', 'applied')
            ->where('created_at', '>=', $since)
            ->count();

        $totalPatterns = AgentPerformanceLog::where('metric_type', 'prediction_accuracy')
            ->where('recorded_at', '>=', $since)
            ->count();

        return [
            'period_days' => $days,
            'total_outcomes_recorded' => $totalOutcomes,
            'active_agents' => $totalAgents,
            'average_accuracy' => round($avgAccuracy * 100, 2),
            'pending_improvements' => $pendingImprovements,
            'applied_improvements' => $appliedImprovements,
            'total_patterns_evaluated' => $totalPatterns,
        ];
    }

    /**
     * Get detailed performance data for a specific agent.
     */
    private function getAgentDetails(string $agentName, int $days): array
    {
        $logs = AgentPerformanceLog::forAgent($agentName)
            ->recent($days)
            ->orderBy('recorded_at', 'desc')
            ->get();

        $accuracyLogs = $logs->where('metric_type', 'prediction_accuracy');
        $avgAccuracy = $accuracyLogs->isNotEmpty() ? $accuracyLogs->avg('metric_value') : 0;

        $learningReports = AgentLearningReport::forAgent($agentName)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return [
            'agent_name' => $agentName,
            'total_records' => $logs->count(),
            'average_accuracy' => round($avgAccuracy * 100, 2),
            'recent_learning_reports' => $learningReports->toArray(),
            'accuracy_trend' => $this->calculateAgentTrend($accuracyLogs->pluck('metric_value')->toArray()),
        ];
    }

    /**
     * Get learned patterns report from AgentMemory.
     */
    private function getLearnedPatternsReport(AgentMemory $memory, ?string $agentName): array
    {
        $patterns = $memory->getLearnedPatterns(
            agentName: $agentName,
            minConfidence: 0.5
        );

        return [
            'total_patterns' => count($patterns),
            'patterns' => array_slice($patterns, 0, 20),
        ];
    }

    /**
     * Get accuracy trends from performance logs.
     */
    private function getAccuracyTrends(?string $agentName, int $days): array
    {
        $query = AgentPerformanceLog::where('metric_type', 'prediction_accuracy')
            ->recent($days)
            ->orderBy('recorded_at', 'desc');

        if ($agentName) {
            $query->forAgent($agentName);
        }

        $accuracies = $query->limit(50)->get();

        $trend = 'stable';
        if ($accuracies->count() >= 5) {
            $values = $accuracies->pluck('metric_value')->toArray();
            $trend = $this->calculateTrend($values);
        }

        return [
            'data_points' => $accuracies->count(),
            'latest_accuracy' => $accuracies->isNotEmpty() ? round($accuracies->first()->metric_value * 100, 2) : null,
            'average_accuracy' => $accuracies->isNotEmpty() ? round($accuracies->avg('metric_value') * 100, 2) : null,
            'trend' => $trend,
            'recent_values' => $accuracies->take(10)->map(fn ($log) => [
                'accuracy' => round($log->metric_value * 100, 2),
                'recorded_at' => $log->recorded_at->toDateTimeString(),
            ])->toArray(),
        ];
    }

    /**
     * Get recent feedback outcomes.
     */
    private function getRecentOutcomes(?string $agentName, int $days): array
    {
        $query = AgentPerformanceLog::where('metric_type', 'prediction_accuracy')
            ->recent($days)
            ->orderBy('recorded_at', 'desc')
            ->limit(10);

        if ($agentName) {
            $query->forAgent($agentName);
        }

        $outcomes = $query->get();

        return [
            'total_recent' => $outcomes->count(),
            'outcomes' => $outcomes->map(fn ($log) => [
                'agent' => $log->agent_name,
                'accuracy' => round($log->metric_value * 100, 2),
                'task_type' => $log->metadata['task_type'] ?? 'unknown',
                'recorded_at' => $log->recorded_at->toDateTimeString(),
                'delta' => $log->metadata['delta'] ?? null,
            ])->toArray(),
        ];
    }

    /**
     * Generate actionable recommendations based on the report data.
     */
    private function generateRecommendations(array $report): array
    {
        $recommendations = [];

        $overview = $report['overview'];

        if ($overview['average_accuracy'] < 60) {
            $recommendations[] = [
                'priority' => 'critical',
                'message' => 'Average prediction accuracy is below 60%. Review agent prompts and training data.',
            ];
        } elseif ($overview['average_accuracy'] < 75) {
            $recommendations[] = [
                'priority' => 'warning',
                'message' => 'Prediction accuracy could be improved. Consider adding more training examples.',
            ];
        }

        if ($overview['pending_improvements'] > 10) {
            $recommendations[] = [
                'priority' => 'high',
                'message' => "There are {$overview['pending_improvements']} pending improvements. Run agents:improve --auto-tune to apply.",
            ];
        }

        if ($overview['total_outcomes_recorded'] < 10) {
            $recommendations[] = [
                'priority' => 'info',
                'message' => 'Limited feedback data. Encourage agents to record more outcomes for better learning.',
            ];
        }

        $accuracyTrend = $report['accuracy_trends']['trend'] ?? 'stable';
        if ($accuracyTrend === 'declining') {
            $recommendations[] = [
                'priority' => 'critical',
                'message' => 'Accuracy trend is declining. Investigate recent changes to agent configurations.',
            ];
        } elseif ($accuracyTrend === 'improving') {
            $recommendations[] = [
                'priority' => 'info',
                'message' => 'Accuracy is improving. Current learning strategies are effective.',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'info',
                'message' => 'All agents are performing within acceptable parameters.',
            ];
        }

        return $recommendations;
    }

    // ─── Display Methods ───────────────────────────────────────────────

    private function displayOverview(array $overview): void
    {
        $this->info('─── Learning Overview ───');
        $this->line("  Period: last {$overview['period_days']} days");
        $this->line("  Outcomes recorded: {$overview['total_outcomes_recorded']}");
        $this->line("  Active agents: {$overview['active_agents']}");
        $this->line("  Average accuracy: {$overview['average_accuracy']}%");
        $this->line("  Pending improvements: {$overview['pending_improvements']}");
        $this->line("  Applied improvements: {$overview['applied_improvements']}");
        $this->newLine();
    }

    private function displayAgentDetails(array $details): void
    {
        $this->info("─── Agent: {$details['agent_name']} ───");
        $this->line("  Total records: {$details['total_records']}");
        $this->line("  Average accuracy: {$details['average_accuracy']}%");
        $this->line("  Trend: {$details['accuracy_trend']}");

        if (! empty($details['recent_learning_reports'])) {
            $this->line('  Recent learning reports:');
            foreach (array_slice($details['recent_learning_reports'], 0, 5) as $report) {
                $this->line("    • [{$report['improvement_type']}] {$report['description']}");
            }
        }
        $this->newLine();
    }

    private function displayLearnedPatterns(array $patternsReport): void
    {
        $this->info('─── Learned Patterns ───');
        $this->line("  Total patterns: {$patternsReport['total_patterns']}");

        if (! empty($patternsReport['patterns'])) {
            $this->line('  Top patterns:');
            foreach (array_slice($patternsReport['patterns'], 0, 5) as $pattern) {
                $confidence = round(($pattern['confidence'] ?? 0) * 100);
                $occurrences = $pattern['occurrence_count'] ?? 1;
                $this->line("    • [{$pattern['agent_name']}] confidence={$confidence}%, occurrences={$occurrences}");
                $this->line('      Pattern: '.Str::limit(json_encode($pattern['pattern']), 80));
            }
        }
        $this->newLine();
    }

    private function displayAccuracyTrends(array $trends): void
    {
        $this->info('─── Prediction Accuracy Trends ───');
        $this->line("  Data points: {$trends['data_points']}");
        $this->line('  Latest accuracy: '.($trends['latest_accuracy'] ?? 'N/A').'%');
        $this->line('  Average accuracy: '.($trends['average_accuracy'] ?? 'N/A').'%');

        $trendIcon = match ($trends['trend']) {
            'improving' => '📈',
            'declining' => '📉',
            default => '➡️',
        };
        $this->line("  Trend: {$trendIcon} {$trends['trend']}");

        if (! empty($trends['recent_values'])) {
            $this->line('  Recent values:');
            foreach (array_slice($trends['recent_values'], 0, 5) as $value) {
                $this->line("    • {$value['accuracy']}% at {$value['recorded_at']}");
            }
        }
        $this->newLine();
    }

    private function displayRecentOutcomes(array $outcomes): void
    {
        $this->info('─── Recent Feedback Outcomes ───');
        $this->line("  Total recent: {$outcomes['total_recent']}");

        if (! empty($outcomes['outcomes'])) {
            foreach (array_slice($outcomes['outcomes'], 0, 5) as $outcome) {
                $this->line("    • [{$outcome['agent']}] {$outcome['accuracy']}% - {$outcome['task_type']}");
            }
        }
        $this->newLine();
    }

    private function displayRecommendations(array $recommendations): void
    {
        $this->info('─── Recommendations ───');
        foreach ($recommendations as $rec) {
            $icon = match ($rec['priority']) {
                'critical' => '🔴',
                'high' => '🟠',
                'warning' => '🟡',
                default => '🔵',
            };
            $this->line("  {$icon} [{$rec['priority']}] {$rec['message']}");
        }
        $this->newLine();
    }

    /**
     * Export report as JSON file.
     */
    private function exportReport(array $report): void
    {
        $filename = 'agent_learning_report_'.now()->format('Y-m-d_His').'.json';
        $path = storage_path("app/reports/{$filename}");

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
        $this->info("Report exported to: {$path}");
    }

    // ─── Helper Methods ────────────────────────────────────────────────

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
        }
        if ($changePercent < -5) {
            return 'declining';
        }

        return 'stable';
    }

    private function calculateAgentTrend(array $values): string
    {
        return $this->calculateTrend($values);
    }
}
