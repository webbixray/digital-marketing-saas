<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\OptimalPostingTime;
use Illuminate\Database\Eloquent\Factories\Factory;

class OptimalPostingTimeFactory extends Factory
{
    protected $model = OptimalPostingTime::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'platform' => fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin']),
            'day_of_week' => fake()->numberBetween(0, 6),
            'hour' => fake()->numberBetween(0, 23),
            'engagement_score' => fake()->randomFloat(2, 0.1, 1.0),
            'sample_size' => fake()->numberBetween(10, 500),
        ];
    }

    public function forPlatform(string $platform): static
    {
        return $this->state(['platform' => $platform]);
    }

    public function forDay(int $day): static
    {
        return $this->state(['day_of_week' => $day]);
    }
}
