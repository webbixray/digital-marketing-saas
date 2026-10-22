<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'event_type' => fake()->randomElement(['post.published', 'post.failed', 'invoice.paid']),
            'payload' => ['data' => fake()->word()],
            'ip_address' => fake()->ipv4(),
            'status' => fake()->randomElement(['received', 'processed', 'failed']),
        ];
    }
}
