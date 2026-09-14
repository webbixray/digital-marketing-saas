<?php

namespace App\Services\Email;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmtpEmailService
{
    public function sendCampaign(EmailCampaign $campaign): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'total' => 0,
            'skipped' => 0,
        ];

        $recipients = $campaign->recipients()
            ->where('status', 'pending')
            ->get();

        $results['total'] = $recipients->count();

        // Build base unsubscribe URL for this campaign
        $unsubscribeBase = url('/email/unsubscribe/');

        foreach ($recipients as $recipient) {
            // Skip unsubscribed recipients
            if ($recipient->status === EmailCampaignRecipient::STATUS_UNSUBSCRIBED) {
                $results['skipped']++;

                continue;
            }

            $unsubscribeUrl = $unsubscribeBase.$recipient->id.'?h='.EmailCampaign::getUnsubscribeHash($recipient->id, $campaign->id);

            try {
                $this->sendToRecipient($campaign, $recipient, $unsubscribeUrl);
                $recipient->update(['status' => 'sent', 'sent_at' => now()]);
                $results['sent']++;
            } catch (\Exception $e) {
                $recipient->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                $results['failed']++;
                Log::error("Email send failed to {$recipient->email}: ".$e->getMessage());
            }
        }

        return $results;
    }

    public function sendToRecipient(EmailCampaign $campaign, EmailCampaignRecipient $recipient, ?string $unsubscribeUrl = null): void
    {
        Mail::html($this->wrapWithLayout($campaign, $recipient, $unsubscribeUrl), function ($message) use ($campaign, $recipient) {
            $message->to($recipient->email, $recipient->name)
                ->subject($campaign->subject);
        });
    }

    /**
     * Wrap campaign content with a professional email layout including unsubscribe link.
     */
    private function wrapWithLayout(EmailCampaign $campaign, EmailCampaignRecipient $recipient, ?string $unsubscribeUrl): string
    {
        $content = $campaign->content ?? '';
        $agencyName = $campaign->agency->name ?? config('app.name');
        $fromEmail = $campaign->from_email ?? config('mail.from.address');

        $unsubscribeHtml = $unsubscribeUrl
            ? '<div style="margin-top:30px;padding-top:20px;border-top:1px solid #e5e7eb;font-size:12px;color:#6b7280;text-align:center;">'
                .'You received this email because you are a contact of '.e($agencyName).'. '
                .'<a href="'.e($unsubscribeUrl).'" style="color:#6b7280;text-decoration:underline;">Unsubscribe</a>'
                .'</div>'
            : '';

        // Add tracking pixel for opens
        $pixel = $unsubscribeUrl
            ? app(TrackingService::class)->getTrackingPixel($recipient->id)
            : '';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            .'<body style="margin:0;padding:0;background-color:#f9fafb;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">'
            .'<div style="max-width:600px;margin:0 auto;padding:20px;">'
            .'<div style="background:#ffffff;border-radius:8px;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">'
            .$content
            .'</div>'
            .$unsubscribeHtml
            .'<div style="text-align:center;padding:16px;font-size:11px;color:#9ca3af;">'
            .'Sent by '.e($agencyName).' via DigitalMarketingSaaS'
            .'<br>Reply to: '.e($fromEmail)
            .'</div>'
            .$pixel
            .'</div></body></html>';
    }

    public function sendTest(string $to, string $subject, string $content): bool
    {
        try {
            Mail::html($content, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Test email failed: '.$e->getMessage());

            return false;
        }
    }

    public function verifyConnection(): array
    {
        try {
            $transport = Mail::getSymfonyTransport();
            $transport->start();

            return ['success' => true, 'message' => 'SMTP connection verified'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
