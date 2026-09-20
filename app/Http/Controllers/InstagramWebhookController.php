<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Webhooks\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookProcessor $processor,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('X-Hub-Signature-256');

        Log::info('Instagram webhook received', [
            'object' => $payload['object'] ?? 'unknown',
        ]);

        // Validate object type
        if (($payload['object'] ?? '') !== 'instagram') {
            return response()->json(['error' => 'Invalid object type'], 400);
        }

        // Process through WebhookProcessor
        $this->processor->process(
            platform: 'instagram',
            eventType: $payload['object'] ?? 'unknown',
            payload: $payload,
            signature: $signature,
            handler: function (array $payload) {
                $this->processPayload($payload);
            },
        );

        return response()->json(['success' => true]);
    }

    private function processPayload(array $payload): void
    {
        if (($payload['object'] ?? '') !== 'instagram') {
            return;
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $igUserId = $entry['id'] ?? null;
            $changes = $entry['changes'] ?? [];

            $account = SocialAccount::where('platform', 'instagram')
                ->where('platform_account_id', $igUserId)
                ->first();

            if (! $account) {
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
    }

    private function handleComment(SocialAccount $account, array $value): void
    {
        Log::info('Instagram comment webhook', [
            'account_id' => $account->id,
            'media_id' => $value['media_id'] ?? null,
            'comment_id' => $value['id'] ?? null,
        ]);

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

    private function handleMention(SocialAccount $account, array $value): void
    {
        Log::info('Instagram mention webhook', [
            'account_id' => $account->id,
            'media_id' => $value['media_id'] ?? null,
        ]);
    }

    private function handleStoryInsights(SocialAccount $account, array $value): void
    {
        Log::info('Instagram story insights webhook', [
            'account_id' => $account->id,
            'story_id' => $value['story_id'] ?? null,
        ]);
    }
}
