<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\User;
use App\Services\AI\Agent\AgentInterface;
use App\Services\AI\Agent\AgentMemory;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\Workflows\ContentCalendarWorkflow;
use App\Services\AI\Agent\Workflows\WorkflowRunner;
use App\Services\AI\Gateway\AiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WorkflowRunnerTest extends TestCase
{
    use RefreshDatabase;

    private AgentOrchestrator $orchestrator;

    private WorkflowRunner $runner;

    private $gateway;

    private AgentMemory $memory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = Mockery::mock(AiGateway::class);
        $this->memory = new AgentMemory;
        $this->memory->clear();
        $this->orchestrator = new AgentOrchestrator($this->gateway, $this->memory);
        $this->runner = new WorkflowRunner($this->orchestrator);
    }

    protected function tearDown(): void
    {
        $this->memory->clear();
        Mockery::close();
        parent::tearDown();
    }

    public function test_run_executes_all_workflow_steps_and_returns_aggregated_results(): void
    {
        // Create test agency and user
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
        ]);

        // Register mock agents for each step
        $agents = [
            'analytics_agent' => ['trend_detection', 'recommendation'],
            'content_agent' => ['content_generate'],
            'social_media_agent' => ['post_schedule'],
        ];

        foreach ($agents as $agentName => $taskTypes) {
            $mockAgent = Mockery::mock(AgentInterface::class);
            $mockAgent->shouldReceive('getName')->andReturn($agentName);
            foreach ($taskTypes as $type) {
                $mockAgent->shouldReceive('canHandle')->with($type)->andReturn(true);
            }
            $mockAgent->shouldReceive('getSuccessRate')->andReturn(0.9);
            $mockAgent->shouldReceive('getSpeedScore')->andReturn(0.8);
            $mockAgent->shouldReceive('getCostScore')->andReturn(0.7);
            $mockAgent->shouldReceive('getSupportedTaskTypes')->andReturn($taskTypes);

            foreach ($taskTypes as $type) {
                $result = AgentResult::success(
                    taskId: "test-task-{$type}",
                    agentName: $agentName,
                    output: "Output for {$type}",
                    costUsd: 0.001,
                    tokensUsed: 100,
                );
                $mockAgent->shouldReceive('execute')->withArgs(function ($task) use ($type) {
                    return $task->type === $type;
                })->andReturn($result);
            }

            $this->orchestrator->registerAgent($agentName, $mockAgent);
        }

        $template = new ContentCalendarWorkflow;
        $result = $this->runner->run($template, $agency->id, $user->id);

        $this->assertEquals('content_calendar', $result['workflow_name']);
        $this->assertEquals(4, $result['total_steps']);
        $this->assertEquals(4, $result['completed_steps']);
        $this->assertTrue($result['successful']);
        $this->assertCount(4, $result['results']);
        $this->assertEquals($agency->id, $result['agency_id']);
        $this->assertEquals($user->id, $result['user_id']);

        // Verify each step result
        $this->assertEquals('analytics_agent', $result['results'][0]['agent']);
        $this->assertEquals('trend_detection', $result['results'][0]['task_type']);
        $this->assertTrue($result['results'][0]['success']);

        $this->assertEquals('content_agent', $result['results'][1]['agent']);
        $this->assertEquals('content_generate', $result['results'][1]['task_type']);

        $this->assertEquals('social_media_agent', $result['results'][2]['agent']);
        $this->assertEquals('post_schedule', $result['results'][2]['task_type']);

        $this->assertEquals('analytics_agent', $result['results'][3]['agent']);
        $this->assertEquals('recommendation', $result['results'][3]['task_type']);
    }

    public function test_run_stops_on_failure_and_reports_partial_results(): void
    {
        // Create test agency and user
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
        ]);

        // First agent succeeds
        $successAgent = Mockery::mock(AgentInterface::class);
        $successAgent->shouldReceive('getName')->andReturn('analytics_agent');
        $successAgent->shouldReceive('canHandle')->with('trend_detection')->andReturn(true);
        $successAgent->shouldReceive('getSuccessRate')->andReturn(0.9);
        $successAgent->shouldReceive('getSpeedScore')->andReturn(0.8);
        $successAgent->shouldReceive('getCostScore')->andReturn(0.7);
        $successAgent->shouldReceive('getSupportedTaskTypes')->andReturn(['trend_detection']);
        $successAgent->shouldReceive('execute')->andReturn(
            AgentResult::success('task-1', 'analytics_agent', 'Trends found', 0.001)
        );
        $this->orchestrator->registerAgent('analytics_agent', $successAgent);

        // Second agent fails
        $failingAgent = Mockery::mock(AgentInterface::class);
        $failingAgent->shouldReceive('getName')->andReturn('content_agent');
        $failingAgent->shouldReceive('canHandle')->with('content_generate')->andReturn(true);
        $failingAgent->shouldReceive('getSuccessRate')->andReturn(0.5);
        $failingAgent->shouldReceive('getSpeedScore')->andReturn(0.5);
        $failingAgent->shouldReceive('getCostScore')->andReturn(0.5);
        $failingAgent->shouldReceive('getSupportedTaskTypes')->andReturn(['content_generate']);
        $failingAgent->shouldReceive('execute')->andReturn(
            AgentResult::failure('task-2', 'content_agent', 'Service unavailable')
        );
        $this->orchestrator->registerAgent('content_agent', $failingAgent);

        $template = new ContentCalendarWorkflow;
        $result = $this->runner->run($template, $agency->id, $user->id);

        $this->assertFalse($result['successful']);
        $this->assertEquals(4, $result['total_steps']);
        $this->assertEquals(1, $result['completed_steps']);
        $this->assertCount(2, $result['results']);
        $this->assertTrue($result['results'][0]['success']);
        $this->assertFalse($result['results'][1]['success']);
        $this->assertEquals('Service unavailable', $result['results'][1]['error']);
    }
}
