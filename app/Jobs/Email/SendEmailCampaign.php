<?php

namespace App\Jobs\Email;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Services\Email\SmtpEmailService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEmailCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 60;

    /**
     * Number of recipients per batch job.
     */
    private const BATCH_SIZE = 50;

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

        // Check quota before sending
        $this->checkQuota($campaign);

        $campaign->update(['status' => 'sending']);

        try {
            $recipientIds = $campaign->recipients()
                ->where('status', EmailCampaignRecipient::STATUS_PENDING)
                ->pluck('id')
                ->toArray();

            $totalRecipients = count($recipientIds);

            if ($totalRecipients === 0) {
                $campaign->update(['status' => 'sent', 'sent_at' => now()]);

                return;
            }

            $batches = array_chunk($recipientIds, self::BATCH_SIZE);
            $jobs = [];

            foreach ($batches as $batchIds) {
                $jobs[] = new SendEmailBatch($campaign->id, $batchIds);
            }

            Bus::batch($jobs)
                ->then(function (Batch $batch) use ($campaign) {
                    // All batches completed
                    $campaign->refresh();
                    $sentCount = $campaign->recipients()->where('status', EmailCampaignRecipient::STATUS_SENT)->count();
                    $failedCount = $campaign->recipients()->where('status', EmailCampaignRecipient::STATUS_BOUNCED)->count();

                    $campaign->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'sent_count' => $sentCount,
                        'bounced_count' => $failedCount,
                    ]);

                    Log::info("SendEmailCampaign: Campaign {$campaign->id} completed: {$sentCount} sent, {$failedCount} failed.");
                })
                ->catch(function (Batch $batch, Throwable $e) use ($campaign) {
                    // Batch failed
                    $campaign->update([
                        'status' => 'failed',
                        'failed_at' => now(),
                        'error_message' => $e->getMessage(),
                    ]);

                    Log::error("SendEmailCampaign: Campaign {$campaign->id} failed: {$e->getMessage()}");
                })
                ->name("Email campaign {$campaign->id}")
                ->onQueue('email')
                ->dispatch();

        } catch (\Exception $e) {
            $campaign->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error("SendEmailCampaign: Campaign {$campaign->id} failed: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Check that the agency hasn't exceeded their email quota.
     */
    private function checkQuota(EmailCampaign $campaign): void
    {
        $agency = $campaign->agency;

        if (! $agency) {
            throw new \RuntimeException('Agency not found for campaign');
        }

        // Define plan limits
        $planLimits = [
            'free' => 100,
            'starter' => 1000,
            'pro' => 10000,
            'enterprise' => 100000,
        ];

        $plan = $agency->subscription_plan ?? 'free';
        $limit = $planLimits[$plan] ?? 100;

        $pendingCount = $campaign->recipients()->where('status', EmailCampaignRecipient::STATUS_PENDING)->count();

        if ($pendingCount > $limit) {
            throw new \RuntimeException(
                "Email quota exceeded. Your plan '{$plan}' allows {$limit} emails per campaign. Current campaign has {$pendingCount} recipients."
            );
        }
    }

    public function failed(Throwable $exception): void
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
