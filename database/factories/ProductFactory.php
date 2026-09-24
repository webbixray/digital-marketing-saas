<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'agency_id' => Agency::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . uniqid(),
            'description' => fake()->sentence(),
            'sku' => fake()->unique()->bothify('???-#####'),
            'price' => fake()->randomFloat(2, 5, 500),
            'sale_price' => null,
            'currency' => 'USD',
            'inventory_count' => fake()->numberBetween(0, 500),
            'low_stock_threshold' => 10,
            'status' => fake()->randomElement(['active', 'draft', 'discontinued']),
            'images' => null,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => 'active']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function discontinued(): static
    {
        return $this->state(['status' => 'discontinued']);
    }

    public function onSale(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'sale_price' => round($attributes['price'] * 0.8, 2),
            ];
        });
    }
}
