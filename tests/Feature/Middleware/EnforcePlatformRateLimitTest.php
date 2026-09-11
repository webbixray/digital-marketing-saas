<?php

namespace Tests\Feature\Middleware;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnforcePlatformRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['subscription_plan' => 'free']);
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_it_allows_requests_within_rate_limit(): void
    {
        $response = $this->actingAs($this->user)->get(route('twitter.index'));

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $response = $this->actingAs($this->user)->get(route('twitter.index'));

        $response->assertOk();
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
    }
}
