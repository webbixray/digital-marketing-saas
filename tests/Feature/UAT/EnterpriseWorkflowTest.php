<?php

namespace Tests\Feature\UAT;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): array
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create(['agency_id' => $agency->id, 'role' => 'owner']);

        return [$agency, $user];
    }

    public function test_social_account_toggle(): void
    {
        [$agency, $user] = $this->owner();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $this->actingAs($user);

        $response = $this->post("/social/accounts/{$account->id}/toggle");
        $response->assertStatus(302);
    }

    public function test_social_post_retry_failed(): void
    {
        [$agency, $user] = $this->owner();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'status' => 'failed',
        ]);
        $this->actingAs($user);

        $response = $this->post("/social/posts/{$post->id}/retry");
        $response->assertStatus(302);
    }

    public function test_social_post_content_scoring(): void
    {
        [$agency, $user] = $this->owner();
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $post = SocialPost::factory()->create([
            'agency_id' => $agency->id,
            'social_account_id' => $account->id,
            'content' => 'This is a great post about our new product launch!',
        ]);
        $this->actingAs($user);

        $response = $this->get("/social/posts/{$post->id}");
        $response->assertStatus(200);
    }

    public function test_campaign_with_client(): void
    {
        [$agency, $user] = $this->owner();
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $this->actingAs($user);

        $response = $this->post('/campaigns', [
            'name' => 'Client Campaign',
            'type' => 'general',
            'description' => 'Test campaign with client',
            'client_id' => $client->id,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addMonth()->toDateString(),
        ]);
        $response->assertStatus(302);
    }

    public function test_client_with_full_details(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/clients', [
            'name' => 'Full Detail Corp',
            'email' => 'info@fullcorp.com',
            'company' => 'Full Detail Corp',
            'phone' => '+1234567890',
            'website' => 'https://fullcorp.com',
            'industry' => 'Technology',
            'address' => '123 Tech Street',
            'notes' => 'Key enterprise client',
        ]);
        $response->assertRedirectContains('/clients/');
        $this->assertDatabaseHas('clients', ['name' => 'Full Detail Corp']);
    }

    public function test_invoice_mark_paid_workflow(): void
    {
        [$agency, $user] = $this->owner();
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $invoice = Invoice::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'status' => 'sent',
        ]);
        $this->actingAs($user);

        $response = $this->post("/invoices/{$invoice->id}/paid");
        $response->assertStatus(302);
    }

    public function test_content_create_and_view(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/content', [
            'name' => 'Brand Voice Guidelines',
            'type' => 'document',
            'content' => '<h1>Brand Guidelines</h1><p>Our brand voice is professional yet approachable.</p>',
        ]);
        $response->assertRedirectContains('/content/');
    }

    public function test_landing_page_create_and_publish(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/landing-pages', [
            'name' => 'Product Launch 2026',
            'headline' => 'Introducing Our Revolutionary Product',
            'content' => '<p>Join thousands of marketers...</p>',
            'cta_text' => 'Get Early Access',
            'cta_url' => 'https://example.com/early-access',
        ]);
        $response->assertRedirectContains('/landing-pages');
    }

    public function test_form_create_and_toggle(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/forms', [
            'name' => 'Newsletter Signup',
            'fields' => [
                ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ],
        ]);
        $response->assertStatus(302);
    }

    public function test_webhook_create(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/webhooks', [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['post.published'],
        ]);
        $response->assertRedirectContains('/webhooks/');
    }

    public function test_workflow_create(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/workflows', [
            'name' => 'Test Workflow',
            'trigger_type' => 'comment_received',
            'actions' => [
                ['type' => 'auto_reply', 'config' => ['message' => 'Thanks!']],
            ],
        ]);
        $response->assertRedirectContains('/workflows/');
    }

    public function test_agency_billing_upgrade(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/agency/billing/upgrade', ['plan' => 'pro']);
        $response->assertRedirect('/agency/billing');
    }

    public function test_search_functionality(): void
    {
        [$agency, $user] = $this->owner();
        Client::factory()->create(['agency_id' => $agency->id, 'name' => 'Searchable Client']);
        $this->actingAs($user);

        $response = $this->get('/search?q=Searchable&type=all');
        $response->assertStatus(200);
    }

    public function test_analytics_dashboard(): void
    {
        [$agency, $user] = $this->owner();
        SocialPost::factory()->count(5)->create([
            'agency_id' => $agency->id,
            'status' => 'published',
        ]);
        $this->actingAs($user);

        $response = $this->get('/analytics');
        $response->assertStatus(200);
    }

    public function test_logout_workflow(): void
    {
        [$agency, $user] = $this->owner();
        $this->actingAs($user);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
