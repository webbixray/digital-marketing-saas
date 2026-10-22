<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        $name = fake()->words(rand(2, 4), true);

        return [
            'agency_id' => Agency::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.uniqid(),
            'type' => fake()->randomElement(['general', 'product_launch', 'seasonal', 'awareness', 'consideration', 'conversion', 'retention']),
            'status' => fake()->randomElement(['draft', 'active', 'paused', 'completed']),
            'description' => fake()->paragraph(),
            'objective' => fake()->sentence(),
            'target_audience' => fake()->sentence(),
            'client_id' => null,
            'start_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'tags' => fake()->words(rand(1, 3)),
            'cover_image' => null,
            'posts_count' => 0,
            'views_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
            'shares_count' => 0,
            'clicks_count' => 0,
            'estimated_roi' => null,
            'engagement_rate' => null,
        ];
    }
}
