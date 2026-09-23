<?php

namespace Database\Factories;

use App\Models\AgentMarketplaceItem;
use App\Models\AgentMarketplaceReview;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentMarketplaceReviewFactory extends Factory
{
    protected $model = AgentMarketplaceReview::class;

    public function definition(): array
    {
        return [
            'item_id' => AgentMarketplaceItem::factory(),
            'user_id' => User::factory(),
            'agency_id' => Agency::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'is_verified_purchase' => fake()->boolean(70),
            'helpful_count' => fake()->numberBetween(0, 50),
            'status' => 'approved',
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }

    public function verified(): static
    {
        return $this->state(['is_verified_purchase' => true]);
    }
}
