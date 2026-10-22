<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeatureFlagFactory extends Factory
{
    protected $model = FeatureFlag::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'feature_key' => fake()->unique()->slug(),
            'feature_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'enabled' => true,
            'required_plan' => 0,
            'minimum_version' => null,
            'allowed_roles' => null,
            'settings' => null,
        ];
    }
}
