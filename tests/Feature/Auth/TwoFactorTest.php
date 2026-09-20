<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_show_requires_auth(): void
    {
        $response = $this->get(route('two-factor.show'));
        $response->assertRedirect();
    }

    public function test_two_factor_show_requires_agency(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('two-factor.show'));
        $response->assertForbidden();
    }

    public function test_two_factor_show_returns_view(): void
    {
        $user = User::factory()->withAgency()->create();
        $response = $this->actingAs($user)->get(route('two-factor.show'));
        $response->assertOk();
        $response->assertViewIs('auth.two-factor');
    }

    public function test_two_factor_enable_returns_qr_svg(): void
    {
        $user = User::factory()->withAgency()->create();
        $response = $this->actingAs($user)->postJson(route('two-factor.enable'));
        $response->assertOk();
        $response->assertJsonStructure(['secret', 'qr_code', 'qr_svg']);
        $data = $response->json();
        $this->assertNotEmpty($data['secret']);
        $this->assertNotEmpty($data['qr_code']);
        $this->assertNotEmpty($data['qr_svg']);
        $this->assertStringContainsString('<svg', $data['qr_svg']);
        // Verify the user now has a secret stored
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_two_factor_verify_with_valid_code(): void
    {
        $user = User::factory()->withAgency()->create();
        $enableResponse = $this->actingAs($user)->postJson(route('two-factor.enable'));
        $secret = $enableResponse->json('secret');
        $this->assertNotEmpty($secret);
        $this->assertGreaterThanOrEqual(16, strlen($secret));

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $validCode = $google2fa->getCurrentOtp($secret);
        $this->assertNotEmpty($validCode);

        $response = $this->actingAs($user)->post(route('two-factor.verify'), [
            'code' => $validCode,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => true,
        ]);
    }

    public function test_two_factor_verify_with_invalid_code(): void
    {
        $user = User::factory()->withAgency()->create();
        $this->actingAs($user)->postJson(route('two-factor.enable'));

        $response = $this->actingAs($user)->post(route('two-factor.verify'), [
            'code' => '000000',
        ]);
        $response->assertRedirect();
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
        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_enabled' => false,
        ]);
    }
}
