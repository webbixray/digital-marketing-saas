<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SocialPostPublished;
use App\Notifications\SocialAccountDisconnected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_post_notification(): void
    {
        $user = User::factory()->create();
        $notification = new SocialPostPublished('facebook', '123', 'Test Post');

        $this->assertEquals(['database', 'broadcast'], $notification->via($user));

        $array = $notification->toArray($user);
        $this->assertEquals('post_published', $array['type']);
        $this->assertEquals('facebook', $array['platform']);
        $this->assertEquals('123', $array['post_id']);
    }

    public function test_social_account_disconnected_notification(): void
    {
        $user = User::factory()->create();
        $notification = new SocialAccountDisconnected('twitter', 'testuser');

        $this->assertEquals(['database', 'broadcast'], $notification->via($user));

        $array = $notification->toArray($user);
        $this->assertEquals('account_disconnected', $array['type']);
        $this->assertEquals('twitter', $array['platform']);
        $this->assertEquals('testuser', $array['account_name']);
    }
}
