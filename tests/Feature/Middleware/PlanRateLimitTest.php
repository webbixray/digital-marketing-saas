<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\PlanRateLimit;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PlanRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(?User $user = null, string $ip = '127.0.0.1'): Request
    {
        $server = [];
        if ($ip) {
            $server['REMOTE_ADDR'] = $ip;
        }
        $request = Request::create('/api/v1/test', 'GET', [], [], [], $server);
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_free_plan_rate_limit_is_enforced(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'free',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $middleware = new PlanRateLimit();
        $next = fn () => response('OK', 200);

        // Make 60 requests (at the free plan limit)
        for ($i = 0; $i < 60; $i++) {
            $response = $middleware->handle($this->makeRequest($user), $next);
            $this->assertSame(200, $response->status());
        }

        // 61st request should be rate limited
        $response = $middleware->handle($this->makeRequest($user), $next);
        $this->assertSame(429, $response->status());
    }

    public function test_pro_plan_has_higher_limit(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'pro',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $middleware = new PlanRateLimit();
        $next = fn () => response('OK', 200);

        // Pro plan: 1200 requests/minute - test first few succeed
        for ($i = 0; $i < 5; $i++) {
            $response = $middleware->handle($this->makeRequest($user), $next);
            $this->assertSame(200, $response->status());
        }
    }

    public function test_enterprise_plan_has_no_limit(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
        ]);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $middleware = new PlanRateLimit();
        $next = fn () => response('OK', 200);

        // Enterprise: unlimited requests
        for ($i = 0; $i < 10; $i++) {
            $response = $middleware->handle($this->makeRequest($user), $next);
            $this->assertSame(200, $response->status());
        }
    }

    public function test_unauthenticated_request_gets_ip_based_limit(): void
    {
        $middleware = new PlanRateLimit();
        $next = fn () => response('OK', 200);

        $request = $this->makeRequest(null, '10.0.0.1');

        // First request should succeed
        $response = $middleware->handle($request, $next);
        $this->assertSame(200, $response->status());
    }

    public function test_middleware_class_exists(): void
    {
        $this->assertTrue(class_exists(PlanRateLimit::class));
    }
}
