<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormFactory extends Factory
{
    protected $model = Form::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->words(2, true).' Form',
            'slug' => fake()->slug(),
            'description' => fake()->sentence(),
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'label' => 'Full Name', 'required' => true],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email Address', 'required' => true],
                ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => false],
            ],
            'is_active' => true,
            'submissions_count' => 0,
        ];
    }
}
