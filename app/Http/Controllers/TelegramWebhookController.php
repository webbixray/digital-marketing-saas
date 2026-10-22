<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use App\Services\Webhooks\WebhookProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramBotService $telegram,
        private readonly WebhookProcessor $processor,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        // Verify the Telegram webhook secret token (fail closed when configured).
        // Without this, anyone could POST forged updates and drive the bot.
        $secret = (string) config('services.telegram.webhook_secret', '');
        if ($secret !== '') {
            $provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
            if (! hash_equals($secret, $provided)) {
                Log::warning('Telegram webhook rejected: invalid secret token');

                abort(403, 'Invalid webhook signature.');
            }
        }

        $update = $request->all();

        Log::debug('Telegram webhook received', self::extractSafeMetadata($update));

        // Process through WebhookProcessor
        $this->processor->process(
            platform: 'telegram',
            eventType: 'update',
            payload: $update,
            signature: $request->header('X-Telegram-Bot-Api-Secret-Token'),
            handler: function (array $update) {
                $this->telegram->handleWebhook($update);
            },
        );

        return response()->json(['status' => 'ok']);
    }

    private static function extractSafeMetadata(array $update): array
    {
        return [
            'update_id' => $update['update_id'] ?? null,
            'message_id' => $update['message']['message_id'] ?? null,
            'chat_id' => $update['message']['chat']['id'] ?? null,
            'has_callback' => isset($update['callback_query']),
        ];
    }

    public function setupWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
        ]);

        $result = $this->telegram->setWebhook($validated['url']);

        return response()->json($result);
    }

    public function webhookInfo(): JsonResponse
    {
        return response()->json($this->telegram->getWebhookInfo());
    }
}
