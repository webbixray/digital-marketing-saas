<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_webhook_verification(): void
    {
        config(['services.facebook.webhook_verify_token' => 'test_token']);

        $response = $this->get('/webhook/facebook?hub_mode=subscribe&hub_verify_token=test_token&hub_challenge=12345');
        $response->assertStatus(200);
        $response->assertContent('12345');
    }

    public function test_facebook_webhook_handles_feed_comment(): void
    {
        $agency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $agency->id,
            'platform' => 'facebook',
            'platform_account_id' => '123456',
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '123456',
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'comment',
                                'comment_id' => 'comment_1',
                                'from' => ['name' => 'John Doe', 'id' => 'user_1'],
                                'message' => 'Great post!',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhook/facebook', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('inbox_messages', [
            'agency_id' => $agency->id,
            'platform' => 'facebook',
            'message_type' => 'comment',
            'author_name' => 'John Doe',
        ]);
    }

    public function test_linkedin_webhook_verification(): void
    {
        $response = $this->get('/webhook/linkedin?challengeCode=test_challenge');
        $response->assertStatus(200);
    }

    public function test_tiktok_webhook_verification(): void
    {
        $response = $this->get('/webhook/tiktok?challenge=test_challenge');
        $response->assertStatus(200);
    }

    public function test_youtube_webhook_verification(): void
    {
        $response = $this->get('/webhook/youtube?hub_challenge=test_challenge');
        $response->assertStatus(200);
    }
}
