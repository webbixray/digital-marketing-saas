<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\Email\SmtpEmailService;
use App\Services\Email\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_smtp_service_sends_campaign(): void
    {
        $agency = Agency::factory()->create();
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $agency->id,
            'status' => 'draft',
        ]);

        EmailCampaignRecipient::factory()->count(3)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        $campaign->update(['recipients_count' => 3]);

        $service = new SmtpEmailService;
        $results = $service->sendCampaign($campaign);

        $this->assertEquals(3, $results['sent']);
        $this->assertEquals(0, $results['failed']);
    }

    public function test_smtp_service_skips_unsubscribed_recipients(): void
    {
        $agency = Agency::factory()->create();
        $campaign = EmailCampaign::factory()->create([
            'agency_id' => $agency->id,
            'status' => 'draft',
        ]);

        EmailCampaignRecipient::factory()->count(2)->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'pending',
        ]);

        EmailCampaignRecipient::factory()->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'unsubscribed',
        ]);

        $campaign->update(['recipients_count' => 3]);

        $service = new SmtpEmailService;
        $results = $service->sendCampaign($campaign);

        $this->assertEquals(2, $results['sent']);
        $this->assertEquals(0, $results['failed']);
        $this->assertEquals(1, $results['skipped']);
    }

    public function test_tracking_service_generates_pixel(): void
    {
        $service = new TrackingService;
        $pixel = $service->getTrackingPixel(1);

        $this->assertStringContainsString('<img', $pixel);
        $this->assertStringContainsString('width="1"', $pixel);
        $this->assertStringContainsString('height="1"', $pixel);
    }

    public function test_tracking_service_generates_tracked_link(): void
    {
        $service = new TrackingService;
        $url = 'https://example.com/test';
        $trackedUrl = $service->getTrackedLink($url, 1);

        $this->assertStringContainsString('email/track/click', $trackedUrl);
        $this->assertStringContainsString('h=', $trackedUrl);
    }

    public function test_tracking_service_verifies_hash(): void
    {
        $service = new TrackingService;
        $url = 'https://example.com/test';

        // Create a recipient for tracking
        $recipient = EmailCampaignRecipient::factory()->create([
            'status' => 'sent',
        ]);

        $trackedUrl = $service->getTrackedLink($url, $recipient->id);

        // Extract hash and encoded URL from the tracked link
        parse_str(parse_url($trackedUrl, PHP_URL_QUERY), $params);
        $hash = $params['h'];
        $encodedUrl = $params['url']; // This is base64 encoded

        // The service should verify with the encoded URL
        $result = $service->trackClick($recipient->id, $hash, $encodedUrl);
        $this->assertEquals($url, $result);
    }

    public function test_tracking_service_rejects_invalid_hash(): void
    {
        $service = new TrackingService;
        $url = 'https://example.com/test';

        $result = $service->trackClick(1, 'invalid_hash', $url);
        $this->assertNull($result);
    }
}
