<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialPost;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialCommentFactory extends Factory
{
    protected $model = SocialComment::class;

    public function definition(): array
    {
        $platform = fake()->randomElement(['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest']);
        $isReplied = fake()->boolean(30);

        return [
            'agency_id' => Agency::factory(),
            'social_post_id' => SocialPost::factory(),
            'social_account_id' => SocialAccount::factory(),
            'platform' => $platform,
            'platform_comment_id' => fake()->uuid(),
            'author_name' => fake()->name(),
            'author_id' => fake()->uuid(),
            'content' => fake()->sentence(rand(5, 20)),
            'parent_id' => null,
            'is_replied' => $isReplied,
            'replied_at' => $isReplied ? fake()->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    public function replied(): static
    {
        return $this->state([
            'is_replied' => true,
            'replied_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function unreplied(): static
    {
        return $this->state([
            'is_replied' => false,
            'replied_at' => null,
        ]);
    }

    public function topLevel(): static
    {
        return $this->state([
            'parent_id' => null,
        ]);
    }

    public function reply(SocialComment $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            $comment = $parent ?? SocialComment::factory()->create();

            return [
                'parent_id' => $comment->id,
                'social_post_id' => $comment->social_post_id,
                'social_account_id' => $comment->social_account_id,
                'platform' => $comment->platform,
            ];
        });
    }

    public function facebook(): static
    {
        return $this->state(['platform' => 'facebook']);
    }

    public function instagram(): static
    {
        return $this->state(['platform' => 'instagram']);
    }

    public function twitter(): static
    {
        return $this->state(['platform' => 'twitter']);
    }

    public function linkedin(): static
    {
        return $this->state(['platform' => 'linkedin']);
    }

    public function tiktok(): static
    {
        return $this->state(['platform' => 'tiktok']);
    }
}
