<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'commentable_type' => 'App\\Models\\SocialPost',
            'commentable_id' => fake()->numberBetween(1, 100),
            'body' => fake()->paragraph(),
            'mentions' => null,
            'parent_id' => null,
        ];
    }
}
