<?php

namespace Tests\Feature\Social;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAccountTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_it_lists_accounts(): void
    {
        SocialAccount::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('social.accounts.index'));
        $response->assertStatus(200);
    }

    public function test_it_creates_an_account(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.accounts.store'), [
            'platform' => 'twitter',
            'platform_display_name' => 'Test Account',
            'access_token' => 'test_token',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('social_accounts', ['platform_display_name' => 'Test Account']);
    }

    public function test_it_validates_account_creation(): void
    {
        $response = $this->actingAs($this->user)->post(route('social.accounts.store'), []);
        $response->assertSessionHasErrors(['platform', 'access_token']);
    }

    public function test_it_toggles_account(): void
    {
        $account = SocialAccount::factory()->create(['agency_id' => $this->agency->id, 'is_active' => true]);
        $response = $this->actingAs($this->user)->post(route('social.accounts.toggle', $account));
        $response->assertRedirect();
        $this->assertDatabaseHas('social_accounts', ['id' => $account->id, 'is_active' => false]);
    }

    public function test_it_deletes_an_account(): void
    {
        $account = SocialAccount::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('social.accounts.destroy', $account));
        $response->assertRedirect();
        // SocialAccount uses soft deletes (OAuth reconnect history is retained).
        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }

    public function test_it_prevents_access_to_other_agency_accounts(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('social.accounts.edit', $account));
        $response->assertForbidden();
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('social.accounts.index'));
        $response->assertRedirect(route('login'));
    }
}
