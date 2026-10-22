<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ZapierSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZapierSubscriptionFactory extends Factory
{
    protected $model = ZapierSubscription::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'webhook_url' => fake()->url(),
            'trigger_type' => fake()->randomElement(['post.created', 'post.published', 'post.failed', 'campaign.completed', 'invoice.paid']),
            'is_active' => fake()->boolean(),
        ];
    }
}
