<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\AgentCostLog;
use App\Services\AI\Agent\AgentCostTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCostTrackerTest extends TestCase
{
    use RefreshDatabase;

    private AgentCostTracker $tracker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tracker = new AgentCostTracker;
    }

    /**
     * Test: record cost creates a log entry.
     */
    public function test_record_cost_creates_log(): void
    {
        $agency = Agency::factory()->create();

        $this->tracker->recordCost(
            agencyId: $agency->id,
            agentName: 'content',
            costUsd: 0.005,
            taskType: 'content_generate',
            tokensUsed: 500,
        );

        $this->assertDatabaseHas('agent_cost_logs', [
            'agency_id' => $agency->id,
            'agent_name' => 'content',
            'task_type' => 'content_generate',
            'tokens_used' => 500,
        ]);

        $log = AgentCostLog::first();
        $this->assertEquals(0.005, $log->cost_usd);
    }

    /**
     * Test: monthly cost calculation.
     */
    public function test_monthly_cost_calculation(): void
    {
        $agency = Agency::factory()->create();

        // Create cost logs for current month
        AgentCostLog::factory()->count(5)->create([
            'agency_id' => $agency->id,
            'cost_usd' => 0.01,
            'executed_at' => now(),
        ]);

        // Create cost logs for previous month (should not count)
        AgentCostLog::factory()->count(3)->create([
            'agency_id' => $agency->id,
            'cost_usd' => 0.05,
            'executed_at' => now()->subMonth(),
        ]);

        $monthlyCost = $this->tracker->getMonthlyCost($agency->id);

        // 5 * 0.01 = 0.05
        $this->assertEqualsWithDelta(0.05, $monthlyCost, 0.001);
    }

    /**
     * Test: budget limit enforcement.
     */
    public function test_budget_limit_enforcement(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'free', // $5 limit
        ]);

        // Cost below limit
        AgentCostLog::factory()->create([
            'agency_id' => $agency->id,
            'cost_usd' => 3.0,
            'executed_at' => now(),
        ]);

        $this->assertFalse($this->tracker->checkBudgetLimit($agency->id));

        // Cost at limit
        AgentCostLog::factory()->create([
            'agency_id' => $agency->id,
            'cost_usd' => 2.0,
            'executed_at' => now(),
        ]);

        $this->assertTrue($this->tracker->checkBudgetLimit($agency->id));
    }

    /**
     * Test: cost trend returns daily data.
     */
    public function test_cost_trend_returns_daily_data(): void
    {
        $agency = Agency::factory()->create();

        // Create cost logs for last 5 days
        for ($i = 0; $i < 5; $i++) {
            AgentCostLog::factory()->create([
                'agency_id' => $agency->id,
                'cost_usd' => 0.01 * ($i + 1),
                'executed_at' => now()->subDays($i),
            ]);
        }

        $trend = $this->tracker->getCostTrend($agency->id, 7);

        $this->assertIsArray($trend);
        // DatePeriod from subDays(7) to now inclusive = 8 days
        $this->assertCount(8, $trend);

        // Each day should have cost_usd and task_count keys
        foreach ($trend as $date => $data) {
            $this->assertArrayHasKey('cost_usd', $data);
            $this->assertArrayHasKey('task_count', $data);
        }
    }
}
