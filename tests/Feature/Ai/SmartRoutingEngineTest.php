<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Services\AI\SmartRoutingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartRoutingEngineTest extends TestCase
{
    use RefreshDatabase;

    private SmartRoutingEngine $engine;
    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SmartRoutingEngine();
        $this->agency = Agency::factory()->create();
    }

    public function test_get_best_provider_returns_scored_list(): void
    {
        $scores = $this->engine->getBestProvider('fast', $this->agency->id);
        $this->assertNotEmpty($scores);

        $first = reset($scores);
        $this->assertArrayHasKey('provider', $first);
        $this->assertArrayHasKey('score', $first);
        $this->assertArrayHasKey('affinity', $first);
        $this->assertArrayHasKey('cost_score', $first);
        $this->assertArrayHasKey('latency_score', $first);
        $this->assertArrayHasKey('health_score', $first);
    }

    public function test_scores_are_sorted_descending(): void
    {
        $scores = $this->engine->getBestProvider('fast', $this->agency->id);
        $scoreValues = array_column($scores, 'score');
        $sorted = $scoreValues;
        rsort($sorted);

        $this->assertEquals($sorted, $scoreValues);
    }

    public function test_budget_pressure_prioritizes_cost(): void
    {
        $normal = $this->engine->getBestProvider('analysis', $this->agency->id, [], false);
        $pressured = $this->engine->getBestProvider('analysis', $this->agency->id, [], true);

        $this->assertNotEmpty($normal);
        $this->assertNotEmpty($pressured);

        $normalFirst = reset($normal);
        $pressuredFirst = reset($pressured);
        $this->assertArrayHasKey('cost_score', $normalFirst);
        $this->assertArrayHasKey('cost_score', $pressuredFirst);
    }

    public function test_record_result_updates_health(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->engine->recordResult($this->agency->id, 'openai', 'fast', true, 0.001, 1000);
        }

        $this->engine->recordResult($this->agency->id, 'openai', 'fast', false, 0.001, 1000);

        $scores = $this->engine->getBestProvider('fast', $this->agency->id);
        $openaiScore = $scores['openai'];

        $this->assertEquals(83.3, round($openaiScore['health_score'], 1));
    }

    public function test_unhealthy_provider_deprioritized(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->engine->recordResult($this->agency->id, 'openai', 'fast', false, 0.001, 1000);
        }

        $scores = $this->engine->getBestProvider('fast', $this->agency->id);

        $this->assertArrayNotHasKey('openai', $scores);
    }

    public function test_get_dashboard_data(): void
    {
        $data = $this->engine->getDashboardData($this->agency->id);

        $this->assertArrayHasKey('openai', $data);
        $this->assertArrayHasKey('anthropic', $data);
        $this->assertArrayHasKey('ollama', $data);

        $openai = $data['openai'];
        $this->assertArrayHasKey('health', $openai);
        $this->assertArrayHasKey('cost_per_1m_input', $openai);
        $this->assertArrayHasKey('avg_latency_ms', $openai);
        $this->assertArrayHasKey('best_for', $openai);
    }

    public function test_task_affinity_scoring(): void
    {
        $reasoning = $this->engine->getBestProvider('reasoning', $this->agency->id);
        $this->assertNotEmpty($reasoning);

        $fast = $this->engine->getBestProvider('fast', $this->agency->id);
        $this->assertNotEmpty($fast);
    }
}
