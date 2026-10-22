<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PinterestTest extends TestCase
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

    public function test_pinterest_index_requires_auth(): void
    {
        $response = $this->get(route('pinterest.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_pinterest_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('pinterest.index'));
        $response->assertStatus(200);
        $response->assertViewIs('pinterest.index');
    }

    public function test_pinterest_connect_redirects_to_pinterest(): void
    {
        $response = $this->actingAs($this->user)->get(route('pinterest.connect'));
        $response->assertStatus(302);
        $response->assertRedirectContains('pinterest.com');
    }

    public function test_pinterest_callback_rejects_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('pinterest.callback', ['state' => 'invalid', 'code' => 'test']));
        $response->assertRedirect(route('pinterest.index'));
        $response->assertSessionHas('error');
    }

    public function test_pinterest_disconnect_requires_ownership(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'pinterest',
        ]);

        $response = $this->actingAs($this->user)->delete(route('pinterest.disconnect', $account->id));
        $response->assertRedirect(route('pinterest.index'));
        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }

    public function test_pinterest_disconnect_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'pinterest',
        ]);

        $response = $this->actingAs($this->user)->delete(route('pinterest.disconnect', $account->id));
        $response->assertStatus(403);
    }

    public function test_pinterest_toggle_updates_status(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'pinterest',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('pinterest.toggle', $account->id));
        $response->assertRedirect(route('pinterest.index'));
        $this->assertDatabaseHas('social_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }

    public function test_pinterest_supported_platforms_list(): void
    {
        $this->assertArrayHasKey('pinterest', SocialAccount::SUPPORTED_PLATFORMS);
    }
}
