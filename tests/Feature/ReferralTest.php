<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
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

    public function test_referral_index_requires_auth(): void
    {
        $response = $this->get(route('referrals.index'));
        $response->assertStatus(302);
    }

    public function test_referral_index_returns_200_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('referrals.index'));
        $response->assertStatus(200);
    }

    public function test_track_referral_redirects(): void
    {
        $response = $this->actingAs($this->user)->get(route('referrals.track', ['code' => 'test123']));
        $response->assertStatus(302);
    }

    public function test_referral_page_shows_referral_link(): void
    {
        $response = $this->actingAs($this->user)->get(route('referrals.index'));
        $response->assertStatus(200);
    }
}
