<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Webhooks\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TikTokWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookProcessor $processor,
    ) {}

    public function verify(Request $request): JsonResponse
    {
        $challenge = $request->get('challenge');
        if ($challenge) {
            return response()->json(['challenge' => $challenge]);
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('X-TikTok-Signature');

        Log::info('TikTok webhook received');

        // Verify TikTok signature if available
        if ($signature && ! $this->processor->verifyTikTokSignature($request->getContent(), $signature)) {
            Log::warning('TikTok webhook: invalid signature');
        }

        // Process through WebhookProcessor
        $this->processor->process(
            platform: 'tiktok',
            eventType: $payload['data']['events'][0]['event'] ?? 'unknown',
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
        foreach ($payload['data']['events'] ?? [] as $event) {
            $this->handleEvent($event);
        }
    }

    private function handleEvent(array $event): void
    {
        $eventType = $event['event'] ?? '';

        match ($eventType) {
            'video.comment.create' => $this->handleComment($event),
            'video.mention.create' => $this->handleMention($event),
            'video.dm.receive' => $this->handleDirectMessage($event),
            default => Log::info("Unhandled TikTok webhook event: {$eventType}"),
        };
    }

    private function handleComment(array $event): void
    {
        $account = $this->getAccount($event['video_id'] ?? '');
        if (! $account) {
            return;
        }

        $account->inboxMessages()->create([
            'platform' => 'tiktok',
            'message_type' => 'comment',
            'author_name' => $event['comment']['user']['display_name'] ?? 'Unknown',
            'content' => $event['comment']['text'] ?? null,
            'status' => 'unread',
            'received_at' => now(),
        ]);
    }

    private function handleMention(array $event): void
    {
        $account = $this->getAccount($event['video_id'] ?? '');
        if (! $account) {
            return;
        }

        $account->inboxMessages()->create([
            'platform' => 'tiktok',
            'message_type' => 'mention',
            'author_name' => $event['mention']['user']['display_name'] ?? 'Unknown',
            'content' => $event['mention']['text'] ?? null,
            'status' => 'unread',
            'received_at' => now(),
        ]);
    }

    private function handleDirectMessage(array $event): void
    {
        $account = $this->getAccount($event['video_id'] ?? '');
        if (! $account) {
            return;
        }

        $account->inboxMessages()->create([
            'platform' => 'tiktok',
            'message_type' => 'dm',
            'author_name' => $event['dm']['user']['display_name'] ?? 'Unknown',
            'content' => $event['dm']['text'] ?? null,
            'status' => 'unread',
            'received_at' => now(),
        ]);
    }

    private function getAccount(string $videoId): ?SocialAccount
    {
        return SocialAccount::where('platform', 'tiktok')
            ->whereJsonContains('metadata->last_video_id', $videoId)
            ->first();
    }
}
