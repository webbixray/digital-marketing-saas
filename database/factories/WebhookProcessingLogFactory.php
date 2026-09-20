<?php

namespace Database\Factories;

use App\Models\WebhookProcessingLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookProcessingLogFactory extends Factory
{
    protected $model = WebhookProcessingLog::class;

    public function definition(): array
    {
        return [
            'platform' => fake()->randomElement(['facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'tiktok', 'zapier']),
            'webhook_id' => fake()->uuid(),
            'event_type' => fake()->randomElement(['post.published', 'post.failed', 'message.received', 'page.mention', 'comment.created']),
            'signature' => fake()->sha256(),
            'signature_valid' => fake()->boolean(),
            'payload' => ['data' => fake()->word()],
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'dead_letter']),
            'attempt' => fake()->numberBetween(0, 5),
            'max_attempts' => 3,
            'error_message' => null,
            'response_time_ms' => fake()->numberBetween(50, 5000),
            'processed_at' => null,
            'failed_at' => null,
        ];
    }
}
