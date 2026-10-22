<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgencySettingFactory extends Factory
{
    protected $model = AgencySetting::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'key' => fake()->word(),
            'value' => fake()->sentence(),
        ];
    }
}
