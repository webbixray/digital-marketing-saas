<?php

namespace Tests\Feature\UAT;

use App\Models\Agency;
use App\Models\Client;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function createAgencyWithUser(string $role = 'owner'): array
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => $role]);

        return [$agency, $user];
    }

    public function test_complete_agency_setup_workflow(): void
    {
        $response = $this->post('/register', [
            'agency_name' => 'New Test Agency',
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => 'securepassword123',
            'password_confirmation' => 'securepassword123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('agencies', ['name' => 'New Test Agency']);
    }

    public function test_social_media_management_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/social/accounts', [
            'platform' => 'facebook',
            'access_token' => 'test_token_123',
            'platform_display_name' => 'Test Page',
        ]);
        $response->assertRedirect('/social/accounts');

        $account = SocialAccount::first();

        $response = $this->post('/social/posts', [
            'social_account_id' => $account->id,
            'content' => 'Test post content for UAT',
        ]);
        $response->assertRedirect('/social/posts');
        $this->assertDatabaseHas('social_posts', ['content' => 'Test post content for UAT']);
    }

    public function test_campaign_management_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $this->actingAs($user);

        $response = $this->post('/campaigns', [
            'name' => 'UAT Campaign',
            'type' => 'general',
            'description' => 'Test campaign for UAT',
            'client_id' => $client->id,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addMonth()->toDateString(),
        ]);
        $response->assertRedirectContains('/campaigns/');
        $this->assertDatabaseHas('campaigns', ['name' => 'UAT Campaign']);
    }

    public function test_client_management_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/clients', [
            'name' => 'Test Client Corp',
            'email' => 'contact@testclient.com',
            'company' => 'Test Client Corp',
            'industry' => 'Technology',
        ]);
        $response->assertRedirectContains('/clients/');
        $this->assertDatabaseHas('clients', ['name' => 'Test Client Corp']);
    }

    public function test_invoice_management_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/invoices', [
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 500],
            ],
        ]);
        $response->assertRedirectContains('/invoices/');
        $this->assertDatabaseHas('invoices', ['total' => 500]);
    }

    public function test_team_management_workflow(): void
    {
        [$agency, $owner] = $this->createAgencyWithUser('owner');
        $this->actingAs($owner);

        $response = $this->post('/agency/team/invite', [
            'name' => 'New Team Member',
            'email' => 'member@test.com',
            'role' => 'manager',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'member@test.com', 'role' => 'manager']);
    }

    public function test_content_library_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/content', [
            'name' => 'Brand Guidelines',
            'type' => 'document',
            'content' => 'Complete brand guidelines document content...',
        ]);
        $response->assertRedirectContains('/content/');
        $this->assertDatabaseHas('content_assets', ['name' => 'Brand Guidelines']);
    }

    public function test_landing_page_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/landing-pages', [
            'name' => 'Free Consultation',
            'headline' => 'Get Your Free Marketing Consultation',
            'content' => '<p>Book a free consultation.</p>',
            'cta_text' => 'Book Now',
            'cta_url' => 'https://example.com/contact',
        ]);
        $response->assertRedirectContains('/landing-pages/');
        $this->assertDatabaseHas('landing_pages', ['name' => 'Free Consultation']);
    }

    public function test_workflow_automation_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/workflows', [
            'name' => 'Auto Reply to Comments',
            'trigger_type' => 'comment_received',
            'actions' => [
                ['type' => 'auto_reply', 'config' => ['message' => 'Thanks!']],
            ],
        ]);
        $response->assertRedirectContains('/workflows/');
        $this->assertDatabaseHas('workflows', ['name' => 'Auto Reply to Comments']);
    }

    public function test_search_functionality(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        Client::factory()->create(['agency_id' => $agency->id, 'name' => 'Searchable Client']);
        $this->actingAs($user);

        $response = $this->get('/search?q=Searchable&type=clients');
        $response->assertStatus(200);
    }

    public function test_analytics_dashboard(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        SocialPost::factory()->count(3)->create([
            'agency_id' => $agency->id,
            'status' => 'published',
        ]);
        $this->actingAs($user);

        $response = $this->get('/analytics');
        $response->assertStatus(200);
    }

    public function test_billing_workflow(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'free']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);
        $this->actingAs($user);

        $response = $this->get('/agency/billing');
        $response->assertStatus(200);

        $response = $this->post('/agency/billing/upgrade', ['plan' => 'starter']);
        $response->assertRedirect('/agency/billing');
    }

    public function test_activity_log_viewing(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->get('/activity');
        $response->assertStatus(200);
    }

    public function test_webhook_management_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/webhooks', [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['post.published'],
        ]);
        $response->assertRedirectContains('/webhooks/');
    }

    public function test_settings_management_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->get('/agency/settings');
        $response->assertStatus(200);
    }

    public function test_logout_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_cross_agency_data_isolation(): void
    {
        $agency1 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $agency2 = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user1 = User::factory()->create(['agency_id' => $agency1->id, 'role' => 'owner']);
        $client2 = Client::factory()->create(['agency_id' => $agency2->id]);
        $this->actingAs($user1);

        $response = $this->get("/clients/{$client2->id}");
        $response->assertStatus(403);
    }

    public function test_ai_content_generation_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->get('/ai');
        $response->assertStatus(200);
    }

    public function test_form_builder_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $this->actingAs($user);

        $response = $this->post('/forms', [
            'name' => 'Contact Form',
            'fields' => [
                ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
            ],
        ]);
        $response->assertRedirectContains('/forms/');
    }

    public function test_post_scheduled_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $this->actingAs($user);

        $response = $this->post('/social/posts', [
            'social_account_id' => $account->id,
            'content' => 'Scheduled post content',
            'scheduled_at' => Carbon::now()->addDay()->toDateTimeString(),
        ]);
        $response->assertRedirect('/social/posts');
        $this->assertDatabaseHas('social_posts', ['content' => 'Scheduled post content']);
    }

    public function test_post_publish_workflow(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $this->actingAs($user);

        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'status' => 'draft',
        ]);

        // Mock the SocialApiService to avoid real API calls
        $mock = \Mockery::mock(\App\Services\Social\SocialApiService::class);
        $mock->shouldReceive('publish')->once()->andReturn([
            'success' => true,
            'platform_post_id' => 'mock_123',
            'url' => 'https://example.com/mock',
        ]);
        $this->app->instance(\App\Services\Social\SocialApiService::class, $mock);

        $response = $this->post("/social/posts/{$post->id}/publish");
        $response->assertRedirect('/social/posts');
    }

    public function test_client_filter_by_status(): void
    {
        [$agency, $user] = $this->createAgencyWithUser();
        Client::factory()->count(3)->create(['agency_id' => $agency->id, 'status' => 'active']);
        $this->actingAs($user);

        $response = $this->get('/clients?status=active');
        $response->assertStatus(200);
    }
}
