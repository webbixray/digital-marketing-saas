<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ContentGenome;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentGenomeFactory extends Factory
{
    protected $model = ContentGenome::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'platform' => fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin']),
            'optimal_length' => fake()->numberBetween(50, 300),
            'best_hashtags' => ['#marketing', '#socialmedia', '#growth'],
            'best_times' => ['09:00', '12:00', '18:00'],
            'content_themes' => ['educational', 'promotional', 'engagement'],
            'tone_patterns' => ['professional', 'casual'],
            'media_types' => ['image', 'video', 'carousel'],
            'cta_patterns' => ['Learn More', 'Sign Up', 'Shop Now'],
            'engagement_prediction' => ['likes' => 0.8, 'shares' => 0.5],
            'accuracy_score' => fake()->randomFloat(2, 0.5, 1.0),
            'last_updated_at' => now(),
            'data_points_count' => fake()->numberBetween(100, 10000),
            'genome_data' => ['version' => '1.0'],
        ];
    }

    public function highAccuracy(float $minScore = 0.75): static
    {
        return $this->state(['accuracy_score' => $minScore + 0.1]);
    }

    public function recentlyUpdated(int $days = 30): static
    {
        return $this->state(['last_updated_at' => now()->subDays($days - 1)]);
    }
}
