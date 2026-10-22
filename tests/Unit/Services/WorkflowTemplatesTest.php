<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\User;
use App\Services\AI\Agent\AgentInterface;
use App\Services\AI\Agent\AgentMemory;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\Workflows\EmailMarketingWorkflow;
use App\Services\AI\Agent\Workflows\LeadGenerationWorkflow;
use App\Services\AI\Agent\Workflows\SocialMediaStrategyWorkflow;
use App\Services\AI\Agent\Workflows\WeeklyReportWorkflow;
use App\Services\AI\Agent\Workflows\WorkflowRunner;
use App\Services\AI\Gateway\AiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WorkflowTemplatesTest extends TestCase
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

    public function test_email_marketing_workflow_executes_all_steps(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
        ]);

        $this->registerMockAgents([
            'analytics_agent' => ['performance_analysis', 'recommendation'],
            'content_agent' => ['content_generate'],
            'campaign_agent' => ['audience_suggest'],
        ]);

        $template = new EmailMarketingWorkflow;
        $result = $this->runner->run($template, $agency->id, $user->id);

        $this->assertEquals('email_marketing', $result['workflow_name']);
        $this->assertEquals(4, $result['total_steps']);
        $this->assertEquals(4, $result['completed_steps']);
        $this->assertTrue($result['successful']);
        $this->assertCount(4, $result['results']);

        // Verify step sequence
        $this->assertEquals('analytics_agent', $result['results'][0]['agent']);
        $this->assertEquals('performance_analysis', $result['results'][0]['task_type']);

        $this->assertEquals('content_agent', $result['results'][1]['agent']);
        $this->assertEquals('content_generate', $result['results'][1]['task_type']);

        $this->assertEquals('campaign_agent', $result['results'][2]['agent']);
        $this->assertEquals('audience_suggest', $result['results'][2]['task_type']);

        $this->assertEquals('analytics_agent', $result['results'][3]['agent']);
        $this->assertEquals('recommendation', $result['results'][3]['task_type']);
    }

    public function test_social_media_strategy_workflow_executes_all_steps(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
        ]);

        $this->registerMockAgents([
            'analytics_agent' => ['competitor_analysis'],
            'social_media_agent' => ['engagement_analysis', 'post_schedule'],
            'content_agent' => ['content_generate'],
        ]);

        $template = new SocialMediaStrategyWorkflow;
        $result = $this->runner->run($template, $agency->id, $user->id);

        $this->assertEquals('social_media_strategy', $result['workflow_name']);
        $this->assertEquals(4, $result['total_steps']);
        $this->assertEquals(4, $result['completed_steps']);
        $this->assertTrue($result['successful']);
        $this->assertCount(4, $result['results']);

        // Verify step sequence
        $this->assertEquals('analytics_agent', $result['results'][0]['agent']);
        $this->assertEquals('competitor_analysis', $result['results'][0]['task_type']);

        $this->assertEquals('social_media_agent', $result['results'][1]['agent']);
        $this->assertEquals('engagement_analysis', $result['results'][1]['task_type']);

        $this->assertEquals('content_agent', $result['results'][2]['agent']);
        $this->assertEquals('content_generate', $result['results'][2]['task_type']);

        $this->assertEquals('social_media_agent', $result['results'][3]['agent']);
        $this->assertEquals('post_schedule', $result['results'][3]['task_type']);
    }

    public function test_lead_generation_workflow_executes_all_steps(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
        ]);

        $this->registerMockAgents([
            'campaign_agent' => ['audience_suggest', 'campaign_optimize'],
            'content_agent' => ['content_generate'],
            'analytics_agent' => ['recommendation'],
        ]);

        $template = new LeadGenerationWorkflow;
        $result = $this->runner->run($template, $agency->id, $user->id);

        $this->assertEquals('lead_generation', $result['workflow_name']);
        $this->assertEquals(4, $result['total_steps']);
        $this->assertEquals(4, $result['completed_steps']);
        $this->assertTrue($result['successful']);
        $this->assertCount(4, $result['results']);

        // Verify step sequence
        $this->assertEquals('campaign_agent', $result['results'][0]['agent']);
        $this->assertEquals('audience_suggest', $result['results'][0]['task_type']);

        $this->assertEquals('content_agent', $result['results'][1]['agent']);
        $this->assertEquals('content_generate', $result['results'][1]['task_type']);

        $this->assertEquals('campaign_agent', $result['results'][2]['agent']);
        $this->assertEquals('campaign_optimize', $result['results'][2]['task_type']);

        $this->assertEquals('analytics_agent', $result['results'][3]['agent']);
        $this->assertEquals('recommendation', $result['results'][3]['task_type']);
    }

    public function test_weekly_report_workflow_executes_all_steps(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
        ]);

        $this->registerMockAgents([
            'analytics_agent' => ['performance_analysis', 'trend_detection', 'recommendation'],
            'content_agent' => ['content_generate'],
        ]);

        $template = new WeeklyReportWorkflow;
        $result = $this->runner->run($template, $agency->id, $user->id);

        $this->assertEquals('weekly_report', $result['workflow_name']);
        $this->assertEquals(4, $result['total_steps']);
        $this->assertEquals(4, $result['completed_steps']);
        $this->assertTrue($result['successful']);
        $this->assertCount(4, $result['results']);

        // Verify step sequence
        $this->assertEquals('analytics_agent', $result['results'][0]['agent']);
        $this->assertEquals('performance_analysis', $result['results'][0]['task_type']);

        $this->assertEquals('analytics_agent', $result['results'][1]['agent']);
        $this->assertEquals('trend_detection', $result['results'][1]['task_type']);

        $this->assertEquals('analytics_agent', $result['results'][2]['agent']);
        $this->assertEquals('recommendation', $result['results'][2]['task_type']);

        $this->assertEquals('content_agent', $result['results'][3]['agent']);
        $this->assertEquals('content_generate', $result['results'][3]['task_type']);
    }

    /**
     * Register mock agents with the orchestrator.
     *
     * @param  array<string, array<string>>  $agentConfig  Agent name => task types
     */
    private function registerMockAgents(array $agentConfig): void
    {
        foreach ($agentConfig as $agentName => $taskTypes) {
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
    }
}
