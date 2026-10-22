<?php

namespace Database\Factories;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFieldValueFactory extends Factory
{
    protected $model = CustomFieldValue::class;

    public function definition(): array
    {
        return [
            'custom_field_id' => CustomField::factory(),
            'entity_type' => 'client',
            'entity_id' => 1,
            'value' => fake()->word(),
        ];
    }
}
