<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomFieldFactory extends Factory
{
    protected $model = CustomField::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'agency_id' => Agency::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'type' => fake()->randomElement(['text', 'number', 'date', 'select', 'textarea', 'boolean']),
            'model_type' => 'App\\Models\\Client',
            'options' => null,
            'is_required' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
