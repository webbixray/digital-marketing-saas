<?php

namespace Database\Factories;

use App\Models\AbTest;
use App\Models\Agency;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbTestFactory extends Factory
{
    protected $model = AbTest::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'social_account_id' => SocialAccount::factory(),
            'name' => fake()->sentence(3),
            'status' => 'draft',
            'type' => 'content',
            'platform' => fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest']),
            'hypothesis' => fake()->sentence(),
            'variant_a_content' => fake()->paragraph(),
            'variant_b_content' => fake()->paragraph(),
            'sample_size' => 100,
            'started_at' => null,
            'ended_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subDays(7),
            'ended_at' => now(),
        ]);
    }
}
