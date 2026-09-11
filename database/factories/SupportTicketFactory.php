<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => fake()->randomElement(['open', 'in_progress', 'resolved', 'closed']),
            'category' => fake()->randomElement(['technical', 'billing', 'general', 'feature_request']),
            'ticket_number' => fake()->unique()->numerify('TKT-#####'),
        ];
    }
}
