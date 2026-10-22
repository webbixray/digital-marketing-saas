<?php

namespace Database\Factories;

use App\Models\InboxMessage;
use App\Models\InboxTriage;
use Illuminate\Database\Eloquent\Factories\Factory;

class InboxTriageFactory extends Factory
{
    protected $model = InboxTriage::class;

    public function definition(): array
    {
        return [
            'message_id' => InboxMessage::factory(),
            'category' => fake()->randomElement(['lead', 'support', 'feedback', 'spam']),
            'priority' => fake()->randomElement(['high', 'medium', 'low']),
            'notes' => fake()->sentence(),
        ];
    }
}
