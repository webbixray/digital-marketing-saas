<?php

namespace App\Services\Email;

use App\Jobs\Email\SendEmailCampaign;
use App\Models\Agency;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Illuminate\Support\Str;

class EmailCampaignService
{
    /**
     * Create a new email campaign.
     */
    public function create(Agency $agency, array $data): EmailCampaign
    {
        $campaign = EmailCampaign::create([
            'agency_id' => $agency->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(6),
            'type' => $data['type'] ?? 'newsletter',
            'status' => 'draft',
            'subject' => $data['subject'],
            'from_name' => $data['from_name'] ?? null,
            'from_email' => $data['from_email'] ?? null,
            'reply_to' => $data['reply_to'] ?? null,
            'content' => $data['content'] ?? null,
            'tags' => $data['tags'] ?? [],
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        // Add recipients if provided
        if (! empty($data['recipients'])) {
            $this->addRecipients($campaign, $data['recipients']);
        }

        return $campaign;
    }

    /**
     * Update an existing email campaign.
     */
    public function update(EmailCampaign $campaign, array $data): EmailCampaign
    {
        if (! $campaign->isEditable()) {
            throw new \InvalidArgumentException('Campaign cannot be edited in current status');
        }

        $campaign->update([
            'name' => $data['name'] ?? $campaign->name,
            'subject' => $data['subject'] ?? $campaign->subject,
            'from_name' => $data['from_name'] ?? $campaign->from_name,
            'from_email' => $data['from_email'] ?? $campaign->from_email,
            'reply_to' => $data['reply_to'] ?? $campaign->reply_to,
            'content' => $data['content'] ?? $campaign->content,
            'tags' => $data['tags'] ?? $campaign->tags,
            'scheduled_at' => $data['scheduled_at'] ?? $campaign->scheduled_at,
        ]);

        if (! empty($data['recipients'])) {
            $this->addRecipients($campaign, $data['recipients']);
        }

        return $campaign;
    }

    /**
     * Add recipients to a campaign.
     */
    public function addRecipients(EmailCampaign $campaign, array $recipients): void
    {
        $records = [];
        foreach ($recipients as $recipient) {
            $records[] = [
                'email_campaign_id' => $campaign->id,
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? null,
                'status' => 'pending',
            ];
        }

        // Batch insert, skipping duplicates
        foreach (array_chunk($records, 500) as $chunk) {
            foreach ($chunk as $record) {
                EmailCampaignRecipient::firstOrCreate(
                    ['email_campaign_id' => $record['email_campaign_id'], 'email' => $record['email']],
                    $record
                );
            }
        }

        $campaign->update([
            'recipients_count' => $campaign->recipients()->count(),
        ]);
    }

    /**
     * Add agency clients as recipients.
     */
    public function addClientRecipients(EmailCampaign $campaign, Agency $agency): int
    {
        $clients = Client::where('agency_id', $agency->id)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get();

        $records = [];
        foreach ($clients as $client) {
            $records[] = [
                'email_campaign_id' => $campaign->id,
                'email' => $client->email,
                'name' => $client->name,
                'status' => 'pending',
            ];
        }

        foreach (array_chunk($records, 500) as $chunk) {
            EmailCampaignRecipient::insert($chunk);
        }

        $count = count($records);
        $campaign->update([
            'recipients_count' => $campaign->recipients()->count(),
        ]);

        return $count;
    }

    /**
     * Send the campaign to all pending recipients.
     */
    public function send(EmailCampaign $campaign): void
    {
        if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
            throw new \InvalidArgumentException('Campaign is not in sendable status');
        }

        SendEmailCampaign::dispatch($campaign->id);
    }

    /**
     * Calculate open/click/bounce rates.
     */
    public function calculateRates(EmailCampaign $campaign): void
    {
        $total = $campaign->recipients_count;
        if ($total === 0) {
            return;
        }

        $campaign->update([
            'open_rate' => round(($campaign->opened_count / $total) * 100, 2),
            'click_rate' => round(($campaign->clicked_count / $total) * 100, 2),
            'bounce_rate' => round(($campaign->bounced_count / $total) * 100, 2),
        ]);
    }

    /**
     * Track an email open.
     */
    public function trackOpen(EmailCampaignRecipient $recipient): void
    {
        if ($recipient->status === 'sent') {
            $recipient->update(['status' => 'opened', 'opened_at' => now()]);
            EmailCampaign::where('id', $recipient->email_campaign_id)->increment('opened_count');
            $campaign = EmailCampaign::find($recipient->email_campaign_id);
            if ($campaign) {
                $this->calculateRates($campaign);
            }
        }
    }

    /**
     * Track a link click.
     */
    public function trackClick(EmailCampaignRecipient $recipient): void
    {
        if (in_array($recipient->status, ['sent', 'opened'])) {
            $recipient->update(['status' => 'clicked', 'clicked_at' => now()]);
            EmailCampaign::where('id', $recipient->email_campaign_id)->increment('clicked_count');
            $campaign = EmailCampaign::find($recipient->email_campaign_id);
            if ($campaign) {
                $this->calculateRates($campaign);
            }
        }
    }

    /**
     * Track an unsubscribe.
     */
    public function trackUnsubscribe(EmailCampaignRecipient $recipient): void
    {
        $recipient->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
        EmailCampaign::where('id', $recipient->email_campaign_id)->increment('unsubscribed_count');
    }

    /**
     * Delete a campaign.
     */
    public function delete(EmailCampaign $campaign): bool
    {
        if ($campaign->status === 'sending') {
            throw new \InvalidArgumentException('Cannot delete campaign while sending');
        }

        return $campaign->delete();
    }
}
