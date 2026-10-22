<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\SearchHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchHistoryFactory extends Factory
{
    protected $model = SearchHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'agency_id' => Agency::factory(),
            'query' => fake()->words(3, true),
            'type' => fake()->randomElement(['all', 'posts', 'campaigns', 'clients', 'content', 'results']),
            'results_count' => fake()->numberBetween(0, 50),
            'clicked_result' => fake()->boolean(30) ? [
                'type' => fake()->randomElement(['post', 'campaign', 'client']),
                'id' => fake()->numberBetween(1, 100),
            ] : null,
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
