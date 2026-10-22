<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AiContentLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiContentLogFactory extends Factory
{
    protected $model = AiContentLog::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'provider' => fake()->randomElement(['openai', 'google', 'anthropic', 'azure']),
            'model' => fake()->randomElement(['gpt-4o', 'gpt-4', 'claude-3-opus', 'claude-3-sonnet', 'gemini-pro']),
            'action' => fake()->randomElement(['generate', 'rewrite', 'summarize', 'translate', 'hashtags', 'ideas']),
            'content_type' => fake()->randomElement(['post', 'email', 'article', 'caption', 'hashtags', 'ideas']),
            'prompt' => fake()->sentence(10),
            'response' => fake()->paragraph(3),
            'total_tokens' => fake()->numberBetween(100, 5000),
            'prompt_tokens' => fake()->numberBetween(50, 2000),
            'completion_tokens' => fake()->numberBetween(50, 3000),
            'cost_usd' => fake()->randomFloat(4, 0, 5.00),
            'status' => fake()->randomElement(['success', 'failed']),
            'error_message' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'error_message' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'AI generation failed',
        ]);
    }

    public function generate(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'generate',
            'content_type' => 'post',
        ]);
    }

    public function hashtags(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'hashtags',
            'content_type' => 'hashtags',
        ]);
    }

    public function ideas(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'ideas',
            'content_type' => 'ideas',
        ]);
    }
}
