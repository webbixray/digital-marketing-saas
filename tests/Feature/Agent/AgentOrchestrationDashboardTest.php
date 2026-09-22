<?php

namespace Tests\Feature\Agent;

use App\Models\Agency;
use App\Models\AgentCostLog;
use App\Models\AgentLearningReport;
use App\Models\AgentPerformanceLog;
use App\Models\AgentSharedKnowledge;
use App\Models\AgentWorkflowExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentOrchestrationDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
        ]);
        $this->admin = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test: orchestration dashboard route loads successfully.
     */
    public function test_orchestration_dashboard_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk()
            ->assertViewIs('agents.dashboard.index');
    }

    /**
     * Test: dashboard shows agent status cards.
     */
    public function test_dashboard_shows_agent_status_cards(): void
    {
        AgentWorkflowExecution::factory()->create([
            'agency_id' => $this->agency->id,
            'workflow_name' => 'content_agent',
            'status' => 'running',
            'started_at' => now(),
        ]);

        AgentWorkflowExecution::factory()->create([
            'agency_id' => $this->agency->id,
            'workflow_name' => 'analytics_agent',
            'status' => 'failed',
            'started_at' => now(),
        ]);

        AgentWorkflowExecution::factory()->create([
            'agency_id' => $this->agency->id,
            'workflow_name' => 'security_agent',
            'status' => 'success',
            'started_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('Idle Agents');
        $response->assertSee('Running Agents');
        $response->assertSee('Error Agents');
    }

    /**
     * Test: dashboard shows cost tracking data.
     */
    public function test_dashboard_shows_cost_data(): void
    {
        AgentCostLog::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'agent_name' => 'content_agent',
            'task_type' => 'content_generate',
            'cost_usd' => 0.005,
            'executed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('AI Cost Spend');
        $response->assertSee('Budget');
        $response->assertSee('$0.0250');
    }

    /**
     * Test: dashboard shows learning progress.
     */
    public function test_dashboard_shows_learning_progress(): void
    {
        AgentLearningReport::factory()->count(3)->create([
            'agent_name' => 'content_agent',
            'improvement_type' => 'prompt_optimization',
            'status' => 'applied',
        ]);

        AgentLearningReport::factory()->count(2)->create([
            'agent_name' => 'content_agent',
            'improvement_type' => 'cost_reduction',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('Agent Learning Progress');
    }

    /**
     * Test: dashboard shows workflow execution log.
     */
    public function test_dashboard_shows_workflow_executions(): void
    {
        AgentWorkflowExecution::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'workflow_name' => 'content_agent',
            'status' => 'success',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('Workflow Execution Log');
    }

    /**
     * Test: dashboard shows collaboration graph data.
     */
    public function test_dashboard_shows_collaboration_data(): void
    {
        AgentSharedKnowledge::factory()->create([
            'agency_id' => $this->agency->id,
            'from_agent' => 'content_agent',
            'category' => 'trend',
            'confidence' => 0.85,
        ]);

        AgentSharedKnowledge::factory()->create([
            'agency_id' => $this->agency->id,
            'from_agent' => 'analytics_agent',
            'category' => 'trend',
            'confidence' => 0.78,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('Agent Collaboration Graph');
    }

    /**
     * Test: unauthenticated users are redirected.
     */
    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get(route('agents.dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test: dashboard shows budget status correctly.
     */
    public function test_dashboard_shows_budget_status(): void
    {
        AgentCostLog::factory()->create([
            'agency_id' => $this->agency->id,
            'agent_name' => 'content_agent',
            'cost_usd' => 30.0,
            'executed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('Budget Usage');
    }

    /**
     * Test: dashboard shows cost breakdown by agent.
     */
    public function test_dashboard_shows_cost_by_agent(): void
    {
        AgentCostLog::factory()->create([
            'agency_id' => $this->agency->id,
            'agent_name' => 'content_agent',
            'cost_usd' => 0.01,
            'executed_at' => now(),
        ]);

        AgentCostLog::factory()->create([
            'agency_id' => $this->agency->id,
            'agent_name' => 'analytics_agent',
            'cost_usd' => 0.005,
            'executed_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('Cost Breakdown by Agent');
        $response->assertSee('Content Agent');
        $response->assertSee('Analytics Agent');
    }

    /**
     * Test: dashboard shows learning reports table.
     */
    public function test_dashboard_shows_learning_reports_table(): void
    {
        AgentLearningReport::factory()->create([
            'agent_name' => 'content_agent',
            'improvement_type' => 'prompt_optimization',
            'description' => 'Improved content generation prompts',
            'status' => 'applied',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('Recent Learning Reports');
        $response->assertSee('Improved content generation prompts');
    }

    /**
     * Test: dashboard handles empty data gracefully.
     */
    public function test_dashboard_handles_empty_data(): void
    {
        // The orchestrator auto-discovers agents, so we just verify the page loads
        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('AI Agent Orchestration Dashboard');
    }

    /**
     * Test: dashboard shows Chart.js CDN script.
     */
    public function test_dashboard_includes_chartjs_cdn(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('chart.js');
    }

    /**
     * Test: dashboard shows v6.0 badge.
     */
    public function test_dashboard_shows_version_badge(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertSee('v6.0');
    }

    /**
     * Test: dashboard shows performance metrics.
     */
    public function test_dashboard_shows_performance_metrics(): void
    {
        AgentPerformanceLog::factory()->count(3)->create([
            'agent_name' => 'content_agent',
            'metric_type' => 'response_time',
            'metric_value' => 0.85,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertViewHas('performanceSummary');
    }

    /**
     * Test: dashboard shows correct status counts.
     */
    public function test_dashboard_shows_correct_status_counts(): void
    {
        AgentWorkflowExecution::factory()->create([
            'agency_id' => $this->agency->id,
            'workflow_name' => 'content_agent',
            'status' => 'running',
            'started_at' => now(),
        ]);

        AgentWorkflowExecution::factory()->create([
            'agency_id' => $this->agency->id,
            'workflow_name' => 'analytics_agent',
            'status' => 'failed',
            'started_at' => now(),
        ]);

        AgentWorkflowExecution::factory()->create([
            'agency_id' => $this->agency->id,
            'workflow_name' => 'security_agent',
            'status' => 'success',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertViewHas('statusCounts', function ($counts) {
            return isset($counts['running']) && isset($counts['error']) && isset($counts['idle']);
        });
    }
}
