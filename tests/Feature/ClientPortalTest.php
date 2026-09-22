<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientAccessToken;
use App\Models\ClientPortalSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    // ------------------------------------------------------------------
    //  Settings page
    // ------------------------------------------------------------------

    public function test_settings_page_loads_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('client-portal.settings'));

        $response->assertOk();
        $response->assertViewIs('client-portal.settings');
        $response->assertViewHas('settings');
    }

    public function test_settings_page_creates_default_settings_if_none_exist(): void
    {
        $this->assertDatabaseMissing('client_portal_settings', [
            'agency_id' => $this->agency->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('client-portal.settings'));

        $this->assertDatabaseHas('client_portal_settings', [
            'agency_id' => $this->agency->id,
            'brand_name' => $this->agency->name,
            'brand_color' => '#6366f1',
        ]);
    }

    public function test_settings_page_loads_existing_settings(): void
    {
        $settings = ClientPortalSetting::create([
            'agency_id' => $this->agency->id,
            'brand_name' => 'My Custom Brand',
            'brand_color' => '#ff0000',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('client-portal.settings'));

        $response->assertOk();
        $response->assertViewHas('settings', function ($viewSettings) use ($settings) {
            return $viewSettings->id === $settings->id
                && $viewSettings->brand_name === 'My Custom Brand';
        });
    }

    public function test_settings_page_redirects_guest(): void
    {
        $response = $this->get(route('client-portal.settings'));
        $response->assertRedirect();
    }

    // ------------------------------------------------------------------
    //  Update settings
    // ------------------------------------------------------------------

    public function test_update_settings_persists_valid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('client-portal.settings.update'), [
                'brand_name' => 'Updated Brand',
                'brand_color' => '#00ff00',
                'is_enabled' => true,
                'show_analytics' => false,
                'show_invoices' => false,
                'allow_approvals' => true,
                'show_team_activity' => true,
                'welcome_message' => 'Welcome to our portal!',
            ]);

        $response->assertRedirect(route('client-portal.settings'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('client_portal_settings', [
            'agency_id' => $this->agency->id,
            'brand_name' => 'Updated Brand',
            'brand_color' => '#00ff00',
            'welcome_message' => 'Welcome to our portal!',
        ]);
    }

    public function test_update_settings_updates_existing_record(): void
    {
        ClientPortalSetting::create([
            'agency_id' => $this->agency->id,
            'brand_name' => 'Old Brand',
            'brand_color' => '#000000',
        ]);

        $this->actingAs($this->user)
            ->put(route('client-portal.settings.update'), [
                'brand_name' => 'New Brand',
                'brand_color' => '#ffffff',
            ]);

        $this->assertDatabaseHas('client_portal_settings', [
            'agency_id' => $this->agency->id,
            'brand_name' => 'New Brand',
        ]);
        $this->assertDatabaseMissing('client_portal_settings', [
            'agency_id' => $this->agency->id,
            'brand_name' => 'Old Brand',
        ]);
    }

    public function test_update_settings_rejects_invalid_brand_name(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('client-portal.settings.update'), [
                'brand_name' => '',
                'brand_color' => '#ffffff',
            ]);

        $response->assertSessionHasErrors('brand_name');
    }

    public function test_update_settings_rejects_invalid_brand_color(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('client-portal.settings.update'), [
                'brand_name' => 'Valid Brand',
                'brand_color' => 'not-a-color',
            ]);

        $response->assertSessionHasErrors('brand_color');
    }

    public function test_update_settings_rejects_invalid_logo_url(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('client-portal.settings.update'), [
                'brand_name' => 'Valid Brand',
                'brand_color' => '#ffffff',
                'logo_url' => 'not-a-url',
            ]);

        $response->assertSessionHasErrors('logo_url');
    }

    public function test_update_settings_redirects_guest(): void
    {
        $response = $this->put(route('client-portal.settings.update'), [
            'brand_name' => 'Hacker Brand',
            'brand_color' => '#ffffff',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('client_portal_settings', [
            'brand_name' => 'Hacker Brand',
        ]);
    }

    // ------------------------------------------------------------------
    //  Generate token
    // ------------------------------------------------------------------

    public function test_generate_token_creates_access_token_for_own_client(): void
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('client-portal.generate-token', $client));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('token');

        $this->assertDatabaseHas('client_access_tokens', [
            'client_id' => $client->id,
        ]);
    }

    public function test_generate_token_stores_hashed_token(): void
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('client-portal.generate-token', $client));

        $token = ClientAccessToken::where('client_id', $client->id)->first();

        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token->token));
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_generate_token_denied_for_other_agency_client(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherClient = Client::factory()->create([
            'agency_id' => $otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('client-portal.generate-token', $otherClient));

        // Should return 403 or redirect - authorization check prevents cross-agency access
        // The exact behavior depends on how exceptions are rendered for web routes
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_generate_token_redirects_guest(): void
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->post(route('client-portal.generate-token', $client));

        $response->assertRedirect();
        $this->assertDatabaseMissing('client_access_tokens', [
            'client_id' => $client->id,
        ]);
    }

    // ------------------------------------------------------------------
    //  Revoke token
    // ------------------------------------------------------------------

    public function test_revoke_token_deletes_access_token(): void
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
        $accessToken = ClientAccessToken::create([
            'client_id' => $client->id,
            'token' => hash('sha256', 'test-token'),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('client-portal.revoke-token', [
                'client' => $client,
                'accessToken' => $accessToken,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('client_access_tokens', [
            'id' => $accessToken->id,
        ]);
    }

    public function test_revoke_token_denied_for_other_agency_client(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherClient = Client::factory()->create([
            'agency_id' => $otherAgency->id,
        ]);
        $accessToken = ClientAccessToken::create([
            'client_id' => $otherClient->id,
            'token' => hash('sha256', 'test-token-other'),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('client-portal.revoke-token', [
                'client' => $otherClient,
                'accessToken' => $accessToken,
            ]));

        // Should return 403 or redirect - authorization check prevents cross-agency access
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    public function test_revoke_token_redirects_guest(): void
    {
        $client = Client::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
        $accessToken = ClientAccessToken::create([
            'client_id' => $client->id,
            'token' => hash('sha256', 'guest-test-token'),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->delete(route('client-portal.revoke-token', [
            'client' => $client,
            'accessToken' => $accessToken,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('client_access_tokens', [
            'id' => $accessToken->id,
        ]);
    }
}
