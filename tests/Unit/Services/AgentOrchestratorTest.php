<?php

namespace Tests\Unit\Services;

use App\Services\AI\Agent\AgentInterface;
use App\Services\AI\Agent\AgentMemory;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiGateway;
use Mockery;
use Tests\TestCase;

class AgentOrchestratorTest extends TestCase
{
    private AgentOrchestrator $orchestrator;

    private $gateway;

    private AgentMemory $memory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = Mockery::mock(AiGateway::class);
        $this->memory = new AgentMemory;
        $this->memory->clear();
        $this->orchestrator = new AgentOrchestrator($this->gateway, $this->memory);
    }

    protected function tearDown(): void
    {
        $this->memory->clear();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: dispatch picks best agent using scoring formula.
     */
    public function test_dispatch_picks_best_agent(): void
    {
        // Agent A: high success rate, low speed, low cost
        $agentA = Mockery::mock(AgentInterface::class);
        $agentA->shouldReceive('getName')->andReturn('agent-a');
        $agentA->shouldReceive('canHandle')->with('content_generate')->andReturn(true);
        $agentA->shouldReceive('getSuccessRate')->andReturn(0.95);
        $agentA->shouldReceive('getSpeedScore')->andReturn(0.3);
        $agentA->shouldReceive('getCostScore')->andReturn(0.2);
        $agentA->shouldReceive('getSupportedTaskTypes')->andReturn(['content_generate']);

        // Agent B: lower success rate, higher speed, higher cost
        $agentB = Mockery::mock(AgentInterface::class);
        $agentB->shouldReceive('getName')->andReturn('agent-b');
        $agentB->shouldReceive('canHandle')->with('content_generate')->andReturn(true);
        $agentB->shouldReceive('getSuccessRate')->andReturn(0.70);
        $agentB->shouldReceive('getSpeedScore')->andReturn(0.9);
        $agentB->shouldReceive('getCostScore')->andReturn(0.9);
        $agentB->shouldReceive('getSupportedTaskTypes')->andReturn(['content_generate']);

        $this->orchestrator->registerAgent('agent-a', $agentA);
        $this->orchestrator->registerAgent('agent-b', $agentB);

        // Agent B should win: 0.70*0.6 + 0.9*0.2 + 0.9*0.2 = 0.78
        // Agent A: 0.95*0.6 + 0.3*0.2 + 0.2*0.2 = 0.67
        $best = $this->orchestrator->getBestAgentFor('content_generate');

        $this->assertNotNull($best);
        $this->assertEquals('agent-b', $best->getName());
    }

    /**
     * Test: dispatch workflow chains outputs between steps.
     */
    public function test_dispatch_workflow_chains_outputs(): void
    {
        // First agent: planner
        $planner = Mockery::mock(AgentInterface::class);
        $planner->shouldReceive('getName')->andReturn('planner');
        $planner->shouldReceive('canHandle')->with('plan')->andReturn(true);
        $planner->shouldReceive('canHandle')->with('execute')->andReturn(false);
        $planner->shouldReceive('getSuccessRate')->andReturn(0.95);
        $planner->shouldReceive('getSpeedScore')->andReturn(0.7);
        $planner->shouldReceive('getCostScore')->andReturn(0.8);
        $planner->shouldReceive('getSupportedTaskTypes')->andReturn(['plan']);

        $planResult = AgentResult::success(
            taskId: 'task-plan',
            agentName: 'planner',
            output: 'Step 1: Research -> Step 2: Draft -> Step 3: Edit',
        );
        $planner->shouldReceive('execute')->once()->andReturn($planResult);

        // Second agent: executor
        $executor = Mockery::mock(AgentInterface::class);
        $executor->shouldReceive('getName')->andReturn('executor');
        $executor->shouldReceive('canHandle')->with('plan')->andReturn(false);
        $executor->shouldReceive('canHandle')->with('execute')->andReturn(true);
        $executor->shouldReceive('getSuccessRate')->andReturn(0.85);
        $executor->shouldReceive('getSpeedScore')->andReturn(0.9);
        $executor->shouldReceive('getCostScore')->andReturn(0.6);
        $executor->shouldReceive('getSupportedTaskTypes')->andReturn(['execute']);

        $execResult = AgentResult::success(
            taskId: 'task-exec',
            agentName: 'executor',
            output: 'Executed: Research -> Draft -> Edit',
        );
        $executor->shouldReceive('execute')->once()->andReturn($execResult);

        $this->orchestrator->registerAgent('planner', $planner);
        $this->orchestrator->registerAgent('executor', $executor);

        $tasks = [
            new AgentTask(id: 'task-plan', type: 'plan', prompt: 'Create plan'),
            new AgentTask(id: 'task-exec', type: 'execute', prompt: 'Execute plan'),
        ];

        $results = $this->orchestrator->dispatchWorkflow($tasks);

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->success);
        $this->assertTrue($results[1]->success);
        $this->assertEquals('planner', $results[0]->agentName);
        $this->assertEquals('executor', $results[1]->agentName);
    }

    /**
     * Test: learn from results adjusts routing weights.
     */
    public function test_learn_from_results_adjusts_weights(): void
    {
        $reliableAgent = Mockery::mock(AgentInterface::class);
        $reliableAgent->shouldReceive('getName')->andReturn('reliable-agent');
        $reliableAgent->shouldReceive('canHandle')->andReturn(true);
        $reliableAgent->shouldReceive('getSuccessRate')->andReturn(0.95);
        $reliableAgent->shouldReceive('getSpeedScore')->andReturn(0.7);
        $reliableAgent->shouldReceive('getCostScore')->andReturn(0.8);
        $reliableAgent->shouldReceive('getSupportedTaskTypes')->andReturn(['analyze']);

        $unreliableAgent = Mockery::mock(AgentInterface::class);
        $unreliableAgent->shouldReceive('getName')->andReturn('unreliable-agent');
        $unreliableAgent->shouldReceive('canHandle')->andReturn(true);
        $unreliableAgent->shouldReceive('getSuccessRate')->andReturn(0.3);
        $unreliableAgent->shouldReceive('getSpeedScore')->andReturn(0.9);
        $unreliableAgent->shouldReceive('getCostScore')->andReturn(0.9);
        $unreliableAgent->shouldReceive('getSupportedTaskTypes')->andReturn(['analyze']);

        $this->orchestrator->registerAgent('reliable-agent', $reliableAgent);
        $this->orchestrator->registerAgent('unreliable-agent', $unreliableAgent);

        // Record results: reliable succeeds, unreliable fails
        for ($i = 0; $i < 10; $i++) {
            $this->memory->recordResult('reliable-agent', 'analyze', AgentResult::success(
                taskId: "task-reliable-$i",
                agentName: 'reliable-agent',
                output: 'Success',
                costUsd: 0.01,
            ));

            $this->memory->recordResult('unreliable-agent', 'analyze', AgentResult::failure(
                taskId: "task-unreliable-$i",
                agentName: 'unreliable-agent',
                error: 'Timeout',
                costUsd: 0.001,
            ));
        }

        $this->orchestrator->learnFromResults();

        $stats = $this->orchestrator->getAgentStats();
        $this->assertEquals(1.0, $stats['reliable-agent']['success_rate']);
        $this->assertEquals(0.0, $stats['unreliable-agent']['success_rate']);
    }

    /**
     * Test: get agent stats returns correct aggregated data.
     */
    public function test_get_agent_stats_returns_correct_data(): void
    {
        $agent = Mockery::mock(AgentInterface::class);
        $agent->shouldReceive('getName')->andReturn('test-agent');
        $agent->shouldReceive('canHandle')->andReturn(true);
        $agent->shouldReceive('getSuccessRate')->andReturn(0.85);
        $agent->shouldReceive('getSpeedScore')->andReturn(0.7);
        $agent->shouldReceive('getCostScore')->andReturn(0.8);
        $agent->shouldReceive('getSupportedTaskTypes')->andReturn(['test-type']);
        $agent->shouldReceive('getTotalExecuted')->andReturn(10);
        $agent->shouldReceive('getTotalCost')->andReturn(0.1);
        $agent->shouldReceive('execute')->andReturn(
            AgentResult::success('task-id', 'test-agent', 'Output', 0.01)
        );

        $this->orchestrator->registerAgent('test-agent', $agent);

        for ($i = 0; $i < 5; $i++) {
            $task = new AgentTask(
                id: "task-$i",
                type: 'test-type',
                prompt: 'Test prompt',
            );
            $this->orchestrator->dispatch($task);
        }

        $stats = $this->orchestrator->getAgentStats();

        $this->assertArrayHasKey('test-agent', $stats);
        $this->assertEquals('test-agent', $stats['test-agent']['name']);
        $this->assertEquals(0.7, $stats['test-agent']['speed_score']);
        $this->assertEquals(0.8, $stats['test-agent']['cost_score']);
        $this->assertContains('test-type', $stats['test-agent']['supported_types']);
    }

    /**
     * Test: collaborative dispatch merges results from multiple agents.
     */
    public function test_collaborative_dispatch_merges_results(): void
    {
        $agentA = Mockery::mock(AgentInterface::class);
        $agentA->shouldReceive('getName')->andReturn('agent_a');
        $agentA->shouldReceive('canHandle')->with('analyze')->andReturn(true);
        $agentA->shouldReceive('execute')->once()->andReturn(
            AgentResult::success('task-1', 'agent_a', 'Analysis from agent A', 0.01, 100)
        );

        $agentB = Mockery::mock(AgentInterface::class);
        $agentB->shouldReceive('getName')->andReturn('agent_b');
        $agentB->shouldReceive('canHandle')->with('analyze')->andReturn(true);
        $agentB->shouldReceive('execute')->once()->andReturn(
            AgentResult::success('task-1', 'agent_b', 'Analysis from agent B', 0.02, 150)
        );

        $this->orchestrator->registerAgent('agent_a', $agentA);
        $this->orchestrator->registerAgent('agent_b', $agentB);

        $task = new AgentTask(
            id: 'task-1',
            type: 'analyze',
            prompt: 'Analyze this data',
        );

        $result = $this->orchestrator->dispatchCollaborative($task, ['agent_a', 'agent_b']);

        $this->assertTrue($result->success);
        $this->assertStringContainsString('agent_a', $result->agentName);
        $this->assertStringContainsString('agent_b', $result->agentName);
        $this->assertStringContainsString('Analysis from agent A', $result->output);
        $this->assertStringContainsString('Analysis from agent B', $result->output);
        $this->assertEquals(0.03, $result->costUsd);
        $this->assertEquals(250, $result->tokensUsed);
        $this->assertTrue($result->metadata['collaborative']);
        $this->assertEquals(2, $result->metadata['success_count']);
    }
}
