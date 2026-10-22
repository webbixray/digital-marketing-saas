<?php

namespace Tests\Feature\Security;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agencyA;

    private Agency $agencyB;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agencyA = Agency::factory()->create();
        $this->agencyB = Agency::factory()->create();

        $this->userA = User::factory()->create([
            'agency_id' => $this->agencyA->id,
            'password' => bcrypt('password'),
        ]);

        $this->userB = User::factory()->create([
            'agency_id' => $this->agencyB->id,
            'password' => bcrypt('password'),
        ]);
    }

    // ============================================================
    // Cross-agency data access prevention - Social Posts
    // ============================================================

    public function test_user_cannot_view_other_agency_social_post(): void
    {
        $postB = SocialPost::factory()->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->get("/social/posts/{$postB->id}");
        // Controller returns 403 Forbidden for cross-agency access
        $response->assertForbidden();
    }

    public function test_user_can_view_own_agency_social_post(): void
    {
        $postA = SocialPost::factory()->create(['agency_id' => $this->agencyA->id]);

        $response = $this->actingAs($this->userA)->get("/social/posts/{$postA->id}");
        $response->assertOk();
    }

    // ============================================================
    // Cross-agency data access prevention - Campaigns
    // ============================================================

    public function test_user_cannot_view_other_agency_campaign(): void
    {
        $campaignB = Campaign::factory()->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->get("/campaigns/{$campaignB->id}");
        $response->assertForbidden();
    }

    public function test_user_can_view_own_agency_campaign(): void
    {
        $campaignA = Campaign::factory()->create(['agency_id' => $this->agencyA->id]);

        $response = $this->actingAs($this->userA)->get("/campaigns/{$campaignA->id}");
        $response->assertOk();
    }

    // ============================================================
    // Cross-agency data access prevention - Clients
    // ============================================================

    public function test_user_cannot_view_other_agency_client(): void
    {
        $clientB = Client::factory()->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->get("/clients/{$clientB->id}");
        $response->assertForbidden();
    }

    public function test_user_can_view_own_agency_client(): void
    {
        $clientA = Client::factory()->create(['agency_id' => $this->agencyA->id]);

        $response = $this->actingAs($this->userA)->get("/clients/{$clientA->id}");
        $response->assertOk();
    }

    // ============================================================
    // Cross-agency data access prevention - Invoices
    // ============================================================

    public function test_user_cannot_view_other_agency_invoice(): void
    {
        $invoiceB = Invoice::factory()->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->get("/invoices/{$invoiceB->id}");
        $response->assertForbidden();
    }

    public function test_user_can_view_own_agency_invoice(): void
    {
        $invoiceA = Invoice::factory()->create(['agency_id' => $this->agencyA->id]);

        $response = $this->actingAs($this->userA)->get("/invoices/{$invoiceA->id}");
        $response->assertOk();
    }

    // ============================================================
    // Cross-agency data access prevention - Email Campaigns
    // ============================================================

    public function test_user_cannot_view_other_agency_email_campaign(): void
    {
        $emailCampaignB = EmailCampaign::factory()->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->get("/email/campaigns/{$emailCampaignB->id}");
        // EmailCampaignController uses firstOrFail() which returns 404 for cross-agency
        $response->assertNotFound();
    }

    public function test_user_can_view_own_agency_email_campaign(): void
    {
        $emailCampaignA = EmailCampaign::factory()->create(['agency_id' => $this->agencyA->id]);

        $response = $this->actingAs($this->userA)->get("/email/campaigns/{$emailCampaignA->id}");
        $response->assertOk();
    }

    // ============================================================
    // Index pages only show own agency's data
    // ============================================================

    public function test_social_posts_index_shows_only_agency_posts(): void
    {
        SocialPost::factory()->count(3)->create(['agency_id' => $this->agencyA->id]);
        SocialPost::factory()->count(2)->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->get('/social/posts');
        $response->assertOk();
        $response->assertViewHas('posts', function ($posts) {
            return $posts->count() === 3;
        });
    }

    public function test_campaigns_index_shows_only_agency_campaigns(): void
    {
        Campaign::factory()->count(2)->create(['agency_id' => $this->agencyA->id]);
        Campaign::factory()->count(3)->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->get('/campaigns');
        $response->assertOk();
        $response->assertViewHas('campaigns', function ($campaigns) {
            return $campaigns->count() === 2;
        });
    }

    // ============================================================
    // Delete operations are agency-scoped
    // ============================================================

    public function test_user_cannot_delete_other_agency_social_post(): void
    {
        $postB = SocialPost::factory()->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->delete("/social/posts/{$postB->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('social_posts', ['id' => $postB->id]);
    }

    public function test_user_cannot_delete_other_agency_campaign(): void
    {
        $campaignB = Campaign::factory()->create(['agency_id' => $this->agencyB->id]);

        $response = $this->actingAs($this->userA)->delete("/campaigns/{$campaignB->id}");
        $response->assertForbidden();

        $this->assertDatabaseHas('campaigns', ['id' => $campaignB->id]);
    }

    // ============================================================
    // Update operations are agency-scoped
    // ============================================================

    public function test_user_cannot_update_other_agency_social_post(): void
    {
        $postB = SocialPost::factory()->create([
            'agency_id' => $this->agencyB->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->userA)->put("/social/posts/{$postB->id}", [
            'content' => 'Hacked content',
            'platform' => 'facebook',
        ]);
        $response->assertForbidden();

        $this->assertDatabaseHas('social_posts', [
            'id' => $postB->id,
            'content' => 'Original content',
        ]);
    }

    public function test_user_cannot_update_other_agency_client(): void
    {
        $clientB = Client::factory()->create([
            'agency_id' => $this->agencyB->id,
            'name' => 'Original Name',
        ]);

        $response = $this->actingAs($this->userA)->put("/clients/{$clientB->id}", [
            'name' => 'Hacked Name',
            'email' => $clientB->email,
        ]);
        $response->assertForbidden();

        $this->assertDatabaseHas('clients', [
            'id' => $clientB->id,
            'name' => 'Original Name',
        ]);
    }
}
