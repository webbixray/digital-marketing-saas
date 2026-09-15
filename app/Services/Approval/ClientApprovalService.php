<?php

namespace App\Services\Approval;

use App\Models\Client;
use App\Models\SocialPost;
use App\Notifications\PostApprovedNotification;
use App\Notifications\PostRejectedNotification;
use App\Notifications\PostSubmittedForApprovalNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ClientApprovalService
{
    public function submitForApproval(SocialPost $post, int $clientId): void
    {
        $post->update([
            'approval_status' => 'pending',
            'client_id' => $clientId,
        ]);

        $client = Client::find($clientId);
        if ($client && $client->email) {
            Notification::route('mail', $client->email)
                ->notify(new PostSubmittedForApprovalNotification($post));
        }

        Log::info('Post submitted for approval', [
            'post_id' => $post->id,
            'client_id' => $clientId,
        ]);
    }

    public function approve(SocialPost $post, int $approvedBy, ?string $notes = null): void
    {
        $post->update([
            'approval_status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        $this->notifyAgencyUsers($post, new PostApprovedNotification($post));

        Log::info('Post approved', [
            'post_id' => $post->id,
            'approved_by' => $approvedBy,
        ]);
    }

    public function reject(SocialPost $post, int $rejectedBy, string $feedback): void
    {
        $post->update([
            'approval_status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'approval_notes' => $feedback,
        ]);

        $this->notifyAgencyUsers($post, new PostRejectedNotification($post, $feedback));

        Log::info('Post rejected', [
            'post_id' => $post->id,
            'rejected_by' => $rejectedBy,
        ]);
    }

    public function isPending(SocialPost $post): bool
    {
        return $post->approval_status === 'pending';
    }

    public function isApproved(SocialPost $post): bool
    {
        return $post->approval_status === 'approved';
    }

    public function isRejected(SocialPost $post): bool
    {
        return $post->approval_status === 'rejected';
    }

    private function notifyAgencyUsers(SocialPost $post, $notification): void
    {
        $agency = $post->agency;
        if ($agency) {
            $users = $agency->users()->whereIn('role', ['owner', 'admin'])->get();
            Notification::send($users, $notification);
        }
    }
}
