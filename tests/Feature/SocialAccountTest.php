<?php

namespace Tests\Feature;

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
        $this->actingAs($this->user);
    }

    public function test_it_lists_social_accounts(): void
    {
        SocialAccount::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/social/accounts');
        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_it_shows_create_form(): void
    {
        $response = $this->get('/social/accounts/create');
        $response->assertStatus(200);
        $response->assertViewHas('platforms');
    }

    public function test_it_creates_social_account(): void
    {
        $data = [
            'platform' => 'facebook',
            'access_token' => 'test_token',
            'refresh_token' => 'test_refresh',
            'platform_username' => 'testuser',
        ];
        $response = $this->post('/social/accounts', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('social_accounts', ['platform' => 'facebook', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->post('/social/accounts', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['platform', 'access_token']);
    }

    public function test_it_toggles_account_status(): void
    {
        $account = SocialAccount::factory()->create(['agency_id' => $this->agency->id, 'is_active' => true]);
        $response = $this->post("/social/accounts/{$account->id}/toggle");
        $response->assertStatus(302);
        $this->assertDatabaseHas('social_accounts', ['id' => $account->id, 'is_active' => false]);
    }

    public function test_it_prevents_accessing_other_agency_accounts(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->post("/social/accounts/{$account->id}/toggle");
        $response->assertStatus(403);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/social/accounts');
        $response->assertRedirect('/login');
    }
}
