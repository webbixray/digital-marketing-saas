<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AiAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiAuditLogFactory extends Factory
{
    protected $model = AiAuditLog::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['generate', 'translate', 'analyze', 'rewrite', 'summarize']),
            'model_used' => fake()->randomElement(['gpt-4o', 'gpt-4', 'claude-3-opus', 'claude-3-sonnet', 'gemini-pro']),
            'input_hash' => fake()->sha256(),
            'output_hash' => fake()->sha256(),
            'bias_score' => fake()->randomFloat(2, 0, 1),
            'toxicity_score' => fake()->randomFloat(2, 0, 1),
            'compliance_status' => fake()->randomElement(['pass', 'fail', 'warn']),
            'flagged_reason' => fake()->optional()->sentence(),
            'metadata' => [],
        ];
    }

    public function pass(): static
    {
        return $this->state(fn (array $attributes) => [
            'compliance_status' => 'pass',
            'flagged_reason' => null,
            'bias_score' => fake()->randomFloat(2, 0, 0.2),
            'toxicity_score' => fake()->randomFloat(2, 0, 0.15),
        ]);
    }

    public function fail(): static
    {
        return $this->state(fn (array $attributes) => [
            'compliance_status' => 'fail',
            'flagged_reason' => 'High toxicity detected',
            'toxicity_score' => fake()->randomFloat(2, 0.5, 1),
        ]);
    }

    public function warn(): static
    {
        return $this->state(fn (array $attributes) => [
            'compliance_status' => 'warn',
            'flagged_reason' => 'Potential bias detected',
            'bias_score' => fake()->randomFloat(2, 0.3, 0.5),
        ]);
    }
}
