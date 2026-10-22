<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->words(2, true).' Webhook',
            'url' => fake()->url().'/webhook',
            'events' => fake()->randomElements(['post.published', 'post.failed', 'campaign.created', 'client.created', 'invoice.paid'], 2),
            'secret' => fake()->sha256(),
            'is_active' => true,
            'last_triggered_at' => null,
            'last_status_code' => null,
        ];
    }
}
