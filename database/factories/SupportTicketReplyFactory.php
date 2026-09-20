<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupportTicketReplyFactory extends Factory
{
    protected $model = SupportTicketReply::class;

    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'user_id' => User::factory(),
            'message' => fake()->paragraph(),
            'is_internal' => fake()->boolean(),
        ];
    }
}
