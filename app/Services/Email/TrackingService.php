<?php

namespace App\Services\Email;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TrackingService
{
    /**
     * Generate a tracking pixel for email opens.
     */
    public function getTrackingPixel(int $recipientId): string
    {
        $hash = hash_hmac('sha256', (string) $recipientId, config('app.key'));
        $url = route('email.track.open', ['recipient' => $recipientId, 'h' => $hash]);

        return '<img src="'.$url.'" width="1" height="1" alt="" style="display:none;width:1px;height:1px;" />';
    }

    /**
     * Generate a tracked link URL.
     */
    public function getTrackedLink(string $url, int $recipientId): string
    {
        $hash = hash_hmac('sha256', (string) $recipientId . ':' . $url, config('app.key'));
        return route('email.track.click', [
            'recipient' => $recipientId,
            'h' => $hash,
            'url' => base64_encode($url),
        ]);
    }

    /**
     * Track an email open.
     */
    public function trackOpen(int $recipientId, string $hash): bool
    {
        if (! $this->verifyHash($recipientId, $hash)) {
            return false;
        }

        $recipient = EmailCampaignRecipient::find($recipientId);

        if (! $recipient || $recipient->status !== EmailCampaignRecipient::STATUS_SENT) {
            return false;
        }

        $recipient->update(['status' => EmailCampaignRecipient::STATUS_OPENED, 'opened_at' => now()]);
        EmailCampaign::where('id', $recipient->email_campaign_id)->increment('opened_count');

        return true;
    }

    /**
     * Track a link click.
     */
    public function trackClick(int $recipientId, string $hash, string $url): ?string
    {
        if (! $this->verifyHash($recipientId . ':' . $url, $hash)) {
            return null;
        }

        $recipient = EmailCampaignRecipient::find($recipientId);

        if (! $recipient) {
            return null;
        }

        $status = $recipient->status;
        if (in_array($status, [EmailCampaignRecipient::STATUS_SENT, EmailCampaignRecipient::STATUS_OPENED])) {
            $recipient->update(['status' => EmailCampaignRecipient::STATUS_CLICKED, 'clicked_at' => now()]);
            EmailCampaign::where('id', $recipient->email_campaign_id)->increment('clicked_count');
        }

        return base64_decode($url);
    }

    private function verifyHash(string $data, string $hash): bool
    {
        return hash_equals(hash_hmac('sha256', $data, config('app.key')), $hash);
    }
}
