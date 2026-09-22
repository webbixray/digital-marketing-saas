<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgentAccessToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentAccessTokenFactory extends Factory
{
    protected $model = AgentAccessToken::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->word() . '-token',
            'token' => Str::random(64),
            'abilities' => ['read', 'write'],
            'last_used_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
