<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ClientPortalSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPortalSettingFactory extends Factory
{
    protected $model = ClientPortalSetting::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'brand_name' => fake()->company(),
            'brand_color' => fake()->hexColor(),
            'logo_url' => fake()->imageUrl(),
            'custom_domain' => fake()->domainName(),
            'is_enabled' => true,
            'show_analytics' => true,
            'show_invoices' => true,
            'allow_approvals' => true,
            'show_team_activity' => false,
            'welcome_message' => fake()->sentence(),
        ];
    }

    public function enabled(): static
    {
        return $this->state(['is_enabled' => true]);
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }
}
