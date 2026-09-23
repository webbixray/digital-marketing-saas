<?php

namespace Tests\Feature\Auth;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // REGISTRATION
    // ──────────────────────────────────────────────

    public function test_registration_page_is_accessible(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_registration_creates_user_and_agency(): void
    {
        $response = $this->post(route('register'), [
            'agency_name' => 'Test Agency',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('agencies', ['name' => 'Test Agency']);
        $this->assertAuthenticated();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('register'), [
            'agency_name' => 'Test Agency',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_rejects_password_mismatch(): void
    {
        $response = $this->post(route('register'), [
            'agency_name' => 'Test Agency',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different456',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_registration_rejects_weak_password(): void
    {
        $response = $this->post(route('register'), [
            'agency_name' => 'Test Agency',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // ──────────────────────────────────────────────
    // LOGIN
    // ──────────────────────────────────────────────

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_login_with_valid_credentials(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_with_unverified_email_redirects_to_dashboard(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'email_verified_at' => null,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rate_limiting(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'password' => Hash::make('password123'),
        ]);

        // Exhaust rate limit (10 requests per minute)
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ]);
        }

        // 11th request should be rate limited
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }

    // ──────────────────────────────────────────────
    // PASSWORD RESET
    // ──────────────────────────────────────────────

    public function test_password_reset_form_is_accessible(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'test-token']));

        $response->assertOk();
        $response->assertViewIs('auth.passwords.reset');
    }

    public function test_password_reset_link_is_sent(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_password_reset_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_password_reset_rejects_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_reset_rejects_expired_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $token = Password::createToken($user);

        // Delete the token from the database to simulate expiration
        DB::table('password_reset_tokens')
            ->where('email', 'test@example.com')
            ->delete();

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ──────────────────────────────────────────────
    // 2FA
    // ──────────────────────────────────────────────

    public function test_two_factor_page_is_accessible(): void
    {
        $user = User::factory()->withAgency()->create();

        $response = $this->actingAs($user)->get(route('two-factor.show'));

        $response->assertOk();
        $response->assertViewIs('auth.two-factor');
    }

    public function test_two_factor_enable_returns_qr_code(): void
    {
        $user = User::factory()->withAgency()->create();

        $response = $this->actingAs($user)->postJson(route('two-factor.enable'));

        $response->assertOk();
        $response->assertJsonStructure(['secret', 'qr_code', 'qr_svg']);
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_two_factor_verify_with_valid_code(): void
    {
        $user = User::factory()->withAgency()->create();
        $enableResponse = $this->actingAs($user)->postJson(route('two-factor.enable'));

        $secret = $enableResponse->json('secret');
        $google2fa = new Google2FA();
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user)->post(route('two-factor.verify'), [
            'code' => $validCode,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => true,
        ]);
    }

    public function test_two_factor_rejects_invalid_code(): void
    {
        $user = User::factory()->withAgency()->create();
        $this->actingAs($user)->postJson(route('two-factor.enable'));

        $response = $this->actingAs($user)->post(route('two-factor.verify'), [
            'code' => '000000',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => false,
        ]);
    }

    public function test_two_factor_disable(): void
    {
        $user = User::factory()->withAgency()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('testsecret'),
        ]);

        $response = $this->actingAs($user)->post(route('two-factor.disable'));

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => false,
        ]);
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    // ──────────────────────────────────────────────
    // OAUTH
    // ──────────────────────────────────────────────

    public function test_oauth_redirect_for_allowed_provider(): void
    {
        if (! class_exists('Laravel\Socialite\Facades\Socialite')) {
            $this->markTestSkipped('Laravel Socialite package is not installed. Run `composer require laravel/socialite` to enable OAuth tests.');
        }

        $provider = \Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('redirect')->once();

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('google')
            ->once()
            ->andReturn($provider);

        $response = $this->get(route('oauth.redirect', ['provider' => 'google']));
        $response->assertStatus(302);

        \Mockery::close();
    }

    public function test_oauth_callback_authenticates_existing_user(): void
    {
        if (! class_exists('Laravel\Socialite\Facades\Socialite')) {
            $this->markTestSkipped('Laravel Socialite package is not installed. Run `composer require laravel/socialite` to enable OAuth tests.');
        }

        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'email' => 'oauth@example.com',
        ]);

        $socialUser = \Mockery::mock('Laravel\Socialite\Contracts\User');
        $socialUser->shouldReceive('getEmail')->andReturn('oauth@example.com');
        $socialUser->shouldReceive('getName')->andReturn('OAuth User');

        $provider = \Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('google')
            ->once()
            ->andReturn($provider);

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        \Mockery::close();
    }

    public function test_oauth_callback_creates_new_user(): void
    {
        if (! class_exists('Laravel\Socialite\Facades\Socialite')) {
            $this->markTestSkipped('Laravel Socialite package is not installed. Run `composer require laravel/socialite` to enable OAuth tests.');
        }

        $socialUser = \Mockery::mock('Laravel\Socialite\Contracts\User');
        $socialUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
        $socialUser->shouldReceive('getName')->andReturn('New User');
        $socialUser->shouldReceive('getNickname')->andReturn(null);

        $provider = \Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
            ->with('google')
            ->once()
            ->andReturn($provider);

        $response = $this->get(route('oauth.callback', ['provider' => 'google']));

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $response->assertRedirect(route('onboarding'));

        \Mockery::close();
    }

    public function test_oauth_rejects_unknown_provider(): void
    {
        $response = $this->get(route('oauth.redirect', ['provider' => 'unknown']));

        $response->assertStatus(404);
    }

    // ──────────────────────────────────────────────
    // LOGOUT
    // ──────────────────────────────────────────────

    public function test_logout_requires_authentication(): void
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create(['agency_id' => $agency->id]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHas('success');
    }

    public function test_logout_regenerates_csrf_token(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create(['agency_id' => $agency->id]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertSessionHas('_token');
    }
}
