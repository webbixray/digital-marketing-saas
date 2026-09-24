<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditTransactionFactory extends Factory
{
    protected $model = CreditTransaction::class;

    public function definition(): array
    {
        $amount = fake()->numberBetween(100, 10000);

        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'type' => 'purchase',
            'amount' => $amount,
            'balance_after' => $amount,
            'description' => fake()->sentence(),
            'metadata' => ['source' => 'factory'],
        ];
    }

    public function usage(): static
    {
        return $this->state([
            'type' => 'usage',
            'amount' => fake()->numberBetween(-5000, -100),
            'balance_after' => fake()->numberBetween(0, 5000),
        ]);
    }

    public function refund(): static
    {
        return $this->state([
            'type' => 'refund',
            'amount' => fake()->numberBetween(50, 2000),
        ]);
    }

    public function bonus(): static
    {
        return $this->state([
            'type' => 'bonus',
            'amount' => fake()->numberBetween(50, 500),
        ]);
    }
}
