<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ChatChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChatChannelFactory extends Factory
{
    protected $model = ChatChannel::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'agency_id' => Agency::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name) . '-' . uniqid(),
            'description' => fake()->sentence(),
            'type' => 'public',
            'created_by' => User::factory(),
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(['is_archived' => true]);
    }

    public function private(): static
    {
        return $this->state(['type' => 'private']);
    }

    public function public(): static
    {
        return $this->state(['type' => 'public']);
    }
}
