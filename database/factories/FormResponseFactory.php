<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormResponseFactory extends Factory
{
    protected $model = FormResponse::class;

    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'respondent_name' => fake()->name(),
            'respondent_email' => fake()->email(),
            'data' => [],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
