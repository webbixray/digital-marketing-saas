<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Webhooks\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YouTubeWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookProcessor $processor,
    ) {}

    public function verify(Request $request): JsonResponse
    {
        $challenge = $request->get('hub_challenge');
        if ($challenge) {
            return response()->json($challenge);
        }

        return response()->json(['error' => 'Verification failed'], 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $xml = simplexml_load_string($payload);

        if (! $xml) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Normalize XML to array for processing
        $parsedPayload = json_decode(json_encode($xml), true);

        Log::info('YouTube webhook received', ['xml' => $payload]);

        // Process through WebhookProcessor
        $this->processor->process(
            platform: 'youtube',
            eventType: 'video.update',
            payload: $parsedPayload,
            signature: null,
            handler: function (array $parsedPayload) use ($xml) {
                $videoId = (string) ($xml->entry->id ?? '');
                $channelId = (string) ($xml->entry->author->uri ?? '');

                if ($videoId && $channelId) {
                    $this->handleVideoUpdate($channelId, $videoId);
                }
            },
        );

        return response()->json(['success' => true]);
    }

    private function handleVideoUpdate(string $channelId, string $videoId): void
    {
        $account = SocialAccount::where('platform', 'youtube')
            ->where('platform_account_id', $channelId)
            ->first();

        if (! $account) {
            Log::warning('YouTube webhook: account not found', ['channel_id' => $channelId]);
            return;
        }

        Log::info('YouTube video update', [
            'account_id' => $account->id,
            'video_id' => $videoId,
        ]);
    }
}
