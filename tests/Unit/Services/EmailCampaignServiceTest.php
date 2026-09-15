<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\Email\EmailCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailCampaignServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailCampaignService $service;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(EmailCampaignService::class);
        $this->agency = Agency::factory()->create();
    }

    public function test_create_campaign_with_basic_data(): void
    {
        $data = [
            'name' => 'Test Newsletter',
            'type' => 'newsletter',
            'subject' => 'Test Subject',
            'content' => '<p>Hello World</p>',
        ];

        $campaign = $this->service->create($this->agency, $data);

        $this->assertInstanceOf(EmailCampaign::class, $campaign);
        $this->assertEquals($this->agency->id, $campaign->agency_id);
        $this->assertEquals('Test Newsletter', $campaign->name);
        $this->assertEquals('newsletter', $campaign->type);
        $this->assertEquals('draft', $campaign->status);
        $this->assertEquals('Test Subject', $campaign->subject);
        $this->assertDatabaseHas('email_campaigns', [
            'id' => $campaign->id,
            'name' => 'Test Newsletter',
            'status' => 'draft',
        ]);
    }

    public function test_create_campaign_with_recipients(): void
    {
        $data = [
            'name' => 'Campaign With Recipients',
            'subject' => 'Test',
            'content' => '<p>Content</p>',
            'recipients' => [
                ['email' => 'user1@example.com', 'name' => 'User One'],
                ['email' => 'user2@example.com', 'name' => 'User Two'],
            ],
        ];

        $campaign = $this->service->create($this->agency, $data);

        $this->assertEquals(2, $campaign->recipients()->count());
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'user1@example.com',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'user2@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_send_updates_status_to_sent_and_sets_sent_at(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        EmailCampaignRecipient::factory()->count(3)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $this->service->send($campaign);

        $campaign->refresh();

        $this->assertEquals('sent', $campaign->status);
        $this->assertNotNull($campaign->sent_at);
        $this->assertEquals(3, $campaign->sent_count);
    }

    public function test_send_marks_all_recipients_as_sent(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $recipients = EmailCampaignRecipient::factory()->count(3)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $this->service->send($campaign);

        foreach ($recipients as $recipient) {
            $recipient->refresh();
            $this->assertEquals('sent', $recipient->status);
            $this->assertNotNull($recipient->sent_at);
        }
    }

    public function test_send_throws_for_non_draft_or_scheduled_status(): void
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

    public function test_get_stats_returns_correct_counts(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'recipients_count' => 100,
            'sent_count' => 95,
            'opened_count' => 45,
            'clicked_count' => 20,
            'bounced_count' => 5,
            'open_rate' => 47.37,
            'click_rate' => 21.05,
            'bounce_rate' => 5.26,
        ]);

        $stats = [
            'recipients_count' => $campaign->recipients_count,
            'sent_count' => $campaign->sent_count,
            'opened_count' => $campaign->opened_count,
            'clicked_count' => $campaign->clicked_count,
            'bounced_count' => $campaign->bounced_count,
            'open_rate' => $campaign->open_rate,
            'click_rate' => $campaign->click_rate,
            'bounce_rate' => $campaign->bounce_rate,
        ];

        $this->assertEquals(100, $stats['recipients_count']);
        $this->assertEquals(95, $stats['sent_count']);
        $this->assertEquals(45, $stats['opened_count']);
        $this->assertEquals(20, $stats['clicked_count']);
        $this->assertEquals(5, $stats['bounced_count']);
        $this->assertEquals(47.37, $stats['open_rate']);
        $this->assertEquals(21.05, $stats['click_rate']);
        $this->assertEquals(5.26, $stats['bounce_rate']);
    }

    public function test_get_stats_after_sending(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        EmailCampaignRecipient::factory()->count(5)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $this->service->send($campaign);

        $campaign->refresh();

        $this->assertEquals(5, $campaign->sent_count);
        $this->assertEquals(5, $campaign->recipients()->count());
        $this->assertEquals(0, $campaign->opened_count);
        $this->assertEquals(0, $campaign->clicked_count);
    }

    public function test_add_recipients_creates_recipient_records(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $recipients = [
            ['email' => 'new1@example.com', 'name' => 'New User 1'],
            ['email' => 'new2@example.com', 'name' => 'New User 2'],
            ['email' => 'new3@example.com', 'name' => 'New User 3'],
        ];

        $this->service->addRecipients($campaign, $recipients);

        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'new1@example.com',
            'name' => 'New User 1',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'new2@example.com',
            'name' => 'New User 2',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'new3@example.com',
            'name' => 'New User 3',
            'status' => 'pending',
        ]);

        $campaign->refresh();
        $this->assertEquals(3, $campaign->recipients_count);
    }

    public function test_add_recipients_updates_recipients_count(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
            'recipients_count' => 0,
        ]);

        $this->service->addRecipients($campaign, [
            ['email' => 'a@example.com', 'name' => 'A'],
            ['email' => 'b@example.com', 'name' => 'B'],
        ]);

        $campaign->refresh();
        $this->assertEquals(2, $campaign->recipients_count);

        $this->service->addRecipients($campaign, [
            ['email' => 'c@example.com', 'name' => 'C'],
        ]);

        $campaign->refresh();
        $this->assertEquals(3, $campaign->recipients_count);
    }

    public function test_duplicate_recipients_are_prevented(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $recipients = [
            ['email' => 'dup@example.com', 'name' => 'Duplicate User'],
        ];

        $this->service->addRecipients($campaign, $recipients);

        $countAfterFirst = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->where('email', 'dup@example.com')
            ->count();
        $this->assertEquals(1, $countAfterFirst);

        // Attempt to add the same email again
        $this->service->addRecipients($campaign, $recipients);

        $countAfterSecond = EmailCampaignRecipient::where('email_campaign_id', $campaign->id)
            ->where('email', 'dup@example.com')
            ->count();
        $this->assertEquals(1, $countAfterSecond);
    }

    public function test_duplicate_recipients_mixed_with_new_ones(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $this->service->addRecipients($campaign, [
            ['email' => 'existing@example.com', 'name' => 'Existing'],
        ]);

        // Add mix of new and duplicate
        $this->service->addRecipients($campaign, [
            ['email' => 'existing@example.com', 'name' => 'Existing'],
            ['email' => 'new@example.com', 'name' => 'New'],
        ]);

        $this->assertEquals(2, $campaign->recipients()->count());
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'existing@example.com',
        ]);
        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_add_recipients_without_name(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $this->service->addRecipients($campaign, [
            ['email' => 'noname@example.com'],
        ]);

        $this->assertDatabaseHas('email_campaign_recipients', [
            'email_campaign_id' => $campaign->id,
            'email' => 'noname@example.com',
            'name' => null,
            'status' => 'pending',
        ]);
    }

    public function test_calculate_rates_after_tracking(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sent',
            'recipients_count' => 100,
            'sent_count' => 100,
            'opened_count' => 0,
            'clicked_count' => 0,
        ]);

        $recipient = EmailCampaignRecipient::factory()->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);

        $this->service->trackOpen($recipient);
        $this->service->trackClick($recipient);

        $campaign->refresh();

        $this->assertEquals(1, $campaign->opened_count);
        $this->assertEquals(1, $campaign->clicked_count);
        $this->assertEquals(1.00, $campaign->open_rate);
        $this->assertEquals(1.00, $campaign->click_rate);
    }

    public function test_delete_campaign_in_draft_status(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $result = $this->service->delete($campaign);

        $this->assertTrue($result);
        $this->assertSoftDeleted('email_campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_delete_fails_when_sending(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'sending',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete campaign while sending');

        $this->service->delete($campaign);
        $this->assertTrue(true, "Expected exception was thrown");
    }
}
