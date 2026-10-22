<?php

namespace Tests\Feature\Email;

use App\Models\Agency;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\User;
use App\Services\Email\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailCampaignTest extends TestCase
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

    public function test_authenticated_user_can_view_campaigns(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.index'));

        $response->assertOk();
    }

    public function test_user_can_create_campaign(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('email.campaigns.store'), [
                'name' => 'Test Campaign',
                'type' => 'newsletter',
                'subject' => 'Test Subject',
                'content' => '<p>Hello World</p>',
                'from_name' => 'Agency',
                'from_email' => 'test@agency.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_campaigns', [
            'name' => 'Test Campaign',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_user_can_view_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.show', $campaign));

        $response->assertOk();
    }

    public function test_user_cannot_view_other_agency_campaign(): void
    {
        $otherAgency = Agency::factory()->create();
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $otherAgency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('email.campaigns.show', $campaign));

        // Returns 404 for security (don't reveal other agency's data exists)
        $response->assertNotFound();
    }

    public function test_user_can_update_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
            'name' => 'Original Campaign',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('email.campaigns.update', $campaign), [
                'name' => 'Updated Campaign',
                'type' => 'newsletter',
                'subject' => 'Updated Subject',
            ]);

        $response->assertRedirect();
        $campaign->refresh();
        $this->assertEquals('Updated Campaign', $campaign->name);
    }

    public function test_user_can_delete_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('email.campaigns.destroy', $campaign));

        $response->assertRedirect();
        // Campaign is soft-deleted, so check with trashed()
        $this->assertSoftDeleted('email_campaigns', ['id' => $campaign->id]);
    }

    public function test_service_can_create_campaign(): void
    {
        $campaign = $this->service->create($this->agency, [
            'name' => 'Service Test',
            'type' => 'newsletter',
            'subject' => 'Test Subject',
            'content' => '<p>Content</p>',
        ]);

        $this->assertInstanceOf(EmailCampaign::class, $campaign);
        $this->assertEquals('Service Test', $campaign->name);
        $this->assertEquals('draft', $campaign->status);
    }

    public function test_service_can_add_recipients(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $this->service->addRecipients($campaign, [
            ['email' => 'john@example.com', 'name' => 'John'],
            ['email' => 'jane@example.com', 'name' => 'Jane'],
        ]);

        $campaign->refresh();
        $this->assertEquals(2, $campaign->recipients_count);
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'john@example.com',
        ]);
    }

    public function test_service_can_send_campaign(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        EmailCampaignRecipient::factory()->count(5)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $campaign->update(['recipients_count' => 5]);

        $this->service->send($campaign);

        $campaign->refresh();
        $this->assertEquals('sent', $campaign->status);
        $this->assertEquals(5, $campaign->sent_count);
    }

    public function test_service_calculates_rates_correctly(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'recipients_count' => 100,
            'sent_count' => 100,
            'opened_count' => 50,
            'clicked_count' => 25,
        ]);

        $this->service->calculateRates($campaign);

        $campaign->refresh();
        $this->assertEquals(50.0, $campaign->open_rate);
        $this->assertEquals(25.0, $campaign->click_rate);
    }

    public function test_campaign_index_requires_authentication(): void
    {
        $response = $this->get(route('email.campaigns.index'));
        $response->assertRedirect(route('login'));
    }
}
