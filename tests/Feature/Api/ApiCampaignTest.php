<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCampaignTest extends TestCase
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

    public function test_campaign_list_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/campaigns');
        $response->assertStatus(401);
    }

    public function test_campaign_list_returns_paginated_results(): void
    {
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/campaigns');
        $response->assertStatus(200);
    }

    public function test_campaign_show_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $otherAgency->id,
        ]);
        $post = SocialPost::factory()->create([
            'agency_id' => $otherAgency->id,
            'social_account_id' => $account->id,
        ]);

        // The API uses route model binding which may return 404 for cross-agency
        // depending on the controller implementation
        $response = $this->actingAs($this->user)->getJson('/api/v1/posts/' . $post->id);
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_campaign_create_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/campaigns', [
            'name' => '',
        ]);

        $response->assertStatus(422);
    }
}
