<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientNotificationFactory extends Factory
{
    protected $model = ClientNotification::class;

    public function definition(): array
    {
        $types = ['campaign_update', 'invoice_generated', 'content_published', 'approval_required', 'report_ready'];

        return [
            'client_id' => Client::factory(),
            'type' => fake()->randomElement($types),
            'title' => fake()->sentence(6),
            'body' => fake()->paragraph(),
            'action_url' => fake()->url(),
            'is_read' => false,
            'read_at' => null,
            'metadata' => null,
            'created_at' => now(),
        ];
    }

    public function read(): static
    {
        return $this->state([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function forType(string $type): static
    {
        return $this->state(['type' => $type]);
    }
}
