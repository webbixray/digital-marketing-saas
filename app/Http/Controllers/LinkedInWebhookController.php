<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Webhooks\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LinkedInWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookProcessor $processor,
    ) {}

    public function verify(Request $request): JsonResponse
    {
        $challenge = $request->get('challengeCode');
        if ($challenge) {
            return response()->json(['challengeCode' => $challenge]);
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('X-LinkedIn-Signature');

        Log::info('LinkedIn webhook received');

        // Process through WebhookProcessor
        $this->processor->process(
            platform: 'linkedin',
            eventType: $payload['events'][0]['type'] ?? 'unknown',
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
        foreach ($payload['events'] ?? [] as $event) {
            $this->handleEvent($event);
        }
    }

    private function handleEvent(array $event): void
    {
        $type = $event['type'] ?? '';
        $actor = $event['actor'] ?? '';

        $account = SocialAccount::where('platform', 'linkedin')
            ->where('platform_account_id', $actor)
            ->first();

        if (! $account) {
            return;
        }

        match ($type) {
            'COMMENT_CREATED', 'COMMENT_UPDATED' => $account->inboxMessages()->create([
                'platform' => 'linkedin',
                'message_type' => 'comment',
                'author_name' => $event['authorName'] ?? 'Unknown',
                'content' => $event['commentText'] ?? null,
                'status' => 'unread',
                'received_at' => now(),
            ]),
            'SOCIAL_ACTION' => $this->handleSocialAction($account, $event),
            default => Log::info("Unhandled LinkedIn webhook type: {$type}"),
        };
    }

    private function handleSocialAction(SocialAccount $account, array $event): void
    {
        Log::info('LinkedIn social action webhook', $event);
    }
}
