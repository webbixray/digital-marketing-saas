<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\EnforcePlatformRateLimit;
use App\Models\Agency;
use App\Models\User;
use App\Services\Social\PlatformRateLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
        $rateLimitService = app(PlatformRateLimitService::class);
        $middleware = new EnforcePlatformRateLimit($rateLimitService);

        $request = Request::create('/twitter', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $middleware->handle($request, fn () => response('OK'));

        $this->assertEquals(200, $response->status());
        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $rateLimitService = app(PlatformRateLimitService::class);
        $middleware = new EnforcePlatformRateLimit($rateLimitService);

        $request = Request::create('/twitter', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $middleware->handle($request, fn () => response('OK'));

        $this->assertNotNull($response->headers->get('X-RateLimit-Limit'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Remaining'));
    }
}
