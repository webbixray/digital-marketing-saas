<?php

namespace App\Console\Commands;

use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\Collaboration\SharedKnowledgeBase;
use Illuminate\Console\Command;

class AgentCollaborationReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:collaborate-report {agency_id? : The agency ID to generate report for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate agent collaboration and shared knowledge report';

    /**
     * Execute the console command.
     */
    public function handle(
        AgentOrchestrator $orchestrator,
        SharedKnowledgeBase $knowledgeBase,
    ): int {
        $agencyId = (int) ($this->argument('agency_id') ?? 0);

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('       Agent Collaboration & Shared Knowledge Report       ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // 1. Collaboration History
        $this->info('📊 COLLABORATION HISTORY');
        $this->line('───────────────────────────────────────────────────────────');
        $history = $orchestrator->getCollaborationHistory($agencyId);
        $this->line('Total collaborations: '.count($history));

        if (! empty($history)) {
            $this->newLine();
            $this->line('Recent collaborations:');
            foreach (array_slice($history, -5) as $entry) {
                $this->line("  - Agent: {$entry['agent_name']} | Task: {$entry['task_type']} | Success: ".($entry['success'] ? '✓' : '✗'));
            }
        }
        $this->newLine();

        // 2. Shared Knowledge Report
        $this->info('🧠 SHARED KNOWLEDGE REPORT');
        $this->line('───────────────────────────────────────────────────────────');
        $report = $orchestrator->getSharedKnowledgeReport($agencyId);
        $this->line("Total collaborations: {$report['total_collaborations']}");

        if (! empty($report['agent_collaboration_frequency'])) {
            $this->newLine();
            $this->line('Agent collaboration frequency:');
            foreach ($report['agent_collaboration_frequency'] as $agent => $count) {
                $this->line("  - {$agent}: {$count} collaborations");
            }
        }

        if ($report['most_collaborative_agent']) {
            $this->line("Most collaborative agent: {$report['most_collaborative_agent']}");
        }
        $this->newLine();

        // 3. Cross-Agent Patterns
        $this->info('🔄 CROSS-AGENT PATTERNS');
        $this->line('───────────────────────────────────────────────────────────');
        $patterns = $knowledgeBase->getCrossAgentPatterns($agencyId);
        $this->line('Cross-agent pattern categories: '.count($patterns));

        foreach ($patterns as $category => $pattern) {
            $this->line("  - Category: {$category} | Agents: {$pattern['agent_count']} | Insights: {$pattern['insight_count']}");
        }
        $this->newLine();

        // 4. Best Practices
        $this->info('⭐ BEST PRACTICES');
        $this->line('───────────────────────────────────────────────────────────');
        $bestPractices = $knowledgeBase->getBestPractices($agencyId, 'general');
        $this->line('Best practice entries: '.count($bestPractices));

        foreach ($bestPractices as $practice) {
            $this->line("  - Agent: {$practice['agent']} | Practices: {$practice['practice_count']}");
        }
        $this->newLine();

        // 5. Agent Capabilities
        $this->info('🤖 AGENT CAPABILITIES');
        $this->line('───────────────────────────────────────────────────────────');
        $agents = $orchestrator->getRegisteredAgents();
        $this->line('Registered agents: '.count($agents));

        foreach ($agents as $name) {
            $agent = $orchestrator->getAgent($name);
            if ($agent) {
                $types = implode(', ', $agent->getSupportedTaskTypes());
                $this->line("  - {$name}: [{$types}] (success: ".round($agent->getSuccessRate() * 100, 1).'%)');
            }
        }
        $this->newLine();

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    Report Complete                         ');
        $this->info('═══════════════════════════════════════════════════════════');

        return self::SUCCESS;
    }
}
