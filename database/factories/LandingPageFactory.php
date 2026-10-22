<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\LandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LandingPageFactory extends Factory
{
    protected $model = LandingPage::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'agency_id' => Agency::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'title' => fake()->sentence(),
            'headline' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'cta_text' => 'Get Started',
            'cta_url' => fake()->url(),
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'button_color' => '#007bff',
            'button_text_color' => '#ffffff',
            'is_published' => fake()->boolean(),
            'views_count' => fake()->numberBetween(0, 1000),
            'clicks_count' => fake()->numberBetween(0, 100),
            'conversions_count' => fake()->numberBetween(0, 10),
            'conversion_rate' => fake()->randomFloat(2, 0, 100),
            'published_at' => null,
        ];
    }
}
