<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Product;
use App\Models\SocialCommercePost;
use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialCommercePostFactory extends Factory
{
    protected $model = SocialCommercePost::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'social_post_id' => SocialPost::factory(),
            'product_id' => Product::factory(),
            'shop_url' => fake()->url() . '/shop',
            'discount_code' => fake()->optional()->bothify('SAVE##'),
            'utm_params' => [
                'utm_source' => 'social',
                'utm_medium' => 'social_commerce',
                'utm_campaign' => fake()->word(),
            ],
            'clicks' => fake()->numberBetween(0, 500),
            'conversions' => fake()->numberBetween(0, 50),
            'revenue' => fake()->randomFloat(2, 0, 5000),
            'created_at' => fake()->dateTimeThisYear(),
        ];
    }
}
