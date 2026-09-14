<?php

namespace App\Jobs\Email;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\Email\SmtpEmailService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public int $backoff = 30;

    public function __construct(
        public int $campaignId,
        public array $recipientIds,
    ) {}

    public function handle(SmtpEmailService $smtp): void
    {
        // Check if the batch was cancelled
        if ($this->batch()->cancelled()) {
            return;
        }

        $campaign = EmailCampaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning("SendEmailBatch: Campaign {$this->campaignId} not found");

            return;
        }

        $unsubscribeBase = url('/email/unsubscribe/');

        foreach ($this->recipientIds as $recipientId) {
            $recipient = EmailCampaignRecipient::find($recipientId);

            if (! $recipient) {
                continue;
            }

            // Skip already sent or unsubscribed
            if ($recipient->status !== EmailCampaignRecipient::STATUS_PENDING) {
                continue;
            }

            try {
                $unsubscribeUrl = $unsubscribeBase.$recipient->id;
                $smtp->sendToRecipient($campaign, $recipient, $unsubscribeUrl);
                $recipient->update(['status' => EmailCampaignRecipient::STATUS_SENT, 'sent_at' => now()]);
            } catch (\Exception $e) {
                $recipient->update(['status' => EmailCampaignRecipient::STATUS_BOUNCED, 'bounced_at' => now()]);
                Log::error("SendEmailBatch: Failed to send to {$recipient->email}: {$e->getMessage()}");
            }
        }
    }
}
