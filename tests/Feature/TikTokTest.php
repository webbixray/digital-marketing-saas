<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TikTokTest extends TestCase
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

    public function test_tiktok_index_requires_auth(): void
    {
        $response = $this->get(route('tiktok.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_tiktok_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('tiktok.index'));
        $response->assertStatus(200);
        $response->assertViewIs('tiktok.index');
    }

    public function test_tiktok_connect_redirects_to_tiktok(): void
    {
        $response = $this->actingAs($this->user)->get(route('tiktok.connect'));
        $response->assertStatus(302);
        $response->assertRedirectContains('tiktok.com');
    }

    public function test_tiktok_callback_rejects_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('tiktok.callback', ['state' => 'invalid', 'code' => 'test']));
        $response->assertRedirect(route('tiktok.index'));
        $response->assertSessionHas('error');
    }

    public function test_tiktok_disconnect_requires_ownership(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'tiktok',
        ]);

        $response = $this->actingAs($this->user)->delete(route('tiktok.disconnect', $account->id));
        $response->assertRedirect(route('tiktok.index'));
        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }

    public function test_tiktok_disconnect_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'tiktok',
        ]);

        $response = $this->actingAs($this->user)->delete(route('tiktok.disconnect', $account->id));
        $response->assertStatus(403);
    }

    public function test_tiktok_toggle_updates_status(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'tiktok',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('tiktok.toggle', $account->id));
        $response->assertRedirect(route('tiktok.index'));
        $this->assertDatabaseHas('social_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }

    public function test_tiktok_supported_platforms_list(): void
    {
        $this->assertArrayHasKey('tiktok', SocialAccount::SUPPORTED_PLATFORMS);
    }
}
