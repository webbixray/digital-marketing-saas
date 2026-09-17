<?php

namespace Tests\E2E;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\InboxMessage;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * End-to-End Platform Tests
 *
 * Covers critical user journeys across the entire platform:
 * - Registration → Onboarding → Dashboard
 * - Social account connection → Post creation → Publishing
 * - Campaign workflow
 * - Multi-tenant isolation
 * - Billing and subscription
 * - AI content generation
 * - Inbox and messaging
 * - Analytics and reporting
 */
class PlatformE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed required data
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
    }

    /**
     * E2E: Complete user registration and onboarding journey
     */
    public function test_complete_registration_and_onboarding_journey(): void
    {
        // Step 1: User registers
        $response = $this->post('/register', [
            'agency_name' => 'Test Agency',
            'name' => 'Test Agency Owner',
            'email' => 'test@agency.com',
            'password' => 'SecureP@ss123!',
            'password_confirmation' => 'SecureP@ss123!',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['email' => 'test@agency.com']);

        $user = User::where('email', 'test@agency.com')->first();
        $this->assertNotNull($user->agency_id);

        // Step 2: User logs in
        $loginResponse = $this->post('/login', [
            'email' => 'test@agency.com',
            'password' => 'SecureP@ss123!',
        ]);

        $loginResponse->assertStatus(302);
        $this->assertAuthenticatedAs($user);

        // Step 3: User accesses dashboard
        $dashboardResponse = $this->get('/dashboard');
        $dashboardResponse->assertStatus(200);
    }

    /**
     * E2E: Social post creation and publishing workflow
     */
    public function test_social_post_creation_and_publishing_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: Connect a social account
        $account = SocialAccount::factory()->create([
            'agency_id' => $user->agency_id,
            'platform' => 'twitter',
            'platform_account_id' => '12345',
            'platform_username' => '@testagency',
            'platform_display_name' => 'Test Agency',
            'is_active' => true,
            'access_token' => 'test-token',
        ]);

        // Step 2: Create a post (requires social_account_id)
        $postResponse = $this->post('/social/posts', [
            'social_account_id' => $account->id,
            'content' => 'Test post content',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
        ]);

        $postResponse->assertStatus(302);
        $this->assertDatabaseHas('social_posts', [
            'agency_id' => $user->agency_id,
            'content' => 'Test post content',
        ]);

        $post = SocialPost::where('agency_id', $user->agency_id)
            ->where('content', 'Test post content')
            ->first();

        // Step 3: Publish the post (fake external API)
        Http::fake([
            'api.twitter.com/*' => Http::response(['data' => ['id' => '12345']], 200),
        ]);

        $publishResponse = $this->post("/social/posts/{$post->id}/publish");
        $publishResponse->assertStatus(302);
        $this->assertDatabaseHas('social_posts', [
            'id' => $post->id,
            'status' => 'published',
        ]);
    }

    /**
     * E2E: Multi-tenant isolation - user cannot access other agency data
     */
    public function test_multi_tenant_isolation_prevents_cross_agency_access(): void
    {
        // Create two separate agencies
        $user1 = $this->createUserWithAgency('user1@test.com', 'Agency One');
        $user2 = $this->createUserWithAgency('user2@test.com', 'Agency Two');

        $this->actingAs($user2);

        // User1 creates a post
        $post = SocialPost::factory()->create([
            'agency_id' => $user1->agency_id,
            'content' => 'Private post',
            'platform' => 'twitter',
            'status' => 'draft',
        ]);

        // User2 tries to view User1's post
        $response = $this->get("/social/posts/{$post->id}");
        $response->assertStatus(403);

        // User2 tries to update User1's post
        $updateResponse = $this->put("/social/posts/{$post->id}", [
            'content' => 'Hacked content',
        ]);
        $updateResponse->assertStatus(403);

        // User2 tries to delete User1's post
        $deleteResponse = $this->delete("/social/posts/{$post->id}");
        $deleteResponse->assertStatus(403);
    }

    /**
     * E2E: Campaign creation and management workflow
     */
    public function test_campaign_creation_and_management_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: Create a campaign
        $campaignResponse = $this->post('/campaigns', [
            'name' => 'Summer Campaign',
            'type' => 'general',
            'description' => 'Summer promotion',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $campaignResponse->assertStatus(302);

        $campaign = Campaign::where('agency_id', $user->agency_id)
            ->where('name', 'Summer Campaign')
            ->first();

        $this->assertNotNull($campaign);

        // Step 2: Add posts to campaign
        $postResponse = $this->post('/social/posts', [
            'content' => 'Campaign post',
            'platforms' => ['twitter'],
            'campaign_id' => $campaign->id,
        ]);

        $postResponse->assertStatus(302);

        // Step 3: View campaign with posts
        $viewResponse = $this->get("/campaigns/{$campaign->id}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Summer Campaign');
    }

    /**
     * E2E: Inbox and messaging workflow
     */
    public function test_inbox_and_messaging_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: Create inbox message
        $message = InboxMessage::factory()->create([
            'agency_id' => $user->agency_id,
            'platform' => 'twitter',
            'message_type' => 'comment',
            'content' => 'Test message',
            'author_name' => 'Test User',
            'status' => 'unread',
        ]);

        // Step 2: List inbox messages (unified inbox)
        $listResponse = $this->get('/inbox');
        $listResponse->assertStatus(200);

        // Step 3: Mark message as read via API
        $readResponse = $this->postJson("/api/v1/inbox/{$message->id}/read");
        $readResponse->assertStatus(200);
    }

    /**
     * E2E: Analytics and reporting workflow
     */
    public function test_analytics_and_reporting_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: View analytics dashboard
        $analyticsResponse = $this->get('/analytics');
        $analyticsResponse->assertStatus(200);
    }

    /**
     * E2E: Billing and subscription workflow
     */
    public function test_billing_and_subscription_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: View billing page
        $billingResponse = $this->get('/agency/billing');
        $billingResponse->assertStatus(200);
    }

    /**
     * E2E: Team management workflow
     */
    public function test_team_management_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: Invite team member
        $inviteResponse = $this->post('/agency/team/invite', [
            'email' => 'teammate@test.com',
            'role' => 'editor',
        ]);

        $inviteResponse->assertStatus(302);
    }

    /**
     * E2E: Workflow automation
     */
    public function test_workflow_automation_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: Create a workflow
        $workflowResponse = $this->post('/workflows', [
            'name' => 'Auto-approve posts',
            'description' => 'Automatically approve posts from trusted sources',
            'trigger_type' => 'new_post',
            'actions' => [['type' => 'send_notification']],
        ]);

        $workflowResponse->assertStatus(302);
        $this->assertDatabaseHas('workflows', [
            'agency_id' => $user->agency_id,
            'name' => 'Auto-approve posts',
        ]);
    }

    /**
     * E2E: Content calendar workflow
     */
    public function test_content_calendar_workflow(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Step 1: Create a scheduled post
        $postResponse = $this->post('/social/posts', [
            'content' => 'Scheduled post',
            'platforms' => ['twitter'],
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
        ]);

        $postResponse->assertStatus(302);

        // Step 2: View calendar
        $calendarResponse = $this->get('/calendar');
        $calendarResponse->assertStatus(200);
    }

    /**
     * E2E: Search functionality
     */
    public function test_search_functionality(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Create some content
        SocialPost::factory()->create([
            'agency_id' => $user->agency_id,
            'content' => 'Searchable post content',
            'platform' => 'twitter',
            'status' => 'draft',
        ]);

        // Step 1: Search for content
        $searchResponse = $this->get('/search?q=Searchable');
        $searchResponse->assertStatus(200);
    }

    /**
     * E2E: Health check endpoints
     */
    public function test_health_check_endpoints(): void
    {
        // Public health check
        $response = $this->get('/up');
        $response->assertStatus(200);
    }

    /**
     * E2E: Authentication and authorization
     */
    public function test_authentication_and_authorization(): void
    {
        // Unauthenticated request should redirect to login
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // Invalid credentials should fail
        $loginResponse = $this->post('/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ]);
        $loginResponse->assertStatus(302);
        $this->assertGuest();
    }

    /**
     * E2E: GDPR data export
     */
    public function test_gdpr_data_export(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // Request data export
        $exportResponse = $this->post('/gdpr/export');
        $exportResponse->assertStatus(302);
    }

    /**
     * E2E: Feature flags
     */
    public function test_feature_flags(): void
    {
        $user = $this->createUserWithAgency();
        $this->actingAs($user);

        // View feature flags
        $flagsResponse = $this->get('/feature-flags');
        $flagsResponse->assertStatus(200);
    }

    /**
     * Helper: Create a user with an agency using factories
     */
    protected function createUserWithAgency(string $email = 'user@test.com', string $agencyName = 'Test Agency'): User
    {
        $agency = Agency::factory()->create([
            'name' => $agencyName,
            'slug' => Str::slug($agencyName),
            'email' => $email,
            'subscription_plan' => 'free',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => $email,
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        return $user;
    }
}
