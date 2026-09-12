<?php

namespace App\Console\Commands;

use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\SelfImprovementEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AgentImprovementCommand extends Command
{
    protected $signature = 'agents:improve
                            {--agent= : Run improvement analysis for a specific agent}
                            {--auto-tune : Automatically adjust agent parameters based on analysis}
                            {--report : Show the full learning report}
                            {--days=30 : Number of days of performance data to analyze}';

    protected $description = 'Run self-improvement analysis on AI agents';

    public function handle(
        AgentOrchestrator $orchestrator,
        SelfImprovementEngine $engine,
    ): int {
        $this->info('═══════════════════════════════════════════════');
        $this->info('  AI AGENT SELF-IMPROVEMENT ENGINE');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        $agentName = $this->option('agent');
        $autoTune = $this->option('auto-tune');
        $showReport = $this->option('report');
        $days = (int) $this->option('days');

        // Show learning report if requested
        if ($showReport) {
            $this->displayLearningReport($engine);

            return self::SUCCESS;
        }

        // Get agents to analyze
        if ($agentName) {
            if (! $orchestrator->hasAgent($agentName)) {
                $this->error("Agent [{$agentName}] not found.");
                $this->line('Available agents: '.implode(', ', $orchestrator->getAgentNames()));

                return self::FAILURE;
            }
            $agents = [$agentName => $orchestrator->getAgent($agentName)];
        } else {
            $agents = $orchestrator->getAllAgents();
        }

        if ($agents->isEmpty()) {
            $this->warn('No agents registered. Register agents in the AgentOrchestrator first.');

            return self::SUCCESS;
        }

        $this->info("Analyzing {$agents->count()} agent(s) over the last {$days} days...");
        $this->newLine();

        $totalImprovements = 0;
        $totalTuned = 0;

        foreach ($agents as $name => $agent) {
            $this->info("─── Agent: {$name} ───");
            $this->line("  Category: {$agent->getCategory()}");
            $this->line("  Description: {$agent->getDescription()}");
            $this->newLine();

            // Generate improvements
            $improvements = $engine->generateImprovements($agent);

            if (empty($improvements)) {
                $this->line('  ✓ No improvements needed. Agent is performing well.');
            } else {
                $this->line('  Suggested improvements:');
                foreach ($improvements as $improvement) {
                    $icon = match ($improvement['type']) {
                        'critical' => '🔴',
                        'warning' => '🟡',
                        'info' => '🔵',
                        default => '⚪',
                    };
                    $this->line("    {$icon} [{$improvement['category']}] {$improvement['message']}");
                    $this->line("       → {$improvement['suggestion']}");
                }
                $totalImprovements += count($improvements);
            }

            // Auto-tune if requested
            if ($autoTune) {
                $this->newLine();
                $this->line('  Applying auto-tuning...');
                try {
                    $engine->autoTune($agent);
                    $totalTuned++;
                    $this->line('  ✓ Auto-tuning complete.');
                } catch (\Exception $e) {
                    $this->error("  ✗ Auto-tuning failed: {$e->getMessage()}");
                    Log::error("Auto-tune failed for agent [{$name}]: {$e->getMessage()}");
                }
            }

            $this->newLine();
        }

        // Summary
        $this->info('═══════════════════════════════════════════════');
        $this->info('  SUMMARY');
        $this->info('═══════════════════════════════════════════════');
        $this->line("  Agents analyzed: {$agents->count()}");
        $this->line("  Improvements suggested: {$totalImprovements}");
        if ($autoTune) {
            $this->line("  Agents tuned: {$totalTuned}");
        }
        $this->newLine();

        // Show learning report
        $this->displayLearningReport($engine);

        return self::SUCCESS;
    }

    /**
     * Display the learning report.
     */
    private function displayLearningReport(SelfImprovementEngine $engine): void
    {
        $report = $engine->getLearningReport();

        $this->info('═══════════════════════════════════════════════');
        $this->info('  LEARNING REPORT');
        $this->info('═══════════════════════════════════════════════');
        $this->line("  Generated at: {$report['generated_at']}");
        $this->line("  Agents analyzed: {$report['agents_analyzed']}");
        $this->newLine();

        if (! empty($report['agent_reports'])) {
            $this->line('  Agent Performance:');
            foreach ($report['agent_reports'] as $agentReport) {
                $successRate = $agentReport['success_rate_avg'] ?? 'N/A';
                $quality = $agentReport['quality_score_avg'] ?? 'N/A';
                $trend = $agentReport['trend']['success'] ?? 'stable';
                $trendIcon = match ($trend) {
                    'improving' => '📈',
                    'declining' => '📉',
                    default => '➡️',
                };
                $this->line("    • {$agentReport['agent_name']}: success={$successRate}%, quality={$quality} {$trendIcon} ({$trend})");
            }
        }

        $this->newLine();
        $this->line("  Overall health score: {$report['summary']['overall_health_score']}/100");
        $this->line("  Pending improvements: {$report['summary']['total_pending_improvements']}");
        $this->line("  Applied improvements: {$report['summary']['total_applied_improvements']}");

        if (! empty($report['top_learnings'])) {
            $this->newLine();
            $this->line('  Top learnings:');
            foreach (array_slice($report['top_learnings'], 0, 5) as $learning) {
                $this->line("    • [{$learning['type']}] {$learning['description']}");
            }
        }

        $this->newLine();
    }
}
