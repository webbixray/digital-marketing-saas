<?php

namespace Database\Factories;

use App\Models\WorkflowTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowTemplateFactory extends Factory
{
    protected $model = WorkflowTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'slug' => fake()->slug(),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['social', 'email', 'automation']),
            'icon' => 'fas fa-cog',
            'nodes' => [],
            'connections' => [],
            'is_public' => true,
            'is_active' => true,
            'usage_count' => 0,
        ];
    }
}
