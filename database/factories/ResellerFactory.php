<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Reseller;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ResellerFactory extends Factory
{
    protected $model = Reseller::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'agency_id' => Agency::factory(),
            'name' => $name,
            'slug' => Reseller::generateSlug($name),
            'domain' => fake()->domainName(),
            'logo_url' => fake()->imageUrl(200, 200),
            'primary_color' => fake()->hexColor(),
            'is_active' => true,
            'commission_rate' => 20.00,
            'commission_type' => Reseller::COMMISSION_TYPE_PERCENTAGE,
            'billing_type' => Reseller::BILLING_TYPE_REVENUE_SHARE,
            'settings' => [
                'brand_name' => $name,
                'brand_color' => fake()->hexColor(),
                'logo_url' => fake()->imageUrl(200, 200),
                'from_name' => $name,
                'from_email' => fake()->safeEmail(),
                'hide_powered_by' => false,
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withFixedCommission(): static
    {
        return $this->state([
            'commission_type' => Reseller::COMMISSION_TYPE_FIXED,
            'billing_type' => Reseller::BILLING_TYPE_MONTHLY,
        ]);
    }
}
