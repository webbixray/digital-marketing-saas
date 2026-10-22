<?php

namespace Tests\Feature\Api;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSocialAccountTest extends TestCase
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

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_it_creates_an_account(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/accounts', [
            'platform' => 'twitter',
            'access_token' => 'test_token',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('social_accounts', ['platform' => 'twitter']);
    }

    public function test_it_validates_account_creation(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/accounts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['platform', 'access_token']);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/accounts');

        $response->assertUnauthorized();
    }
}
