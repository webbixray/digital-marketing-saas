<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards against two classes of routing defect that are easy to reintroduce:
 *
 * 1. Duplicate method+URI pairs — when a later route group re-declares the same
 *    URI, it silently SHADOWS the earlier one. This is how the client-portal API
 *    group lost its auth middleware (an unauthenticated duplicate was registered
 *    last and won).
 * 2. Authenticated API groups that are missing a throttle.
 */
class RouteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_duplicate_method_and_uri_routes_exist(): void
    {
        $seen = [];
        $duplicates = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }
                $key = $method.' '.$route->uri();
                if (isset($seen[$key])) {
                    $duplicates[] = $key;
                }
                $seen[$key] = true;
            }
        }

        $this->assertSame(
            [],
            $duplicates,
            'Duplicate method+URI routes shadow earlier definitions (last one wins): '.implode(', ', $duplicates)
        );
    }

    public function test_client_portal_api_requires_authentication(): void
    {
        // Regression: an unauthenticated duplicate group previously shadowed the
        // authenticated one, exposing these endpoints publicly.
        $response = $this->getJson('/api/v1/client-portal/dashboard');

        $response->assertUnauthorized();
    }

    public function test_client_portal_api_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/client-portal/profile')->assertUnauthorized();
        $this->putJson('/api/v1/client-portal/profile')->assertUnauthorized();
    }

    public function test_authenticated_api_groups_are_rate_limited(): void
    {
        $unthrottled = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! str_starts_with($uri, 'api/')) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $joined = implode(' ', array_map('strval', $middleware));

            $hasAuth = str_contains($joined, 'auth') || str_contains($joined, 'Authenticate');
            $hasThrottle = str_contains($joined, 'throttle') || str_contains($joined, 'Throttle');

            // Only flag routes that ARE authenticated but have no throttle at all.
            if ($hasAuth && ! $hasThrottle) {
                $unthrottled[] = $uri;
            }
        }

        $this->assertSame(
            [],
            $unthrottled,
            'Authenticated API routes must be rate limited: '.implode(', ', $unthrottled)
        );
    }
}
