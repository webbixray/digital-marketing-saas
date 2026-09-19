<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\Workflow;
use App\Services\AI\AiContentService;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    private WorkflowEngine $engine;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new WorkflowEngine(
            \Mockery::mock(AiContentService::class)
        );
        $this->agency = Agency::factory()->create();
    }

    // ──────────────────────────────────────────────
    // Registration (creating workflows)
    // ──────────────────────────────────────────────

    public function test_can_register_workflow_with_factory(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'post_published',
        ]);

        $this->assertDatabaseHas('workflows', [
            'id' => $workflow->id,
            'trigger_type' => 'post_published',
        ]);
    }

    public function test_registered_workflow_has_required_fields(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Test Workflow',
            'slug' => 'test-workflow-'.uniqid(),
            'trigger_type' => 'post_published',
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Hello']],
            ],
            'conditions' => ['platform' => 'instagram'],
        ]);

        $this->assertEquals('Test Workflow', $workflow->name);
        $this->assertEquals('post_published', $workflow->trigger_type);
        $this->assertIsArray($workflow->actions);
        $this->assertIsArray($workflow->conditions);
    }

    public function test_registered_workflow_without_conditions_executes_all_triggers(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'post_published',
            'conditions' => null,
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'No conditions']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, ['platform' => 'facebook']);

        $this->assertEquals('success', $execution->status);
        $this->assertArrayNotHasKey('skipped', $execution->action_results ?? []);
    }

    // ──────────────────────────────────────────────
    // Execute workflow with trigger
    // ──────────────────────────────────────────────

    public function test_execute_creates_workflow_execution_record(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'post_published',
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Test']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, ['post_id' => 123]);

        $this->assertDatabaseHas('workflow_executions', [
            'workflow_id' => $workflow->id,
            'status' => 'success',
        ]);
        $this->assertEquals($workflow->id, $execution->workflow_id);
    }

    public function test_execute_with_matching_trigger_runs_all_actions(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'post_published',
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Action 1']],
                ['type' => 'send_notification', 'config' => ['message' => 'Action 2']],
                ['type' => 'ai_generate', 'config' => ['prompt' => 'Generate something']],
            ],
        ]);

        $mockAiContent = Mockery::mock(AiContentService::class);
        $mockAiContent->shouldReceive('generate')->withArgs(function ($agency, $prompt, $type) {
            return $agency instanceof \App\Models\Agency && $prompt === 'Generate something' && $type === 'social_post';
        })->andReturn(new \App\Services\AI\Gateway\AiResponse(content: 'Generated content', model: 'test', provider: 'test', promptTokens: 10, completionTokens: 20, totalTokens: 30, costUsd: 0.01));
        $engine = new WorkflowEngine($mockAiContent);

        $execution = $engine->execute($workflow, ['platform' => 'instagram', 'agency' => $this->agency]);

        $this->assertEquals('success', $execution->status);
        $this->assertCount(3, $execution->action_results);
        $this->assertEquals('success', $execution->action_results[0]['status']);
        $this->assertEquals('success', $execution->action_results[1]['status']);
        $this->assertEquals('success', $execution->action_results[2]['status']);
    }

    public function test_execute_triggers_log_for_send_notification_action(): void
    {
        Log::spy();

        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'post_published',
            'actions' => [
                ['type' => 'send_notification', 'config' => [
                    'channel' => 'log',
                    'message' => 'Post published successfully',
                    'recipient' => 'admin',
                ]],
            ],
        ]);

        $execution = $this->engine->execute($workflow, []);

        $this->assertEquals('success', $execution->status);
        $this->assertEquals('send_notification', $execution->action_results[0]['action']);
        $this->assertEquals('log', $execution->action_results[0]['channel']);
    }

    // ──────────────────────────────────────────────
    // Verify actions are executed
    // ──────────────────────────────────────────────

    public function test_actions_executed_in_order(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'First']],
                ['type' => 'ai_generate', 'config' => ['prompt' => 'Second']],
                ['type' => 'send_notification', 'config' => ['message' => 'Third']],
            ],
        ]);

        $mockAiContent = Mockery::mock(AiContentService::class);
        $mockAiContent->shouldReceive('generate')->withArgs(function ($agency, $prompt, $type) {
            return $agency instanceof \App\Models\Agency && $prompt === 'Second' && $type === 'social_post';
        })->andReturn(new \App\Services\AI\Gateway\AiResponse(content: 'Generated', model: 'test', provider: 'test', promptTokens: 10, completionTokens: 20, totalTokens: 30, costUsd: 0.01));
        $engine = new WorkflowEngine($mockAiContent);

        $execution = $engine->execute($workflow, ['agency' => $this->agency]);

        $actions = collect($execution->action_results)->pluck('action')->toArray();
        $this->assertEquals(['send_notification', 'ai_generate', 'send_notification'], $actions);
    }

    public function test_unknown_action_type_returns_skipped(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'actions' => [
                ['type' => 'unknown_action_type', 'config' => []],
            ],
        ]);

        $execution = $this->engine->execute($workflow, []);

        $this->assertEquals('skipped', $execution->action_results[0]['status']);
        $this->assertStringContainsString('Unknown action type', $execution->action_results[0]['reason']);
    }

    public function test_ai_generate_action_returns_success(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'actions' => [
                ['type' => 'ai_generate', 'config' => [
                    'prompt' => 'Write a blog post about marketing',
                    'generation_type' => 'blog_post',
                ]],
            ],
        ]);

        $mockAiContent = Mockery::mock(AiContentService::class);
        $mockAiContent->shouldReceive('generate')->withArgs(function ($agency, $prompt, $type) {
            return $agency instanceof \App\Models\Agency && $prompt === 'Write a blog post about marketing' && $type === 'blog_post';
        })->andReturn(new \App\Services\AI\Gateway\AiResponse(content: 'Blog post content', model: 'test', provider: 'test', promptTokens: 10, completionTokens: 20, totalTokens: 30, costUsd: 0.01));
        $engine = new WorkflowEngine($mockAiContent);

        $execution = $engine->execute($workflow, ['agency' => $this->agency]);

        $this->assertEquals('success', $execution->action_results[0]['status']);
        $this->assertEquals('ai_generate', $execution->action_results[0]['action']);
        $this->assertEquals('blog_post', $execution->action_results[0]['type']);
    }

    // ──────────────────────────────────────────────
    // Verify conditions are checked
    // ──────────────────────────────────────────────

    public function test_conditions_met_executes_actions(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'conditions' => ['platform' => 'instagram', 'status' => 'published'],
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Conditions met']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, [
            'platform' => 'instagram',
            'status' => 'published',
        ]);

        $this->assertEquals('success', $execution->status);
        $this->assertNotEquals('skipped', $execution->action_results[0]['status'] ?? '');
    }

    public function test_conditions_not_met_skips_actions(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'conditions' => ['platform' => 'instagram'],
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Should not run']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, [
            'platform' => 'facebook',
        ]);

        $this->assertEquals('success', $execution->status);
        $this->assertEquals('Conditions not met', $execution->action_results['skipped']);
    }

    public function test_partial_conditions_not_met_skips_execution(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'conditions' => ['platform' => 'instagram', 'status' => 'published'],
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Should not run']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, [
            'platform' => 'instagram',
            'status' => 'draft', // mismatch
        ]);

        $this->assertEquals('Conditions not met', $execution->action_results['skipped']);
    }

    public function test_empty_conditions_always_executes(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'conditions' => [],
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Always runs']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, ['any' => 'data']);

        $this->assertEquals('success', $execution->status);
        $this->assertArrayNotHasKey('skipped', $execution->action_results ?? []);
    }

    public function test_non_matching_trigger_data_does_not_execute_actions(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'conditions' => ['trigger' => 'post_published'],
            'actions' => [
                ['type' => 'send_notification', 'config' => ['message' => 'Should not run']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, [
            'trigger' => 'campaign_created', // different trigger
        ]);

        $this->assertEquals('Conditions not met', $execution->action_results['skipped']);
    }

    // ──────────────────────────────────────────────
    // Execution metadata & workflow updates
    // ──────────────────────────────────────────────

    public function test_successful_execution_increments_execution_count(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'execution_count' => 0,
            'actions' => [
                ['type' => 'send_notification', 'config' => []],
            ],
        ]);

        $this->engine->execute($workflow, []);

        $workflow->refresh();
        $this->assertEquals(1, $workflow->execution_count);
        $this->assertNotNull($workflow->last_executed_at);
    }

    public function test_execution_records_duration(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'actions' => [
                ['type' => 'send_notification', 'config' => []],
            ],
        ]);

        $execution = $this->engine->execute($workflow, []);

        $this->assertGreaterThanOrEqual(0, $execution->duration_ms);
        $this->assertNotNull($execution->started_at);
        $this->assertNotNull($execution->completed_at);
    }

    // ──────────────────────────────────────────────
    // Error handling for invalid workflows
    // ──────────────────────────────────────────────

    public function test_execute_with_empty_actions_still_succeeds(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'actions' => [],
        ]);

        $execution = $this->engine->execute($workflow, []);

        $this->assertEquals('success', $execution->status);
        $this->assertEmpty($execution->action_results);
    }

    public function test_execute_with_null_actions_succeeds(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'actions' => null,
        ]);

        $execution = $this->engine->execute($workflow, []);

        $this->assertEquals('success', $execution->status);
    }

    public function test_malformed_action_does_not_break_execution(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'actions' => [
                ['config' => ['message' => 'Missing type key']],
                ['type' => 'send_notification', 'config' => ['message' => 'Valid action']],
            ],
        ]);

        $execution = $this->engine->execute($workflow, []);

        $this->assertEquals('success', $execution->status);
        $this->assertEquals('skipped', $execution->action_results[0]['status']);
        $this->assertEquals('success', $execution->action_results[1]['status']);
    }

    // ──────────────────────────────────────────────
    // getWorkflows / getWorkflow (model queries)
    // ──────────────────────────────────────────────

    public function test_can_get_workflows_by_agency(): void
    {
        Workflow::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $workflows = Workflow::where('agency_id', $this->agency->id)->get();

        $this->assertCount(3, $workflows);
    }

    public function test_can_get_workflow_by_id(): void
    {
        $workflow = Workflow::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $found = Workflow::find($workflow->id);

        $this->assertNotNull($found);
        $this->assertEquals($workflow->id, $found->id);
    }

    public function test_get_nonexistent_workflow_returns_null(): void
    {
        $found = Workflow::find(99999);
        $this->assertNull($found);
    }

    public function test_workflows_can_be_filtered_by_trigger_type(): void
    {
        Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'post_published',
        ]);
        Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'campaign_created',
        ]);
        Workflow::factory()->create([
            'agency_id' => $this->agency->id,
            'trigger_type' => 'post_published',
        ]);

        $postPublished = Workflow::where('trigger_type', 'post_published')->get();
        $this->assertCount(2, $postPublished);
    }
}
