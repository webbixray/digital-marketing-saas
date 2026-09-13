<?php

namespace App\Http\Controllers;

use App\Models\InboxMessage;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookWebhookController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.facebook.webhook_verify_token')) {
            Log::info('Facebook webhook verified');
            return response()->json((int) $challenge);
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Facebook webhook received', [
            'object' => $payload['object'] ?? 'unknown',
        ]);

        if (($payload['object'] ?? '') !== 'page') {
            return response()->json(['error' => 'Invalid webhook object'], 400);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            $account = SocialAccount::where('platform', 'facebook')
                ->where('platform_account_id', $pageId)
                ->first();

            if (!$account) {
                Log::warning('Facebook webhook: account not found', ['page_id' => $pageId]);
                continue;
            }

            foreach ($changes as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                match ($field) {
                    'feed' => $this->handleFeed($account, $value),
                    'comments' => $this->handleComment($account, $value),
                    'mentions' => $this->handleMention($account, $value),
                    'ratings' => $this->handleRating($account, $value),
                    default => Log::info("Unhandled Facebook webhook field: {$field}"),
                };
            }
        }

        return response()->json(['success' => true]);
    }

    private function handleFeed(SocialAccount $account, array $value): void
    {
        Log::info('Facebook feed webhook', ['item' => $value['item'] ?? null]);

        if (($value['item'] ?? '') === 'comment') {
            InboxMessage::create([
                'agency_id' => $account->agency_id,
                'social_account_id' => $account->id,
                'platform' => 'facebook',
                'platform_message_id' => $value['comment_id'] ?? null,
                'message_type' => 'comment',
                'author_name' => $value['from']['name'] ?? 'Unknown',
                'author_id' => $value['from']['id'] ?? null,
                'content' => $value['message'] ?? null,
                'status' => 'unread',
                'received_at' => now(),
            ]);
        }
    }

    private function handleComment(SocialAccount $account, array $value): void
    {
        InboxMessage::create([
            'agency_id' => $account->agency_id,
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'platform_message_id' => $value['id'] ?? null,
            'message_type' => 'comment',
            'author_name' => $value['from']['name'] ?? 'Unknown',
            'author_id' => $value['from']['id'] ?? null,
            'content' => $value['message'] ?? null,
            'status' => 'unread',
            'received_at' => now(),
        ]);
    }

    private function handleMention(SocialAccount $account, array $value): void
    {
        InboxMessage::create([
            'agency_id' => $account->agency_id,
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'platform_message_id' => $value['post_id'] ?? null,
            'message_type' => 'mention',
            'author_name' => $value['sender_name'] ?? 'Unknown',
            'content' => $value['message'] ?? null,
            'status' => 'unread',
            'received_at' => now(),
        ]);
    }

    private function handleRating(SocialAccount $account, array $value): void
    {
        Log::info('Facebook rating webhook', ['rating' => $value['rating'] ?? null]);
    }
}
