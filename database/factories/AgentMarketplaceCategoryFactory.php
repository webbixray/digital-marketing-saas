<?php

namespace Database\Factories;

use App\Models\AgentMarketplaceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentMarketplaceCategoryFactory extends Factory
{
    protected $model = AgentMarketplaceCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . fake()->unique()->randomNumber(3),
            'description' => fake()->sentence(),
            'icon' => 'fas fa-' . fake()->word(),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'agent_count' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
