<?php

namespace Database\Factories;

use App\Models\AgentLearningReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentLearningReportFactory extends Factory
{
    protected $model = AgentLearningReport::class;

    public function definition(): array
    {
        return [
            'agent_name' => fake()->randomElement(['content', 'analytics', 'security', 'social', 'support', 'campaign']),
            'improvement_type' => fake()->randomElement(['prompt_optimization', 'cost_reduction', 'accuracy_improvement', 'speed_improvement', 'error_handling']),
            'description' => fake()->sentence(),
            'changes' => ['adjustment' => fake()->word()],
            'status' => fake()->randomElement(['pending', 'applied']),
            'applied_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
