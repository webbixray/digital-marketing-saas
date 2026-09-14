<?php

namespace App\Jobs\Email;

use App\Models\EmailCampaign;
use App\Services\Email\SmtpEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 60;

    public function __construct(public int $campaignId) {}

    public function handle(SmtpEmailService $smtp): void
    {
        $campaign = EmailCampaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning("SendEmailCampaign: Campaign {$this->campaignId} not found");

            return;
        }

        if ($campaign->status === 'sent') {
            Log::info("SendEmailCampaign: Campaign {$this->campaignId} already sent");

            return;
        }

        $campaign->update(['status' => 'sending']);

        try {
            $results = $smtp->sendCampaign($campaign);

            $campaign->update([
                'status' => 'sent',
                'sent_at' => now(),
                'sent_count' => $results['sent'],
                'bounced_count' => $results['failed'],
            ]);

            Log::info("SendEmailCampaign: Campaign {$this->campaignId} sent: {$results['sent']} sent, {$results['failed']} failed.");
        } catch (\Exception $e) {
            $campaign->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error("SendEmailCampaign: Campaign {$this->campaignId} failed: {$e->getMessage()}");

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendEmailCampaign job failed for campaign {$this->campaignId}: {$exception->getMessage()}");

        $campaign = EmailCampaign::find($this->campaignId);
        if ($campaign && $campaign->status === 'sending') {
            $campaign->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
