<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    /**
     * Handle Instagram webhook notifications (real-time updates).
     * Instagram sends webhooks for comments, mentions, and story insights.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Instagram webhook received', [
            'object' => $payload['object'] ?? 'unknown',
            'entry' => $payload['entry'] ?? [],
        ]);

        if (($payload['object'] ?? '') !== 'instagram') {
            return response()->json(['error' => 'Invalid webhook object'], 400);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $igUserId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            // Find the social account
            $account = SocialAccount::where('platform', 'instagram')
                ->where('platform_account_id', $igUserId)
                ->first();

            if (!$account) {
                Log::warning('Instagram webhook: account not found', [
                    'ig_user_id' => $igUserId,
                ]);
                continue;
            }

            foreach ($changes as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                match ($field) {
                    'comments' => $this->handleComment($account, $value),
                    'mentions' => $this->handleMention($account, $value),
                    'story_insights' => $this->handleStoryInsights($account, $value),
                    default => Log::info("Unhandled Instagram webhook field: {$field}"),
                };
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle comment webhook.
     */
    private function handleComment(SocialAccount $account, array $value): void
    {
        Log::info('Instagram comment webhook', [
            'account_id' => $account->id,
            'media_id' => $value['media_id'] ?? null,
            'comment_id' => $value['id'] ?? null,
            'text' => $value['text'] ?? null,
        ]);

        // Create inbox message for the comment
        if (isset($value['text'])) {
            $account->inboxMessages()->create([
                'agency_id' => $account->agency_id,
                'platform' => 'instagram',
                'platform_message_id' => $value['id'] ?? null,
                'author_name' => $value['from']['username'] ?? 'Unknown',
                'content' => $value['text'],
                'status' => 'unread',
            ]);
        }
    }

    /**
     * Handle mention webhook.
     */
    private function handleMention(SocialAccount $account, array $value): void
    {
        Log::info('Instagram mention webhook', [
            'account_id' => $account->id,
            'media_id' => $value['media_id'] ?? null,
            'mention' => $value,
        ]);
    }

    /**
     * Handle story insights webhook.
     */
    private function handleStoryInsights(SocialAccount $account, array $value): void
    {
        Log::info('Instagram story insights webhook', [
            'account_id' => $account->id,
            'story_id' => $value['story_id'] ?? null,
        ]);
    }
}
