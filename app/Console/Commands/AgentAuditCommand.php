<?php

namespace App\Console\Commands;

use App\Models\SocialPost;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\SecurityAuditAgent;
use App\Services\AI\Agent\SelfImprovementEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AgentAuditCommand extends Command
{
    protected $signature = 'agents:audit
                            {--auto-fix : Automatically fix issues where possible}
                            {--agent= : Run a specific audit agent}
                            {--severity= : Minimum severity to report (low, medium, high, critical)}
                            {--category= : Filter by category}';

    protected $description = 'Run security and audit agents to find issues';

    public function handle(
        AgentOrchestrator $orchestrator,
        SelfImprovementEngine $engine,
    ): int {
        $this->info('═══════════════════════════════════════════════');
        $this->info('  AI AGENT AUDIT ENGINE');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        $autoFix = $this->option('auto-fix');
        $agentName = $this->option('agent');
        $severity = $this->option('severity');
        $category = $this->option('category');

        // Register the security audit agent if not already registered
        if (! $orchestrator->hasAgent('security_auditor')) {
            $orchestrator->registerAgent(new SecurityAuditAgent);
        }

        // Get agents to run
        if ($agentName) {
            if (! $orchestrator->hasAgent($agentName)) {
                $this->error("Agent [{$agentName}] not found.");
                $this->line('Available agents: '.implode(', ', $orchestrator->getAgentNames()));

                return self::FAILURE;
            }
            $agents = [$agentName => $orchestrator->getAgent($agentName)];
        } else {
            $agents = $orchestrator->getAgentsByCategory('security');
            if ($agents->isEmpty()) {
                $agents = $orchestrator->getAllAgents();
            }
        }

        if ($agents->isEmpty()) {
            $this->warn('No audit agents available.');

            return self::SUCCESS;
        }

        $this->info('Running '.$agents->count().' audit agent(s)...');
        if ($autoFix) {
            $this->warn('Auto-fix mode ENABLED — fixable issues will be resolved automatically.');
        }
        $this->newLine();

        $allFindings = [];
        $totalFixed = 0;
        $totalFailed = 0;

        foreach ($agents as $name => $agent) {
            $this->info("─── Agent: {$name} ───");
            $this->line("  Category: {$agent->getCategory()}");
            $this->newLine();

            // Execute the agent
            try {
                $result = $agent->execute([]);

                if (! ($result['success'] ?? false)) {
                    $this->error('  ✗ Agent execution failed.');
                    $totalFailed++;

                    continue;
                }

                $findings = $result['findings'] ?? [];
                $this->line("  Total findings: {$result['total_findings']}");
                $this->line("  Critical: {$result['critical_count']} | High: {$result['high_count']} | Medium: {$result['medium_count']} | Low: {$result['low_count']}");

                // Filter by severity if specified
                if ($severity) {
                    $severityOrder = ['low' => 0, 'medium' => 1, 'high' => 2, 'critical' => 3];
                    $minLevel = $severityOrder[$severity] ?? 0;
                    $findings = array_filter($findings, fn ($f) => ($severityOrder[$f['severity']] ?? 0) >= $minLevel);
                }

                // Filter by category if specified
                if ($category) {
                    $findings = array_filter($findings, fn ($f) => strcasecmp($f['category'], $category) === 0);
                }

                // Sort findings by severity (critical first)
                $severityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
                usort($findings, fn ($a, $b) => ($severityOrder[$a['severity']] ?? 4) <=> ($severityOrder[$b['severity']] ?? 4));

                // Display findings
                if (empty($findings)) {
                    $this->line('  ✓ No findings matching criteria.');
                } else {
                    $this->newLine();
                    $this->line('  Findings:');
                    $this->line('  '.str_repeat('─', 50));

                    foreach ($findings as $index => $finding) {
                        $icon = match ($finding['severity']) {
                            'critical' => '🔴',
                            'high' => '🟠',
                            'medium' => '🟡',
                            'low' => '🔵',
                            default => '⚪',
                        };

                        $this->line("  {$icon} [".strtoupper($finding['severity'])."] {$finding['title']}");
                        $this->line("     Category: {$finding['category']}");
                        $this->line("     {$finding['description']}");
                        $this->line("     Fix: {$finding['fix']}");

                        // Auto-fix if requested and the finding is auto-fixable
                        if ($autoFix && ($finding['auto_fixable'] ?? false)) {
                            $this->line('     🔧 Attempting auto-fix...');
                            $fixed = $this->attemptAutoFix($finding);
                            if ($fixed) {
                                $this->line('     ✓ Auto-fixed successfully.');
                                $totalFixed++;
                            } else {
                                $this->line('     ✗ Auto-fix failed.');
                                $totalFailed++;
                            }
                        }

                        $this->line('  '.str_repeat('─', 50));
                    }
                }

                $allFindings = array_merge($allFindings, $findings);

            } catch (\Exception $e) {
                $this->error("  ✗ Exception: {$e->getMessage()}");
                Log::error("Audit agent [{$name}] exception: {$e->getMessage()}");
                $totalFailed++;
            }

            $this->newLine();
        }

        // Summary
        $this->info('═══════════════════════════════════════════════');
        $this->info('  AUDIT SUMMARY');
        $this->info('═══════════════════════════════════════════════');
        $this->line('  Total findings: '.count($allFindings));
        $this->line('  Critical: '.count(array_filter($allFindings, fn ($f) => $f['severity'] === 'critical')));
        $this->line('  High: '.count(array_filter($allFindings, fn ($f) => $f['severity'] === 'high')));
        $this->line('  Medium: '.count(array_filter($allFindings, fn ($f) => $f['severity'] === 'medium')));
        $this->line('  Low: '.count(array_filter($allFindings, fn ($f) => $f['severity'] === 'low')));
        $this->line("  Auto-fixed: {$totalFixed}");
        if ($totalFailed > 0) {
            $this->line("  Failed fixes: {$totalFailed}");
        }
        $this->newLine();

        // Health assessment
        $criticalCount = count(array_filter($allFindings, fn ($f) => $f['severity'] === 'critical'));
        if ($criticalCount > 0) {
            $this->error("  ⚠ {$criticalCount} CRITICAL issue(s) require immediate attention!");
        } elseif (count($allFindings) === 0) {
            $this->info('  ✓ All systems pass audit checks.');
        } else {
            $this->warn('  Some issues found. Review and address them soon.');
        }

        $this->newLine();

        // Record performance for the security agent
        try {
            $securityAgent = $orchestrator->getAgent('security_auditor');
            $engine->generateImprovements($securityAgent);
        } catch (\Exception $e) {
            // Silently skip if not available
        }

        return $criticalCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Attempt to auto-fix a finding.
     */
    private function attemptAutoFix(array $finding): bool
    {
        try {
            $title = $finding['title'] ?? '';

            // Handle stale scheduled posts cleanup
            if (str_contains($title, 'Stale scheduled posts')) {
                $staleCount = SocialPost::where('status', 'scheduled')
                    ->where('scheduled_at', '<', now()->subDays(7))
                    ->update(['status' => 'draft']);
                Log::info("Auto-fixed: {$staleCount} stale scheduled posts reverted to draft.");

                return true;
            }

            // Debug mode and app key fixes require manual intervention (env file changes)
            // but we can log the suggestion
            if (str_contains($title, 'Debug mode') || str_contains($title, 'application key')) {
                Log::warning("Auto-fix unavailable for: {$title} — requires manual .env configuration change.");

                return false;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Auto-fix failed for finding: {$finding['title']} — {$e->getMessage()}");

            return false;
        }
    }
}
