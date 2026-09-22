<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatReactionFactory extends Factory
{
    protected $model = ChatReaction::class;

    public function definition(): array
    {
        return [
            'message_id' => ChatMessage::factory(),
            'user_id' => User::factory(),
            'emoji' => fake()->randomElement(['👍', '❤️', '😂', '🎉', '👀']),
        ];
    }
}
