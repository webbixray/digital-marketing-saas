<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientSubscriptionFactory extends Factory
{
    protected $model = ClientSubscription::class;

    public function definition(): array
    {
        $plans = ['basic', 'starter', 'professional', 'enterprise'];
        $plan = fake()->randomElement($plans);

        $prices = [
            'basic' => 49,
            'starter' => 99,
            'professional' => 199,
            'enterprise' => 499,
        ];

        return [
            'agency_id' => Agency::factory(),
            'client_id' => Client::factory(),
            'plan_name' => $plan,
            'price' => $prices[$plan],
            'interval' => 'month',
            'status' => 'active',
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'end_date' => null,
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function withPlan(string $plan): static
    {
        return $this->state(['plan_name' => $plan]);
    }
}
