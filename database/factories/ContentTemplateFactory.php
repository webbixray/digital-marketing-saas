<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ContentTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentTemplateFactory extends Factory
{
    protected $model = ContentTemplate::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'agency_id' => Agency::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'platform' => fake()->randomElement(['twitter', 'facebook', 'instagram', 'linkedin', 'tiktok', 'pinterest']),
            'type' => fake()->randomElement(['post', 'story', 'reel', 'pin', 'article']),
            'template_content' => fake()->paragraph(),
            'variables' => null,
            'hashtags' => null,
            'usage_count' => 0,
            'status' => 'active',
        ];
    }
}
