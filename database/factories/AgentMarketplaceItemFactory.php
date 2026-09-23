<?php

namespace Database\Factories;

use App\Models\AgentMarketplaceCategory;
use App\Models\AgentMarketplaceItem;
use App\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentMarketplaceItemFactory extends Factory
{
    protected $model = AgentMarketplaceItem::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        return [
            'agency_id' => Agency::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(3),
            'description' => fake()->paragraph(),
            'category_id' => AgentMarketplaceCategory::factory(),
            'tags' => fake()->words(3),
            'icon' => 'fas fa-' . fake()->word(),
            'screenshots' => [fake()->imageUrl(), fake()->imageUrl()],
            'demo_url' => fake()->url(),
            'pricing_type' => fake()->randomElement(['free', 'paid', 'pricing_tiers']),
            'pricing_config' => ['price' => fake()->numberBetween(9, 99)],
            'features' => [fake()->sentence(), fake()->sentence()],
            'requirements' => [fake()->sentence()],
            'install_count' => fake()->numberBetween(0, 5000),
            'rating_avg' => fake()->randomFloat(2, 1, 5),
            'rating_count' => fake()->numberBetween(0, 500),
            'is_featured' => fake()->boolean(20),
            'is_approved' => true,
            'status' => 'approved',
            'published_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'is_approved' => false]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'is_approved' => false]);
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function free(): static
    {
        return $this->state(['pricing_type' => 'free']);
    }
}
