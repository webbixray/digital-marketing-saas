<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_it_lists_workflows(): void
    {
        Workflow::factory()->count(3)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/workflows');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_it_creates_a_workflow(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/workflows', [
            'name' => 'Test Workflow',
            'trigger_type' => 'manual',
            'actions' => [['type' => 'auto_reply', 'config' => ['message' => 'Thanks!']]],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('workflows', ['name' => 'Test Workflow']);
    }

    public function test_it_validates_workflow_creation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/workflows', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'trigger_type', 'actions']);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/workflows');

        $response->assertUnauthorized();
    }
}
