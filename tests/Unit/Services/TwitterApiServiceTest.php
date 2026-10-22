<?php

namespace Tests\Unit\Services;

use App\Services\Social\TwitterApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwitterApiServiceTest extends TestCase
{
    private TwitterApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwitterApiService;
    }

    public function test_authenticate_returns_success_when_credentials_valid(): void
    {
        Http::fake([
            'api.twitter.com/2/users/me*' => Http::response([
                'data' => [
                    'id' => '12345678',
                    'name' => 'Test User',
                    'username' => 'testuser',
                    'public_metrics' => [
                        'followers_count' => 1500,
                        'following_count' => 200,
                        'tweet_count' => 4200,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->authenticate();

        $this->assertTrue($result['success']);
        $this->assertEquals('testuser', $result['data']['data']['username']);
        $this->assertEquals(1500, $result['data']['data']['public_metrics']['followers_count']);
    }

    public function test_get_user_metrics_returns_structured_data(): void
    {
        Http::fake([
            'api.twitter.com/2/users/by/username/*' => Http::response([
                'data' => [
                    'id' => '98765432',
                    'name' => 'Jane Doe',
                    'username' => 'janedoe',
                    'public_metrics' => [
                        'followers_count' => 5000,
                        'following_count' => 300,
                        'tweet_count' => 1000,
                        'listed_count' => 25,
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->getUserMetrics('janedoe');

        $this->assertTrue($result['success']);
        $this->assertEquals('98765432', $result['data']['id']);
        $this->assertEquals('janedoe', $result['data']['username']);
        $this->assertEquals('Jane Doe', $result['data']['name']);
        $this->assertEquals(5000, $result['data']['followers_count']);
        $this->assertEquals(300, $result['data']['following_count']);
        $this->assertEquals(1000, $result['data']['tweet_count']);
        $this->assertEquals(25, $result['data']['listed_count']);
    }
}
