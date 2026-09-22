<?php

namespace Database\Factories;

use App\Models\AgentSharedKnowledge;
use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentSharedKnowledgeFactory extends Factory
{
    protected $model = AgentSharedKnowledge::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'from_agent' => fake()->randomElement(['content', 'analytics', 'security', 'social', 'support', 'campaign']),
            'insight' => fake()->sentence(),
            'category' => fake()->randomElement(['trend', 'pattern', 'optimization', 'anomaly', 'best_practice']),
            'confidence' => fake()->randomFloat(4, 0.5, 0.99),
        ];
    }
}
