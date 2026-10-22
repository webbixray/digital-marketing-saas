<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\WhiteLabelSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhiteLabelSettingFactory extends Factory
{
    protected $model = WhiteLabelSetting::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'brand_name' => fake()->company(),
            'brand_color' => fake()->hexColor(),
            'logo_url' => fake()->imageUrl(),
            'favicon_url' => fake()->imageUrl(),
            'from_name' => fake()->name(),
            'from_email' => fake()->safeEmail(),
            'custom_css' => null,
            'email_signature' => fake()->sentence(),
            'hide_powered_by' => fake()->boolean(),
            'enabled' => fake()->boolean(),
        ];
    }
}
