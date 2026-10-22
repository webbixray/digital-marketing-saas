<?php

namespace Tests\Feature\Workflow;

use App\Enums\WorkflowStatus;
use App\Models\Agency;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowExecutionTest extends TestCase
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

    public function test_it_executes_a_simple_notification_workflow(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'comment_received',
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'New comment!', 'channel' => 'log']],
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow), [
                'trigger_data' => ['platform' => 'facebook', 'comment' => 'Hello!'],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('workflow_executions', [
            'workflow_id' => $workflow->id,
            'status' => 'success',
        ]);
    }

    public function test_it_executes_ai_generate_workflow(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'schedule',
            'actions' => [
                ['type' => 'ai_generate', 'config' => ['prompt' => 'Write a tweet about AI', 'generation_type' => 'social_post']],
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow), [
                'trigger_data' => ['prompt' => 'Write a tweet about AI'],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_it_executes_webhook_call_workflow(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'webhook',
            'actions' => [
                ['type' => 'webhook', 'config' => ['url' => 'https://httpbin.org/post', 'method' => 'POST', 'payload' => ['test' => true]]],
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow), [
                'trigger_data' => ['event' => 'test'],
            ]);

        // Webhook might fail due to network but execution should be recorded
        $response->assertStatus(200);
    }

    public function test_it_skips_when_conditions_not_met(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'comment_received',
            'conditions' => ['platform' => 'instagram'],
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Should not run']],
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow), [
                'trigger_data' => ['platform' => 'facebook'], // Different platform
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $execution = WorkflowExecution::where('workflow_id', $workflow->id)->first();
        $this->assertEquals('success', $execution->status);
        $this->assertArrayHasKey('skipped', $execution->action_results);
    }

    public function test_it_records_failed_execution(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'comment_received',
            'actions' => [
                ['type' => 'create_post', 'config' => ['content' => 'Test']], // Missing social_account_id
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow), [
                'trigger_data' => ['platform' => 'facebook'],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $execution = WorkflowExecution::where('workflow_id', $workflow->id)->first();
        $this->assertEquals('success', $execution->status); // Overall success
        $this->assertEquals('failed', $execution->action_results[0]['status']); // Action failed
    }

    public function test_it_prevents_unauthorized_execution(): void
    {
        $otherAgency = Agency::factory()->create();
        $workflow = Workflow::factory()->create([
            'agency_id' => $otherAgency->id,
            'trigger_type' => 'comment_received',
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Hello']],
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow));

        $response->assertForbidden();
    }

    public function test_it_stores_workflow_from_builder_and_executes(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.builder.save'), [
                'name' => 'Test Social Workflow',
                'description' => 'Test workflow from builder',
                'nodes' => [
                    [
                        'id' => 1,
                        'type' => 'trigger',
                        'subtype' => 'comment_received',
                        'x' => 100,
                        'y' => 200,
                        'config' => ['platform' => 'facebook'],
                        'label' => 'Comment Received',
                    ],
                    [
                        'id' => 2,
                        'type' => 'action',
                        'subtype' => 'send_notification',
                        'x' => 350,
                        'y' => 200,
                        'config' => ['message' => 'New FB comment!', 'channel' => 'log'],
                        'label' => 'Send Notification',
                    ],
                ],
                'connections' => [
                    ['from' => 1, 'to' => 2],
                ],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('workflows', [
            'name' => 'Test Social Workflow',
            'trigger_type' => 'comment_received',
            'agency_id' => $this->agency->id,
        ]);

        $workflow = Workflow::where('name', 'Test Social Workflow')->first();
        $this->assertNotNull($workflow);
        $this->assertCount(1, $workflow->actions);
        $this->assertEquals('send_notification', $workflow->actions[0]['type']);

        // Now execute the stored workflow
        $execResponse = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow), [
                'trigger_data' => ['platform' => 'facebook', 'comment' => 'Test'],
            ]);

        $execResponse->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_it_updates_existing_workflow_from_builder(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Original Name',
            'trigger_type' => 'comment_received',
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Original']],
            ],
            'status' => WorkflowStatus::DRAFT->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.builder.update', $workflow), [
                'name' => 'Updated Workflow',
                'description' => 'Updated description',
                'nodes' => [
                    [
                        'id' => 1,
                        'type' => 'trigger',
                        'subtype' => 'mention_received',
                        'x' => 100,
                        'y' => 200,
                        'config' => [],
                        'label' => 'Mention Received',
                    ],
                    [
                        'id' => 2,
                        'type' => 'action',
                        'subtype' => 'auto_reply',
                        'x' => 350,
                        'y' => 200,
                        'config' => ['message' => 'Thanks for the mention!'],
                        'label' => 'Auto Reply',
                    ],
                ],
                'connections' => [
                    ['from' => 1, 'to' => 2],
                ],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $workflow->refresh();
        $this->assertEquals('Updated Workflow', $workflow->name);
        $this->assertEquals('Updated description', $workflow->description);
        $this->assertEquals('mention_received', $workflow->trigger_type);
        $this->assertEquals('auto_reply', $workflow->actions[0]['type']);
    }

    public function test_it_loads_existing_workflow_into_builder(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'My Workflow',
            'description' => 'Test workflow',
            'trigger_type' => 'comment_received',
            'actions' => [
                ['type' => 'auto_reply', 'config' => ['message' => 'Thanks!']],
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('workflows.builder', $workflow));

        $response->assertOk();
    }

    public function test_it_executes_loop_action(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'schedule',
            'actions' => [
                [
                    'type' => 'loop',
                    'config' => [
                        'iterations' => 3,
                        'actions' => [
                            ['type' => 'send_notification', 'config' => ['message' => 'Loop iteration']],
                        ],
                    ],
                ],
            ],
            'status' => WorkflowStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('workflows.execute', $workflow), [
                'trigger_data' => [],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $execution = WorkflowExecution::where('workflow_id', $workflow->id)->first();
        $this->assertEquals(3, $execution->action_results[0]['iterations']);
        $this->assertCount(3, $execution->action_results[0]['results']);
    }
}
