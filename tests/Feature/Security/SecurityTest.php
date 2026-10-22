<?php

namespace Tests\Feature\Security;

use App\Models\Agency;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prevents_xss_in_forms(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create(['agency_id' => $agency->id]);
        $response = $this->actingAs($user)->post(route('clients.store'), [
            'name' => '<script>alert("xss")</script>',
            'email' => 'test@example.com',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseMissing('clients', ['name' => '<script>alert("xss")</script>']);
    }

    public function test_it_prevents_cross_tenant_access(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        $user1 = User::factory()->create(['agency_id' => $agency1->id]);
        $client = Client::factory()->create(['agency_id' => $agency2->id]);
        $response = $this->actingAs($user1)->get(route('clients.show', $client));
        $response->assertForbidden();
    }

    public function test_web_routes_are_protected_by_csrf_middleware(): void
    {
        // Laravel's PreventRequestForgery middleware skips token validation
        // while running unit tests (runningUnitTests()), so a 419 response
        // cannot be triggered from the test environment. Instead, verify the
        // CSRF middleware is actually applied to the web routes.
        $route = app('router')->getRoutes()->getByName('clients.store');

        $this->assertNotNull($route, 'clients.store route exists');

        $resolved = collect($route->gatherMiddleware())
            ->flatMap(function ($middleware) {
                // Expand group references (e.g. 'web') to their class list
                if (is_string($middleware) && str_starts_with($middleware, 'web')) {
                    return app('router')->getMiddlewareGroups()['web'] ?? [];
                }

                return [$middleware];
            })
            ->flatMap(function ($middleware) {
                // Resolve aliases to class names
                if (is_string($middleware)) {
                    $alias = explode(':', $middleware)[0];

                    return [app('router')->getMiddleware()[$alias] ?? $middleware];
                }

                return [$middleware];
            })
            ->all();

        $this->assertContains(
            PreventRequestForgery::class,
            $resolved,
            'clients.store is protected by CSRF middleware'
        );
    }

    public function test_it_hashes_passwords(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);
        $this->assertNotEquals('secret123', $user->password);
        $this->assertTrue(\Hash::check('secret123', $user->password));
    }
}
