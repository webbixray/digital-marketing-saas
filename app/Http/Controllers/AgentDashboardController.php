<?php

namespace App\Http\Controllers;

use App\Models\AgentCostLog;
use App\Models\AgentLearningReport;
use App\Models\AgentPerformanceLog;
use App\Models\AgentSharedKnowledge;
use App\Models\AgentWorkflowExecution;
use App\Services\AI\Agent\AgentCostTracker;
use App\Services\AI\Agent\AgentHealthMonitor;
use App\Services\AI\Agent\AgentOrchestrator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AgentDashboardController extends Controller
{
    public function __construct(
        private AgentOrchestrator $orchestrator,
        private AgentHealthMonitor $healthMonitor,
        private AgentCostTracker $costTracker,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * AI Agent Orchestration Dashboard — v6.0
     * Shows real-time agent status, cost tracking, learning progress,
     * workflow execution log, and collaboration graph.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $agencyId = $user->agency_id;

            // ─── Agent Status Cards ───────────────────────────────────────
            $agentStats = $this->orchestrator->getAgentStats();
            $agents = [];
            $statusCounts = ['idle' => 0, 'running' => 0, 'error' => 0];

            foreach ($agentStats as $name => $stat) {
                $agent = $this->orchestrator->getAgent($name);
                $health = $agent ? $this->healthMonitor->checkAgentHealth($agent) : null;

                // Determine real-time status from recent workflow executions
                $latestExecution = AgentWorkflowExecution::where('workflow_name', $name)
                    ->orderByDesc('started_at')
                    ->first();

                $status = 'idle';
                if ($latestExecution) {
                    $status = match ($latestExecution->status) {
                        'running', 'pending' => 'running',
                        'failed' => 'error',
                        default => 'idle',
                    };
                }

                $statusCounts[$status]++;

                $agents[$name] = array_merge($stat, [
                    'status' => $status,
                    'health_status' => $health['status'] ?? 'unknown',
                    'last_run' => $latestExecution?->started_at
                        ? Carbon::parse($latestExecution->started_at)->diffForHumans()
                        : 'Never',
                    'description' => $this->getAgentDescription($name),
                    'category' => $this->getAgentCategory($name),
                ]);
            }

            // ─── Cost Tracking (Daily / Monthly) ──────────────────────────
            $costTrendDaily = $this->costTracker->getCostTrend($agencyId, 14);
            $costByAgent = $this->costTracker->getCostByAgent($agencyId);
            $monthlyTotal = $this->costTracker->getMonthlyCost($agencyId);
            $budgetLimit = $this->costTracker->getBudgetLimit($agencyId);
            $budgetRemaining = $this->costTracker->getRemainingBudget($agencyId);

            // ─── Learning Progress ─────────────────────────────────────────
            $learningReports = AgentLearningReport::orderByDesc('created_at')
                ->take(20)
                ->get();

            $learningStats = [
                'total' => AgentLearningReport::count(),
                'applied' => AgentLearningReport::applied()->count(),
                'pending' => AgentLearningReport::pending()->count(),
                'by_type' => AgentLearningReport::selectRaw('improvement_type, COUNT(*) as count')
                    ->groupBy('improvement_type')
                    ->pluck('count', 'improvement_type')
                    ->toArray(),
            ];

            // Per-agent learning progress percentage
            $agentLearningProgress = [];
            foreach ($agents as $name => $agentData) {
                $total = AgentLearningReport::where('agent_name', $name)->count();
                $applied = AgentLearningReport::where('agent_name', $name)->applied()->count();
                $agentLearningProgress[$name] = [
                    'total' => $total,
                    'applied' => $applied,
                    'percent' => $total > 0 ? round(($applied / $total) * 100) : 0,
                ];
            }

            // ─── Workflow Execution Log ────────────────────────────────────
            $executions = AgentWorkflowExecution::where('agency_id', $agencyId)
                ->orderByDesc('started_at')
                ->take(25)
                ->get();

            // ─── Collaboration Graph Data ──────────────────────────────────
            $sharedKnowledge = AgentSharedKnowledge::byAgency($agencyId)
                ->recent(30)
                ->get();

            $collaborationEdges = [];
            $knowledgeByCategory = $sharedKnowledge->groupBy('category');
            foreach ($knowledgeByCategory as $category => $items) {
                $agentNames = $items->pluck('from_agent')->unique()->values()->toArray();
                if (count($agentNames) > 1) {
                    $collaborationEdges[] = [
                        'category' => $category,
                        'agents' => $agentNames,
                        'insight_count' => $items->count(),
                        'avg_confidence' => round($items->avg('confidence') * 100, 1),
                    ];
                }
            }

            // ─── Performance Metrics ───────────────────────────────────────
            $performanceSummary = AgentPerformanceLog::whereIn('agent_name', array_keys($agents))
                ->recent(7)
                ->selectRaw('agent_name, metric_type, AVG(metric_value) as avg_value, COUNT(*) as samples')
                ->groupBy('agent_name', 'metric_type')
                ->get()
                ->groupBy('agent_name');

            return view('agents.dashboard.index', compact(
                'agents',
                'statusCounts',
                'costTrendDaily',
                'costByAgent',
                'monthlyTotal',
                'budgetLimit',
                'budgetRemaining',
                'learningReports',
                'learningStats',
                'agentLearningProgress',
                'executions',
                'collaborationEdges',
                'performanceSummary',
            ));
        } catch (\Exception $e) {
            Log::error('AgentDashboardController: failed to load dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to load agent orchestration dashboard.');
        }
    }

    /**
     * Get a human-readable description for an agent.
     */
    private function getAgentDescription(string $name): string
    {
        return match ($name) {
            'content_agent' => 'Generates and optimizes marketing content across channels.',
            'analytics_agent' => 'Analyzes campaign performance and detects trends.',
            'security_agent' => 'Monitors security posture and runs audits.',
            'social_agent' => 'Manages social media posting and engagement.',
            'support_agent' => 'Handles customer inquiries and triage.',
            'campaign_agent' => 'Optimizes ad campaigns and A/B tests.',
            default => "AI agent for {$name} tasks.",
        };
    }

    /**
     * Get the category for an agent.
     */
    private function getAgentCategory(string $name): string
    {
        return match ($name) {
            'content_agent' => 'Content',
            'analytics_agent' => 'Analytics',
            'security_agent' => 'Security',
            'social_agent' => 'Social',
            'support_agent' => 'Support',
            'campaign_agent' => 'Campaigns',
            default => 'General',
        };
    }
}
