<?php

namespace Tests\Unit\Services;

use App\Services\AI\Agent\AgentInterface;
use App\Services\AI\Agent\AgentMemory;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Agent\Collaboration\AgentCollaborationProtocol;
use App\Services\AI\Agent\Collaboration\SharedKnowledgeBase;
use App\Services\AI\Gateway\AiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class AgentCollaborationTest extends TestCase
{
    use RefreshDatabase;

    private AgentOrchestrator $orchestrator;

    private AgentMemory $memory;

    private SharedKnowledgeBase $knowledgeBase;

    private AgentCollaborationProtocol $protocol;

    protected function setUp(): void
    {
        parent::setUp();
        $gateway = Mockery::mock(AiGateway::class);
        $this->memory = new AgentMemory;
        $this->memory->clear();
        $this->orchestrator = new AgentOrchestrator($gateway, $this->memory);
        $this->knowledgeBase = new SharedKnowledgeBase;
        $this->protocol = new AgentCollaborationProtocol($this->orchestrator, $this->knowledgeBase);
    }

    protected function tearDown(): void
    {
        $this->memory->clear();
        Cache::flush();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test 1: AgentCollaborationProtocol can find collaborators for a task type.
     */
    public function test_find_collaborators_returns_agents_that_can_handle_task_type(): void
    {
        // Create two agents with different capabilities
        $contentAgent = Mockery::mock(AgentInterface::class);
        $contentAgent->shouldReceive('getName')->andReturn('content_agent');
        $contentAgent->shouldReceive('canHandle')->with('content_generate')->andReturn(true);
        $contentAgent->shouldReceive('getSuccessRate')->andReturn(0.9);
        $contentAgent->shouldReceive('getSpeedScore')->andReturn(0.8);
        $contentAgent->shouldReceive('getCostScore')->andReturn(0.7);
        $contentAgent->shouldReceive('getSupportedTaskTypes')->andReturn(['content_generate', 'content_optimize']);

        $analyticsAgent = Mockery::mock(AgentInterface::class);
        $analyticsAgent->shouldReceive('getName')->andReturn('analytics_agent');
        $analyticsAgent->shouldReceive('canHandle')->with('content_generate')->andReturn(false);
        $analyticsAgent->shouldReceive('getSuccessRate')->andReturn(0.85);
        $analyticsAgent->shouldReceive('getSpeedScore')->andReturn(0.7);
        $analyticsAgent->shouldReceive('getCostScore')->andReturn(0.8);
        $analyticsAgent->shouldReceive('getSupportedTaskTypes')->andReturn(['performance_analysis', 'trend_detection']);

        $this->orchestrator->registerAgent('content_agent', $contentAgent);
        $this->orchestrator->registerAgent('analytics_agent', $analyticsAgent);

        $collaborators = $this->protocol->findCollaborators('content_generate');

        $this->assertCount(1, $collaborators);
        $this->assertArrayHasKey('content_agent', $collaborators);
        $this->assertEquals(0.9, $collaborators['content_agent']['success_rate']);
    }

    /**
     * Test 2: SharedKnowledgeBase stores and retrieves insights across agents.
     */
    public function test_shared_knowledge_base_stores_and_retrieves_insights(): void
    {
        $agencyId = 1;

        // Share insights from different agents
        $this->knowledgeBase->shareInsight($agencyId, 'content_agent', 'Use emotional hooks in headlines', 'optimization');
        $this->knowledgeBase->shareInsight($agencyId, 'analytics_agent', 'Peak engagement at 9 AM on weekdays', 'optimization');
        $this->knowledgeBase->shareInsight($agencyId, 'content_agent', 'Questions increase comments by 30%', 'engagement');

        // Retrieve insights by category
        $optimizationInsights = $this->knowledgeBase->getInsights($agencyId, 'optimization');
        $this->assertCount(2, $optimizationInsights);
        $this->assertEquals('Use emotional hooks in headlines', $optimizationInsights[0]['insight']);
        $this->assertEquals('Peak engagement at 9 AM on weekdays', $optimizationInsights[1]['insight']);

        // Test cross-agent patterns
        $patterns = $this->knowledgeBase->getCrossAgentPatterns($agencyId);
        $this->assertArrayHasKey('optimization', $patterns);
        $this->assertEquals(2, $patterns['optimization']['agent_count']);
        $this->assertEquals(2, $patterns['optimization']['insight_count']);
    }

    /**
     * Test 3: AgentOrchestrator collaborative dispatch merges results from multiple agents.
     */
    public function test_dispatch_collaborative_merges_results_from_multiple_agents(): void
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
