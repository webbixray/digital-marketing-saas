<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkedInTest extends TestCase
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

    public function test_linkedin_index_requires_auth(): void
    {
        $response = $this->get(route('linkedin.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_linkedin_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('linkedin.index'));
        $response->assertStatus(200);
        $response->assertViewIs('linkedin.index');
    }

    public function test_linkedin_connect_redirects_to_linkedin(): void
    {
        $response = $this->actingAs($this->user)->get(route('linkedin.connect'));
        $response->assertStatus(302);
        $response->assertRedirectContains('linkedin.com');
    }

    public function test_linkedin_callback_rejects_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('linkedin.callback', ['state' => 'invalid', 'code' => 'test']));
        $response->assertRedirect(route('linkedin.index'));
        $response->assertSessionHas('error');
    }

    public function test_linkedin_disconnect_requires_ownership(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'linkedin',
        ]);

        $response = $this->actingAs($this->user)->delete(route('linkedin.disconnect', $account->id));
        $response->assertRedirect(route('linkedin.index'));
        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }

    public function test_linkedin_disconnect_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'linkedin',
        ]);

        $response = $this->actingAs($this->user)->delete(route('linkedin.disconnect', $account->id));
        $response->assertStatus(403);
    }

    public function test_linkedin_toggle_updates_status(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'linkedin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('linkedin.toggle', $account->id));
        $response->assertRedirect(route('linkedin.index'));
        $this->assertDatabaseHas('social_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }

    public function test_linkedin_supported_platforms_list(): void
    {
        $this->assertArrayHasKey('linkedin', SocialAccount::SUPPORTED_PLATFORMS);
    }
}
