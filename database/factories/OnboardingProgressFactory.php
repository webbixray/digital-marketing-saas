<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\OnboardingProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

class OnboardingProgressFactory extends Factory
{
    protected $model = OnboardingProgress::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'step' => fake()->randomElement(['profile', 'branding', 'team', 'integrations', 'first_post']),
            'completed_at' => null,
            'data' => [],
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'completed_at' => now(),
            'data' => ['completed_by' => 'test'],
        ]);
    }

    public function forStep(string $step): static
    {
        return $this->state(['step' => $step]);
    }
}
