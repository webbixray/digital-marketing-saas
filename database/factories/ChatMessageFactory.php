<?php

namespace Database\Factories;

use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        return [
            'channel_id' => ChatChannel::factory(),
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'type' => 'text',
            'file_url' => null,
            'file_name' => null,
            'file_type' => null,
            'file_size' => null,
            'reply_to_id' => null,
            'is_edited' => false,
            'is_deleted' => false,
        ];
    }

    public function edited(): static
    {
        return $this->state(['is_edited' => true]);
    }

    public function deleted(): static
    {
        return $this->state(['is_deleted' => true]);
    }

    public function withFile(): static
    {
        return $this->state([
            'file_url' => 'https://example.com/file.pdf',
            'file_name' => 'file.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);
    }
}
