<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaignRecipient;
use App\Services\Email\EmailCampaignService;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function __construct()
    {
        // Public endpoint — no auth required
    }

    public function show(int $recipient)
    {
        $recipient = EmailCampaignRecipient::with('campaign')->find($recipient);

        if (! $recipient) {
            return view('email.unsubscribe', [
                'status' => 'invalid',
                'message' => 'Invalid unsubscribe link.',
            ]);
        }

        if ($recipient->status === EmailCampaignRecipient::STATUS_UNSUBSCRIBED) {
            return view('email.unsubscribe', [
                'status' => 'already',
                'message' => 'You are already unsubscribed from this mailing list.',
                'agencyName' => $recipient->campaign->agency->name ?? null,
            ]);
        }

        return view('email.unsubscribe', [
            'status' => 'pending',
            'recipientId' => $recipient->id,
            'agencyName' => $recipient->campaign->agency->name ?? 'this agency',
        ]);
    }

    public function confirm(Request $request, int $recipient, EmailCampaignService $service)
    {
        $recipient = EmailCampaignRecipient::with('campaign')->find($recipient);

        if (! $recipient) {
            return view('email.unsubscribe', [
                'status' => 'invalid',
                'message' => 'Invalid unsubscribe link.',
            ]);
        }

        if ($recipient->status !== EmailCampaignRecipient::STATUS_UNSUBSCRIBED) {
            $service->trackUnsubscribe($recipient);
        }

        return view('email.unsubscribe', [
            'status' => 'success',
            'message' => 'You have been successfully unsubscribed. You will no longer receive emails from this agency.',
            'agencyName' => $recipient->campaign->agency->name ?? null,
        ]);
    }
}
