<?php

namespace Database\Factories;

use App\Models\AgentPerformanceLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentPerformanceLogFactory extends Factory
{
    protected $model = AgentPerformanceLog::class;

    public function definition(): array
    {
        return [
            'agent_name' => fake()->randomElement(['content', 'analytics', 'security', 'social', 'support', 'campaign']),
            'agent_category' => fake()->randomElement(['Content', 'Analytics', 'Security', 'Social', 'Support', 'Campaigns']),
            'metric_type' => fake()->randomElement(['response_time', 'accuracy', 'cost_efficiency', 'success_rate']),
            'metric_value' => fake()->randomFloat(4, 0.1, 0.99),
            'parameters_used' => ['model' => fake()->word()],
            'metadata' => ['iteration' => fake()->numberBetween(1, 100)],
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
