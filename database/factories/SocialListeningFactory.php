<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\SocialListening;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialListeningFactory extends Factory
{
    protected $model = SocialListening::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'keyword' => fake()->word(),
            'platform' => fake()->randomElement(['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok']),
            'is_active' => fake()->boolean(),
            'last_checked_at' => null,
            'match_count' => fake()->numberBetween(0, 1000),
            'sentiment_positive' => fake()->numberBetween(0, 500),
            'sentiment_negative' => fake()->numberBetween(0, 200),
            'sentiment_neutral' => fake()->numberBetween(0, 300),
        ];
    }
}
