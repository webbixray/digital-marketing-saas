<?php

namespace Tests\Unit\Services\AI;

use App\Services\AI\Agent\AgentCostTracker;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentCostTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_zero_for_new_agency(): void
    {
        $agency = Agency::factory()->create();
        $tracker = new AgentCostTracker();
        $cost = $tracker->getMonthlyCost($agency->id);
        $this->assertEquals(0, $cost);
    }

    public function test_returns_cost_for_agency_with_logs(): void
    {
        $agency = Agency::factory()->create();
        $tracker = new AgentCostTracker();
        
        // Create some cost logs
        for ($i = 0; $i < 5; $i++) {
            \App\Models\AgentCostLog::create([
                'agency_id' => $agency->id,
                'agent_name' => 'content_agent',
                'task_type' => 'content_generate',
                'cost_usd' => 0.01,
                'executed_at' => now(),
            ]);
        }
        $cost = $tracker->getMonthlyCost($agency->id);
        $this->assertEquals(0.05, $cost);
    }

    public function test_get_cost_by_agent_returns_array(): void
    {
        $agency = Agency::factory()->create();
        $tracker = new AgentCostTracker();
        $costs = $tracker->getCostByAgent($agency->id);
        $this->assertIsArray($costs);
    }

    public function test_get_budget_limit_returns_numeric(): void
    {
        $agency = Agency::factory()->create();
        $tracker = new AgentCostTracker();
        $limit = $tracker->getBudgetLimit($agency->id);
        $this->assertIsNumeric($limit);
    }

    public function test_get_remaining_budget_returns_numeric(): void
    {
        $agency = Agency::factory()->create();
        $tracker = new AgentCostTracker();
        $remaining = $tracker->getRemainingBudget($agency->id);
        $this->assertIsNumeric($remaining);
    }
}
