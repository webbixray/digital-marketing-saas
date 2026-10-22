<?php

namespace Tests\Feature\UAT;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteUserJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_journey_registration_to_upgrade(): void
    {
        // === STEP 1: REGISTRATION ===
        $response = $this->post('/register', [
            'agency_name' => 'UAT Test Agency',
            'name' => 'UAT Test User',
            'email' => 'uat@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(302);
        $this->assertDatabaseHas('users', ['email' => 'uat@example.com']);
        $this->assertDatabaseHas('agencies', ['name' => 'UAT Test Agency']);

        $user = User::where('email', 'uat@example.com')->first();
        $this->assertNotNull($user);
        $this->actingAs($user);

        // === STEP 2: LOGIN (simulate fresh login) ===
        $this->post('/logout');
        $this->assertGuest();

        $loginResponse = $this->post('/login', [
            'email' => 'uat@example.com',
            'password' => 'password',
        ]);
        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        // === STEP 3: DASHBOARD ===
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertStatus(200);

        // === STEP 4: CREATE SOCIAL POST ===
        $socialAccount = SocialAccount::factory()->create([
            'agency_id' => $user->agency_id,
        ]);

        $socialPostResponse = $this->actingAs($user)->post('/social/posts', [
            'social_account_id' => $socialAccount->id,
            'content' => 'This is a UAT test social post about our amazing new product launch!',
            'platform' => 'facebook',
        ]);
        $socialPostResponse->assertStatus(302);
        $this->assertDatabaseHas('social_posts', [
            'content' => 'This is a UAT test social post about our amazing new product launch!',
        ]);

        // View social posts index
        $socialIndexResponse = $this->actingAs($user)->get('/social/posts');
        $socialIndexResponse->assertStatus(200);

        // === STEP 5: CREATE CAMPAIGN ===
        $campaignResponse = $this->actingAs($user)->post('/campaigns', [
            'name' => 'UAT Campaign 2026',
            'type' => 'general',
            'description' => 'UAT test campaign for full user journey validation',
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addMonth()->toDateString(),
        ]);
        $campaignResponse->assertStatus(302);
        $this->assertDatabaseHas('campaigns', ['name' => 'UAT Campaign 2026']);

        // View campaigns index
        $campaignsIndexResponse = $this->actingAs($user)->get('/campaigns');
        $campaignsIndexResponse->assertStatus(200);

        // === STEP 6: CREATE CLIENT ===
        $clientResponse = $this->actingAs($user)->post('/clients', [
            'name' => 'UAT Client Corp',
            'email' => 'client@uat-corp.com',
            'company' => 'UAT Client Corp',
            'phone' => '+15551234567',
            'website' => 'https://uat-corp.com',
            'industry' => 'Technology',
            'notes' => 'Key client for UAT testing',
        ]);
        $clientResponse->assertRedirectContains('/clients/');
        $this->assertDatabaseHas('clients', ['name' => 'UAT Client Corp']);

        // View clients index
        $clientsIndexResponse = $this->actingAs($user)->get('/clients');
        $clientsIndexResponse->assertStatus(200);

        // === STEP 7: CREATE INVOICE ===
        $client = Client::where('email', 'client@uat-corp.com')->first();
        $this->assertNotNull($client);

        $invoiceResponse = $this->actingAs($user)->post('/invoices', [
            'issue_date' => Carbon::now()->toDateString(),
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'notes' => 'UAT test invoice',
            'items' => [
                [
                    'description' => 'Social Media Management',
                    'quantity' => 1,
                    'unit_price' => 5000.00,
                ],
            ],
        ]);
        $invoiceResponse->assertStatus(302);
        $this->assertDatabaseHas('invoices', [
            'agency_id' => $user->agency_id,
            'status' => 'pending',
        ]);

        // View invoices index
        $invoicesIndexResponse = $this->actingAs($user)->get('/invoices');
        $invoicesIndexResponse->assertStatus(200);

        $invoice = Invoice::where('agency_id', $user->agency_id)->first();
        $this->assertNotNull($invoice);

        // === STEP 8: MARK INVOICE PAID ===
        $paidResponse = $this->actingAs($user)->post("/invoices/{$invoice->id}/paid", [
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TXN-'.uniqid(),
        ]);
        $paidResponse->assertStatus(302);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);

        // === STEP 9: CHECK ANALYTICS ===
        // Create some analytics data
        SocialPost::factory()->count(3)->create([
            'agency_id' => $user->agency_id,
            'status' => 'published',
        ]);

        $analyticsResponse = $this->actingAs($user)->get('/analytics');
        $analyticsResponse->assertStatus(200);

        // === STEP 10: INVITE TEAM MEMBER ===
        $teamIndexResponse = $this->actingAs($user)->get('/agency/team');
        $teamIndexResponse->assertStatus(200);

        $inviteResponse = $this->actingAs($user)->post('/agency/team/invite', [
            'name' => 'UAT Teammate',
            'email' => 'teammate@example.com',
            'role' => 'member',
        ]);
        $inviteResponse->assertStatus(302);

        $this->assertDatabaseHas('users', ['email' => 'teammate@example.com']);

        // === STEP 11: LOGOUT ===
        $logoutResponse = $this->actingAs($user)->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_subscription_upgrade_journey(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        // Verify current plan
        $this->assertEquals('starter', $agency->subscription_plan);

        // Upgrade subscription
        $upgradeResponse = $this->post('/agency/billing/upgrade', [
            'plan' => 'pro',
        ]);
        $upgradeResponse->assertRedirect('/agency/billing');

        // Refresh and verify upgrade
        $agency->refresh();
        $this->assertEquals('pro', $agency->subscription_plan);

        // View billing page
        $billingResponse = $this->get('/agency/billing');
        $billingResponse->assertStatus(200);
    }

    public function test_social_post_creation_and_management(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);
        $account = SocialAccount::factory()->create(['agency_id' => $agency->id]);
        $this->actingAs($user);

        // View posts index
        $this->get('/social/posts')->assertStatus(200);

        // View create form
        $this->get('/social/posts/create')->assertStatus(200);

        // Create post
        $response = $this->post('/social/posts', [
            'social_account_id' => $account->id,
            'content' => 'Testing our social media management capabilities',
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('social_posts', [
            'content' => 'Testing our social media management capabilities',
        ]);
    }

    public function test_campaign_with_client_workflow(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $this->actingAs($user);

        $response = $this->post('/campaigns', [
            'name' => 'Client Campaign',
            'type' => 'product_launch',
            'description' => 'Test campaign linked to client',
            'client_id' => $client->id,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addMonth()->toDateString(),
        ]);
        $response->assertStatus(302);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Client Campaign',
            'client_id' => $client->id,
        ]);
    }

    public function test_client_invoice_payment_flow(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);
        $client = Client::factory()->create(['agency_id' => $agency->id]);
        $invoice = Invoice::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'status' => 'pending',
        ]);
        $this->actingAs($user);

        // View invoice
        $this->get("/invoices/{$invoice->id}")->assertStatus(200);

        // Mark as paid
        $response = $this->post("/invoices/{$invoice->id}/paid", [
            'payment_method' => 'credit_card',
            'transaction_id' => 'TXN-'.uniqid(),
        ]);
        $response->assertStatus(302);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_team_management_workflow(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $owner = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($owner);

        // View team page
        $this->get('/agency/team')->assertStatus(200);

        // Invite new member
        $inviteResponse = $this->post('/agency/team/invite', [
            'name' => 'New Team Member',
            'email' => 'newmember@example.com',
            'role' => 'member',
        ]);
        $inviteResponse->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'email' => 'newmember@example.com',
            'agency_id' => $agency->id,
        ]);
    }

    public function test_analytics_dashboard_with_data(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
            'password' => bcrypt('password'),
        ]);

        // Seed analytics data
        SocialPost::factory()->count(5)->create([
            'agency_id' => $agency->id,
            'status' => 'published',
        ]);
        Campaign::factory()->count(2)->create([
            'agency_id' => $agency->id,
            'status' => 'active',
        ]);
        Client::factory()->count(3)->create([
            'agency_id' => $agency->id,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get('/analytics');
        $response->assertStatus(200);
    }

    public function test_full_registration_to_logout_journey(): void
    {
        // Register new agency owner
        $response = $this->post('/register', [
            'agency_name' => 'Journey Agency',
            'name' => 'Journey User',
            'email' => 'journey@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(302);

        $user = User::where('email', 'journey@example.com')->first();
        $this->assertNotNull($user);
        $this->actingAs($user);

        // Visit all main pages in sequence
        $this->get('/dashboard')->assertStatus(200);
        $this->get('/social/posts')->assertStatus(200);
        $this->get('/campaigns')->assertStatus(200);
        $this->get('/clients')->assertStatus(200);
        $this->get('/invoices')->assertStatus(200);
        $this->get('/analytics')->assertStatus(200);
        $this->get('/agency/team')->assertStatus(200);

        // Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logout_workflow(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
