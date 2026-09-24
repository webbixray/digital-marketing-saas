<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\MeteredUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeteredUsageFactory extends Factory
{
    protected $model = MeteredUsage::class;

    public function definition(): array
    {
        $metrics = ['ai_tokens', 'storage_gb', 'api_calls', 'sms_sent', 'emails_sent'];
        $metric = fake()->randomElement($metrics);
        $quantity = fake()->randomFloat(4, 1, 10000);
        $unitPrice = fake()->randomFloat(8, 0.001, 0.1);

        return [
            'agency_id' => Agency::factory(),
            'metric' => 'ai_tokens',
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'recorded_at' => now(),
        ];
    }
}
