<?php

namespace Tests\Feature\UAT;

use App\Models\Agency;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\User;
use App\Services\Email\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailMarketingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    private EmailCampaignService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->service = $this->app->make(EmailCampaignService::class);
    }

    /**
     * Complete UAT workflow: create → add clients → send → track opens/clicks → view analytics
     */
    public function test_full_email_campaign_lifecycle(): void
    {
        // Step 1: Create a campaign via service
        $campaign = $this->service->create($this->agency, [
            'name' => 'UAT Test Campaign',
            'type' => 'newsletter',
            'subject' => 'Welcome to Our Service',
            'content' => '<h1>Welcome!</h1><p>Thanks for joining us.</p>',
            'from_name' => 'Test Agency',
            'from_email' => 'hello@testagency.com',
        ]);

        $this->assertInstanceOf(EmailCampaign::class, $campaign);
        $this->assertEquals('UAT Test Campaign', $campaign->name);
        $this->assertEquals('newsletter', $campaign->type);
        $this->assertEquals('draft', $campaign->status);
        $this->assertEquals(0, $campaign->recipients_count);
        $this->assertEquals(0, $campaign->sent_count);

        // Step 2: Add recipients (clients) via service
        Client::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);

        $count = $this->service->addClientRecipients($campaign, $this->agency);
        $this->assertEquals(3, $count);

        $campaign->refresh();
        $this->assertEquals(3, $campaign->recipients_count);
        $this->assertEquals(3, $campaign->recipients()->count());

        // Step 3: Send the campaign via service
        $this->service->send($campaign);

        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals(3, $campaign->sent_count);
        $this->assertNotNull($campaign->sent_at);

        // Verify all recipients were marked as sent
        $sentRecipients = $campaign->recipients()->where('status', 'sent')->count();
        $this->assertEquals(3, $sentRecipients);

        // Step 4: Track opens
        $recipient1 = $campaign->recipients()->first();
        $this->service->trackOpen($recipient1);

        $campaign->refresh();
        $this->assertEquals(1, $campaign->opened_count);
        $this->assertGreaterThan(0, $campaign->open_rate);

        // Step 5: Track clicks
        $this->service->trackClick($recipient1);

        $campaign->refresh();
        $this->assertEquals(1, $campaign->clicked_count);
        $this->assertGreaterThan(0, $campaign->click_rate);

        // Step 6: Track another open (partial engagement)
        $recipient2 = $campaign->recipients()->skip(1)->first();
        $this->service->trackOpen($recipient2);

        $campaign->refresh();
        $this->assertEquals(2, $campaign->opened_count);

        // Step 7: Verify rates are calculated correctly
        $expectedOpenRate = round((2 / 3) * 100, 2);
        $expectedClickRate = round((1 / 3) * 100, 2);

        $this->assertEquals($expectedOpenRate, $campaign->open_rate);
        $this->assertEquals($expectedClickRate, $campaign->click_rate);

        // Step 8: View analytics via show route
        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.show', $campaign));

        $response->assertOk();
        $response->assertViewIs('email.campaigns.show');
        $response->assertViewHas('campaign');
    }

    /**
     * Test creating a campaign via web route
     */
    public function test_create_campaign_via_web_route(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.create'));

        $response->assertOk();
        $response->assertViewIs('email.campaigns.create');
    }

    /**
     * Test storing a campaign via web route
     */
    public function test_store_campaign_via_web_route(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.store'), [
                'name' => 'Web Test Campaign',
                'type' => 'newsletter',
                'subject' => 'Test Subject',
                'content' => '<p>Test content</p>',
                'from_name' => 'Agency',
                'from_email' => 'test@agency.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'Web Test Campaign',
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Test viewing campaigns index via web route
     */
    public function test_view_campaigns_index_via_web_route(): void
    {
        EmailCampaign::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.index'));

        $response->assertOk();
        $campaigns = $response->viewData('campaigns');
        $this->assertCount(3, $campaigns);
    }

    /**
     * Test adding clients to campaign via web route
     */
    public function test_add_clients_via_web_route(): void
    {
        Client::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);

        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.add-clients', $campaign));

        $response->assertRedirect(route('email.campaigns.show', $campaign));
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
        ]);

        $campaign->refresh();
        $this->assertEquals(2, $campaign->recipients_count);
    }

    /**
     * Test sending campaign via web route
     */
    public function test_send_campaign_via_web_route(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        EmailCampaignRecipient::factory()->count(3)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $campaign->update(['recipients_count' => 3]);

        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.send', $campaign));

        $response->assertRedirect(route('email.campaigns.show', $campaign));

        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals(3, $campaign->sent_count);
    }

    /**
     * Test service: create method with recipients
     */
    public function test_service_create_with_recipients(): void
    {
        $campaign = $this->service->create($this->agency, [
            'name' => 'Campaign With Recipients',
            'type' => 'newsletter',
            'subject' => 'Subject',
            'content' => '<p>Content</p>',
            'recipients' => [
                ['email' => 'user1@example.com', 'name' => 'User 1'],
                ['email' => 'user2@example.com', 'name' => 'User 2'],
            ],
        ]);

        $this->assertEquals('Campaign With Recipients', $campaign->name);
        $this->assertEquals(2, $campaign->recipients_count);
    }

    /**
     * Test service: send method prevents double-sending
     */
    public function test_service_send_prevents_double_send(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Campaign is not in sendable status');

        $this->service->send($campaign);
        $this->assertTrue(true, "Expected exception was thrown");
    }

    /**
     * Test service: calculateRates computes correct percentages
     */
    public function test_service_calculate_rates(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'recipients_count' => 200,
            'sent_count' => 200,
            'opened_count' => 80,
            'clicked_count' => 30,
            'bounced_count' => 10,
        ]);

        $this->service->calculateRates($campaign);

        $campaign->refresh();
        $this->assertEquals(40.0, $campaign->open_rate);  // 80/200 * 100
        $this->assertEquals(15.0, $campaign->click_rate); // 30/200 * 100
        $this->assertEquals(5.0, $campaign->bounce_rate); // 10/200 * 100
    }

    /**
     * Test service: trackOpen only transitions from 'sent' status
     */
    public function test_service_track_open_only_from_sent_status(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
        ]);

        $recipient = EmailCampaignRecipient::factory()->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'clicked', // already clicked, should not count again
        ]);

        $this->service->trackOpen($recipient);

        $campaign->refresh();
        // opened_count should not increment since status was 'clicked', not 'sent'
        $this->assertEquals(0, $campaign->opened_count);
    }

    /**
     * Test service: trackClick transitions from sent or opened
     */
    public function test_service_track_click_from_opened(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'opened_count' => 1,
            'clicked_count' => 0,
            'recipients_count' => 1,
        ]);

        $recipient = EmailCampaignRecipient::factory()->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'opened',
        ]);

        $this->service->trackClick($recipient);

        $campaign->refresh();
        $this->assertEquals(1, $campaign->clicked_count);
    }

    /**
     * Test service: addRecipients bulk inserts correctly
     */
    public function test_service_add_recipients_bulk(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $recipients = [];
        for ($i = 0; $i < 10; $i++) {
            $recipients[] = [
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
            ];
        }

        $this->service->addRecipients($campaign, $recipients);

        $campaign->refresh();
        $this->assertEquals(10, $campaign->recipients_count);
        $this->assertEquals(10, $campaign->recipients()->count());
    }

    /**
     * Test service: getStats returns campaign analytics (via show route)
     */
    public function test_service_get_stats_via_show_route(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'recipients_count' => 100,
            'sent_count' => 95,
            'opened_count' => 50,
            'clicked_count' => 25,
            'open_rate' => 52.63,
            'click_rate' => 26.32,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.show', $campaign));

        $response->assertOk();
        $viewCampaign = $response->viewData('campaign');

        $this->assertEquals('sent', $viewCampaign->status);
        $this->assertEquals(100, $viewCampaign->recipients_count);
        $this->assertEquals(95, $viewCampaign->sent_count);
        $this->assertEquals(50, $viewCampaign->opened_count);
        $this->assertEquals(25, $viewCampaign->clicked_count);
        $this->assertEquals(52.63, $viewCampaign->open_rate);
        $this->assertEquals(26.32, $viewCampaign->click_rate);
    }

    /**
     * Test agency isolation: user cannot see other agency's campaign analytics
     */
    public function test_agency_isolation_for_analytics(): void
    {
        $otherAgency = Agency::factory()->create();
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $otherAgency->id,
            'status' => 'sent',
            'opened_count' => 50,
            'clicked_count' => 25,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.show', $campaign));

        $response->assertNotFound();
    }

    /**
     * Test full workflow: create → add recipients → send → track → analytics
     * with multiple recipients and varied engagement
     */
    public function test_full_workflow_with_multiple_engagement_levels(): void
    {
        // Create campaign
        $campaign = $this->service->create($this->agency, [
            'name' => 'Engagement Test',
            'type' => 'promotional',
            'subject' => 'Big Sale!',
            'content' => '<h1>Sale!</h1>',
            'from_name' => 'Test Agency',
            'from_email' => 'sale@testagency.com',
        ]);

        // Add recipients
        $this->service->addRecipients($campaign, [
            ['email' => 'open1@test.com', 'name' => 'Open Only'],
            ['email' => 'click1@test.com', 'name' => 'Clicker 1'],
            ['email' => 'click2@test.com', 'name' => 'Clicker 2'],
            ['email' => 'noop@test.com', 'name' => 'No Engagement'],
        ]);

        $this->assertEquals(4, $campaign->recipients_count);

        // Send
        $this->service->send($campaign);
        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals(4, $campaign->sent_count);

        // Track: 1 open, 2 clicks (clickers also open)
        $recipients = $campaign->recipients;
        $this->service->trackOpen($recipients[0]); // open only
        $this->service->trackOpen($recipients[1]); // clicker 1 opens
        $this->service->trackClick($recipients[1]); // clicker 1 clicks
        $this->service->trackOpen($recipients[2]); // clicker 2 opens
        $this->service->trackClick($recipients[2]); // clicker 2 clicks

        $campaign->refresh();
        $this->assertEquals(3, $campaign->opened_count);
        $this->assertEquals(2, $campaign->clicked_count);

        // Verify rates
        $expectedOpenRate = round((3 / 4) * 100, 2);
        $expectedClickRate = round((2 / 4) * 100, 2);
        $this->assertEquals($expectedOpenRate, $campaign->open_rate);
        $this->assertEquals($expectedClickRate, $campaign->click_rate);

        // View analytics
        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.show', $campaign));
        $response->assertOk();
    }

    /**
     * Test routes are protected by authentication
     */
    public function test_email_campaign_routes_require_auth(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        // All routes should redirect to login for unauthenticated users
        $this->get(route('email.campaigns.index'))->assertRedirect(route('login'));
        $this->get(route('email.campaigns.create'))->assertRedirect(route('login'));
        $this->get(route('email.campaigns.show', $campaign))->assertRedirect(route('login'));
        $this->post(route('email.campaigns.store', [
            'name' => 'Test', 'type' => 'newsletter', 'subject' => 'Test',
        ]))->assertRedirect(route('login'));
        $this->post(route('email.campaigns.send', $campaign))->assertRedirect(route('login'));
        $this->post(route('email.campaigns.add-clients', $campaign))->assertRedirect(route('login'));
    }
}
