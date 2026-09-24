<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Reseller;
use App\Models\ResellerCommission;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResellerCommissionFactory extends Factory
{
    protected $model = ResellerCommission::class;

    public function definition(): array
    {
        $reseller = Reseller::factory()->create();

        return [
            'reseller_id' => $reseller->id,
            'agency_id' => $reseller->agency_id,
            'amount' => fake()->randomFloat(2, 50, 5000),
            'type' => ResellerCommission::TYPE_SIGNUP,
            'status' => ResellerCommission::STATUS_PENDING,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status' => ResellerCommission::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function reversed(): static
    {
        return $this->state([
            'status' => ResellerCommission::STATUS_REVERSED,
        ]);
    }

    public function renewal(): static
    {
        return $this->state([
            'type' => ResellerCommission::TYPE_RENEWAL,
        ]);
    }

    public function revenue(): static
    {
        return $this->state([
            'type' => ResellerCommission::TYPE_REVENUE,
        ]);
    }
}
