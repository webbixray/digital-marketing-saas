<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\RoleMiddleware;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(?User $user = null): Request
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn() => $user);
        return $request;
    }

    public function test_role_middleware_allows_owner(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $request = $this->makeRequest($user);
        $middleware = new RoleMiddleware();

        $response = $middleware->handle($request, fn() => response('OK'), 'owner');

        $this->assertEquals(200, $response->status());
    }

    public function test_role_middleware_allows_admin(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);

        $request = $this->makeRequest($user);
        $middleware = new RoleMiddleware();

        $response = $middleware->handle($request, fn() => response('OK'), 'admin');

        $this->assertEquals(200, $response->status());
    }

    public function test_role_middleware_rejects_member_for_admin_routes(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);

        $request = $this->makeRequest($user);
        $middleware = new RoleMiddleware();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, fn() => response('OK'), 'admin');
    }

    public function test_role_middleware_rejects_guest(): void
    {
        $request = $this->makeRequest(null);
        $middleware = new RoleMiddleware();

        $response = $middleware->handle($request, fn() => response('OK'), 'owner');

        $this->assertEquals(302, $response->status());
    }

    public function test_role_middleware_handles_multiple_roles(): void
    {
        $agency = Agency::factory()->create();
        $owner = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);
        $admin = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);
        $member = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);

        $middleware = new RoleMiddleware();

        // Owner should pass role:owner|admin
        $response = $middleware->handle($this->makeRequest($owner), fn() => response('OK'), 'owner', 'admin');
        $this->assertEquals(200, $response->status());

        // Admin should pass role:owner|admin
        $response = $middleware->handle($this->makeRequest($admin), fn() => response('OK'), 'owner', 'admin');
        $this->assertEquals(200, $response->status());

        // Member should NOT pass role:owner|admin
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($this->makeRequest($member), fn() => response('OK'), 'owner', 'admin');
    }
}
