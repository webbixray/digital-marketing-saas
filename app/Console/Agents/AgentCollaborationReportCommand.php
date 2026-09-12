<?php

namespace App\Console\Agents;

use App\Models\AgentSharedKnowledge;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\Collaboration\SharedKnowledgeBase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AgentCollaborationReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:collaborate-report
                            {agency_id? : The agency ID to generate report for}
                            {--category= : Filter by specific category}
                            {--min-confidence=0.0 : Minimum confidence threshold}
                            {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate agent collaboration and shared knowledge report with DB-backed insights';

    /**
     * Execute the console command.
     */
    public function handle(
        AgentOrchestrator $orchestrator,
        SharedKnowledgeBase $knowledgeBase,
    ): int {
        $agencyId = (int) ($this->argument('agency_id') ?? 0);
        $category = $this->option('category');
        $minConfidence = (float) $this->option('min-confidence');
        $format = $this->option('format');

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('       Agent Collaboration & Shared Knowledge Report       ');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // 1. Collaboration Stats from DB
        $this->info('📊 COLLABORATION STATISTICS (Database-Backed)');
        $this->line('───────────────────────────────────────────────────────────');
        $stats = $knowledgeBase->getCollaborationStats($agencyId);
        $this->line("Total insights: {$stats['total_insights']}");
        $this->line("Unique agents: {$stats['unique_agents']}");
        $this->line("Unique categories: {$stats['unique_categories']}");
        $this->line("Cross-agent categories: {$stats['cross_agent_categories']}");
        $this->line('Average confidence: '.round($stats['avg_confidence'] * 100, 1).'%');
        $this->newLine();

        // 2. Top Contributing Agents
        if (! empty($stats['top_agents'])) {
            $this->info('🏆 TOP CONTRIBUTING AGENTS');
            $this->line('───────────────────────────────────────────────────────────');
            foreach ($stats['top_agents'] as $agent) {
                $this->line("  - {$agent['agent']}: {$agent['insight_count']} insights");
            }
            $this->newLine();
        }

        // 3. Category Breakdown
        if (! empty($stats['categories'])) {
            $this->info('📁 CATEGORY BREAKDOWN');
            $this->line('───────────────────────────────────────────────────────────');
            foreach ($stats['categories'] as $cat) {
                $this->line("  - {$cat['category']}: {$cat['insight_count']} insights from {$cat['agent_count']} agents");
            }
            $this->newLine();
        }

        // 4. Cross-Agent Patterns
        $this->info('🔄 CROSS-AGENT PATTERNS');
        $this->line('───────────────────────────────────────────────────────────');
        $patterns = $knowledgeBase->getCrossAgentPatterns($agencyId);
        $this->line('Cross-agent pattern categories: '.count($patterns));

        foreach ($patterns as $cat => $pattern) {
            $this->line("  - Category: {$cat} | Agents: {$pattern['agent_count']} | Insights: {$pattern['insight_count']}");
        }
        $this->newLine();

        // 5. Best Practices
        $this->info('⭐ BEST PRACTICES');
        $this->line('───────────────────────────────────────────────────────────');
        $bestPractices = $knowledgeBase->getBestPractices($agencyId, $category ?? 'general');
        $this->line('Best practice entries: '.count($bestPractices));

        foreach ($bestPractices as $practice) {
            $this->line("  - Agent: {$practice['agent']} | Practices: {$practice['practice_count']} | Avg confidence: ".round($practice['avg_confidence'] * 100, 1).'%');
        }
        $this->newLine();

        // 6. High Confidence Insights (if threshold set)
        if ($minConfidence > 0) {
            $this->info('🔒 HIGH CONFIDENCE INSIGHTS (>= '.round($minConfidence * 100).'%)');
            $this->line('───────────────────────────────────────────────────────────');

            $targetCategories = $category ? [$category] : array_column($stats['categories'], 'category');

            foreach ($targetCategories as $cat) {
                $highConfInsights = $knowledgeBase->getHighConfidenceInsights($agencyId, $cat, $minConfidence);
                if (! empty($highConfInsights)) {
                    $this->line("  Category: {$cat}");
                    foreach ($highConfInsights as $insight) {
                        $this->line("    - [{$insight['from_agent']}] {$insight['insight']} (confidence: ".round($insight['confidence'] * 100, 1).'%)');
                    }
                }
            }
            $this->newLine();
        }

        // 7. Agent Capabilities
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

        // 8. Recent Insights (last 10)
        $this->info('🕐 RECENT INSIGHTS');
        $this->line('───────────────────────────────────────────────────────────');
        $recentQuery = AgentSharedKnowledge::byAgency($agencyId)->recent(10);
        if ($category) {
            $recentQuery->byCategory($category);
        }
        $recent = $recentQuery->get();

        foreach ($recent as $row) {
            $this->line("  - [{$row->from_agent}] ({$row->category}) {$row->insight} (conf: ".round((float) $row->confidence * 100, 1).'%)');
        }
        $this->newLine();

        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('                    Report Complete                         ');
        $this->info('═══════════════════════════════════════════════════════════');

        return self::SUCCESS;
    }
}
