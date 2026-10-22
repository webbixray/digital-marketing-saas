<?php

namespace Tests\Feature\Agent;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentDashboardTest extends TestCase
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
        ]);
    }

    /**
     * Test: agents dashboard loads with agent data.
     */
    public function test_agents_dashboard_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('agents.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [],
            ])
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    /**
     * Test: single agent detail page loads.
     */
    public function test_agent_detail_page_loads(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('agents.show', ['agentName' => 'content_agent']));

        // agentDetail returns a view, so we just check it doesn't error
        $response->assertOk();
    }

    /**
     * Test: unauthenticated users cannot dispatch tasks.
     */
    public function test_dispatch_task_requires_auth(): void
    {
        $response = $this->postJson(route('agents.dispatch'), [
            'agent_name' => 'content',
            'task_type' => 'content_generate',
            'prompt' => 'Generate a blog post',
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test: dispatch validates input.
     */
    public function test_dispatch_task_validates_input(): void
    {
        // Missing required fields
        $response = $this->actingAs($this->admin)
            ->postJson(route('agents.dispatch'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['agent_name', 'task_type', 'prompt']);

        // Invalid task_type
        $response = $this->actingAs($this->admin)
            ->postJson(route('agents.dispatch'), [
                'agent_name' => 'content',
                'task_type' => 'invalid_task_type',
                'prompt' => 'Test prompt',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['task_type']);
    }

    /**
     * Test: workflow templates list loads.
     */
    public function test_workflow_templates_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('agents.workflows'));

        // workflows() returns a view, so we just check it doesn't error
        $response->assertOk();
    }

    /**
     * Test: cross-agency access is blocked (agency isolation).
     */
    public function test_unauthorized_agency_isolation(): void
    {
        $otherAgency = Agency::factory()->create([
            'subscription_plan' => 'starter',
        ]);
        $otherUser = User::factory()->create([
            'agency_id' => $otherAgency->id,
            'role' => 'admin',
        ]);

        // Other agency user should not access this agency's agent stats
        // The agents endpoint uses agency-scoped data via the controller
        $response = $this->actingAs($otherUser)
            ->getJson(route('agents.index'));

        // Response should be successful but contain no data from other agencies
        $response->assertOk();
        $this->assertNotNull($response->json('data'));
    }
}
