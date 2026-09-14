<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(private TelegramBotService $telegram)
    {
        // No auth middleware - Telegram calls this directly
    }

    /**
     * Handle incoming webhook from Telegram.
     */
    public function handle(Request $request): JsonResponse
    {
        $update = $request->all();

        // Log only safe metadata — never log message text or personal data
        Log::debug('Telegram webhook received', self::extractSafeMetadata($update));

        $this->telegram->handleWebhook($update);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Extract only non-sensitive metadata from a Telegram update.
     * Never includes message text, user personal data, or chat content.
     */
    private static function extractSafeMetadata(array $update): array
    {
        return [
            'update_id' => $update['update_id'] ?? null,
            'message_id' => $update['message']['message_id'] ?? null,
            'chat_id' => $update['message']['chat']['id'] ?? null,
            'has_callback' => isset($update['callback_query']),
        ];
    }

    /**
     * Set up the webhook URL.
     */
    public function setupWebhook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:500',
        ]);

        $result = $this->telegram->setWebhook($validated['url']);

        return response()->json($result);
    }

    /**
     * Get webhook info.
     */
    public function webhookInfo(): JsonResponse
    {
        return response()->json($this->telegram->getWebhookInfo());
    }
}
