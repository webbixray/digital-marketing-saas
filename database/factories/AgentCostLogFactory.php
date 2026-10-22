<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgentCostLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentCostLogFactory extends Factory
{
    protected $model = AgentCostLog::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'agent_name' => fake()->randomElement(['content', 'analytics', 'security', 'social', 'support', 'campaign']),
            'task_type' => fake()->randomElement(['content_generate', 'content_optimize', 'performance_analysis', 'trend_detection', 'security_audit']),
            'cost_usd' => fake()->randomFloat(6, 0.001, 0.1),
            'tokens_used' => fake()->numberBetween(100, 5000),
            'executed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
