<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacebookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_facebook_index_requires_auth(): void
    {
        $response = $this->get(route('facebook.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_facebook_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('facebook.index'));
        $response->assertStatus(200);
        $response->assertViewIs('facebook.index');
    }

    public function test_facebook_connect_redirects_to_facebook(): void
    {
        $response = $this->actingAs($this->user)->get(route('facebook.connect'));
        $response->assertStatus(302);
        $response->assertRedirectContains('facebook.com');
    }

    public function test_facebook_callback_rejects_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('facebook.callback', ['state' => 'invalid', 'code' => 'test']));
        $response->assertRedirect(route('facebook.index'));
        $response->assertSessionHas('error');
    }

    public function test_facebook_disconnect_requires_ownership(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
        ]);

        $response = $this->actingAs($this->user)->delete(route('facebook.disconnect', $account->id));
        $response->assertRedirect(route('facebook.index'));
        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }

    public function test_facebook_disconnect_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'facebook',
        ]);

        $response = $this->actingAs($this->user)->delete(route('facebook.disconnect', $account->id));
        $response->assertStatus(403);
    }

    public function test_facebook_toggle_updates_status(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('facebook.toggle', $account->id));
        $response->assertRedirect(route('facebook.index'));
        $this->assertDatabaseHas('social_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }

    public function test_facebook_supported_platforms_list(): void
    {
        $this->assertArrayHasKey('facebook', SocialAccount::SUPPORTED_PLATFORMS);
    }
}
