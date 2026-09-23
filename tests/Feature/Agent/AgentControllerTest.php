<?php

namespace Tests\Feature\Agent;

use App\Models\Agency;
use App\Models\AgentCostLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->getJson(route('agents.index'));
        $response->assertUnauthorized();
    }

    public function test_index_returns_agent_list(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('agents.index'));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_stats_returns_agent_statistics(): void
    {
        AgentCostLog::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'agent_name' => 'content_agent',
            'cost_usd' => 0.05,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('agents.stats'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertArrayHasKey('total_agents', $data);
        $this->assertArrayHasKey('total_executed', $data);
        $this->assertArrayHasKey('total_cost_usd', $data);
    }

    public function test_dispatch_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('agents.dispatch'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['agent_name', 'task_type', 'prompt']);
    }

    public function test_dispatch_validates_task_type(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('agents.dispatch'), [
            'agent_name' => 'content_agent',
            'task_type' => 'invalid_type',
            'prompt' => 'Generate content',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['task_type']);
    }

    public function test_run_workflow_validates_workflow_name(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('agents.run-workflow'), [
            'workflow_name' => 'invalid_workflow',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['workflow_name']);
    }

    public function test_run_workflow_dispatches_async(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('agents.run-workflow'), [
            'workflow_name' => 'content_campaign',
            'async' => true,
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure(['success', 'data']);
    }

    public function test_list_workflows_returns_templates(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('agents.workflows'));

        $response->assertOk();
    }

    public function test_workflows_page_shows_execution_history(): void
    {
        $response = $this->actingAs($this->user)->get(route('agents.workflows'));

        $response->assertOk();
        $response->assertViewHas('workflows');
    }

    public function test_dashboard_shows_agent_overview(): void
    {
        AgentCostLog::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('agents.dashboard'));

        $response->assertOk();
        $response->assertViewHas('agents');
    }
}
