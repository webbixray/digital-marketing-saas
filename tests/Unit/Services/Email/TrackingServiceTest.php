<?php

namespace Tests\Unit\Services\Email;

use App\Services\Email\TrackingService;
use App\Models\Agency;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_tracking_pixel(): void
    {
        $service = new TrackingService();
        $pixel = $service->getTrackingPixel(1);

        $this->assertStringContainsString('img', $pixel);
        $this->assertStringContainsString('width="1"', $pixel);
        $this->assertStringContainsString('height="1"', $pixel);
    }

    public function test_generates_tracked_link(): void
    {
        $service = new TrackingService();
        $url = $service->getTrackedLink('https://example.com', 1);

        $this->assertStringContainsString('email/track/click', $url);
        $this->assertStringContainsString('h=', $url);
    }

    public function test_track_open_creates_open_event(): void
    {
        $campaign = EmailCampaign::factory()->create(['opened_count' => 0]);
        $recipient = EmailCampaignRecipient::factory()->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);
        $service = new TrackingService();
        
        $pixel = $service->getTrackingPixel($recipient->id);
        // Extract hash from pixel
        preg_match('/h=([a-f0-9]+)/', $pixel, $matches);
        $hash = $matches[1];
        
        $service->trackOpen($recipient->id, $hash);
        
        $recipient->refresh();
        $this->assertEquals('opened', $recipient->status);
    }

    public function test_track_open_with_invalid_hash_returns_false(): void
    {
        $recipient = EmailCampaignRecipient::factory()->create(['status' => 'sent']);
        $service = new TrackingService();
        
        $result = $service->trackOpen($recipient->id, 'invalid_hash');
        
        $this->assertFalse($result);
    }

    public function test_track_click_with_valid_hash(): void
    {
        $service = new TrackingService();
        $url = 'https://example.com/test';
        $recipient = EmailCampaignRecipient::factory()->create(['status' => 'sent']);
        
        $trackedUrl = $service->getTrackedLink($url, $recipient->id);
        parse_str(parse_url($trackedUrl, PHP_URL_QUERY), $params);
        
        $result = $service->trackClick($recipient->id, $params['h'], $params['url']);
        
        $this->assertEquals($url, $result);
    }

    public function test_track_click_increments_campaign_click_count(): void
    {
        $campaign = EmailCampaign::factory()->create(['clicked_count' => 0]);
        $recipient = EmailCampaignRecipient::factory()->create([
            'email_campaign_id' => $campaign->id,
            'status' => 'sent',
        ]);
        $service = new TrackingService();
        
        $trackedUrl = $service->getTrackedLink('https://example.com', $recipient->id);
        parse_str(parse_url($trackedUrl, PHP_URL_QUERY), $params);
        
        $service->trackClick($recipient->id, $params['h'], $params['url']);
        
        $campaign->refresh();
        $this->assertEquals(1, $campaign->clicked_count);
    }

    public function test_track_click_with_invalid_hash_returns_null(): void
    {
        $service = new TrackingService();
        $result = $service->trackClick(1, 'invalid_hash', base64_encode('https://example.com'));
        
        $this->assertNull($result);
    }
}
