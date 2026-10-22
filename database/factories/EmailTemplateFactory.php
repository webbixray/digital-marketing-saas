<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->slug(),
            'subject' => fake()->sentence(),
            'category' => fake()->randomElement(['welcome', 'notification', 'marketing', 'general']),
            'html_content' => '<h1>'.fake()->sentence().'</h1>',
            'plain_text_content' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
