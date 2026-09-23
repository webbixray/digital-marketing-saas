<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\Enforce2FA;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Enforce2FATest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(?User $user = null, bool $expectsJson = false): Request
    {
        $server = $expectsJson ? ['HTTP_ACCEPT' => 'application/json'] : [];
        $request = Request::create('/test', 'GET', [], [], [], $server);
        $request->setUserResolver(fn() => $user);
        return $request;
    }

    public function test_enforce_2fa_allows_verified_2fa(): void
    {
        $agency = Agency::factory()->create([
            'custom_settings' => ['enforce_2fa' => true],
        ]);

        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'two_factor_enabled' => true,
        ]);

        $middleware = new Enforce2FA();
        $response = $middleware->handle($this->makeRequest($user), fn() => response('OK'));

        $this->assertEquals(200, $response->status());
    }

    public function test_enforce_2fa_redirects_unverified_2fa(): void
    {
        $agency = Agency::factory()->create([
            'custom_settings' => ['enforce_2fa' => true],
        ]);

        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'two_factor_enabled' => false,
        ]);

        $middleware = new Enforce2FA();
        $response = $middleware->handle($this->makeRequest($user), fn() => response('OK'));

        $this->assertEquals(302, $response->status());
    }

    public function test_enforce_2fa_allows_users_without_2fa_enabled_when_agency_does_not_enforce(): void
    {
        $agency = Agency::factory()->create([
            'custom_settings' => ['enforce_2fa' => false],
        ]);

        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'two_factor_enabled' => false,
        ]);

        $middleware = new Enforce2FA();
        $response = $middleware->handle($this->makeRequest($user), fn() => response('OK'));

        $this->assertEquals(200, $response->status());
    }

    public function test_enforce_2fa_requires_auth(): void
    {
        $middleware = new Enforce2FA();
        $response = $middleware->handle($this->makeRequest(null), fn() => response('OK'));

        $this->assertEquals(200, $response->status());
    }

    public function test_enforce_2fa_returns_json_for_api_requests(): void
    {
        $agency = Agency::factory()->create([
            'custom_settings' => ['enforce_2fa' => true],
        ]);

        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'two_factor_enabled' => false,
        ]);

        $middleware = new Enforce2FA();
        $response = $middleware->handle($this->makeRequest($user, true), fn() => response('OK'));

        $this->assertEquals(403, $response->status());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Two-factor authentication is required. Please enable it in your settings.', $data['message']);
    }

    public function test_enforce_2fa_allows_when_agency_has_no_settings(): void
    {
        $agency = Agency::factory()->create([
            'custom_settings' => [],
        ]);

        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'two_factor_enabled' => false,
        ]);

        $middleware = new Enforce2FA();
        $response = $middleware->handle($this->makeRequest($user), fn() => response('OK'));

        $this->assertEquals(200, $response->status());
    }

    public function test_enforce_2fa_passes_through_for_guests(): void
    {
        $middleware = new Enforce2FA();
        $response = $middleware->handle($this->makeRequest(null), fn() => response('OK'));

        $this->assertEquals(200, $response->status());
    }
}
