<?php

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\AI\AgencyAIAssistantService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private string $botToken;

    private string $apiBase = 'https://api.telegram.org/bot';

    public function __construct(private AgencyAIAssistantService $assistant)
    {
        $this->botToken = config('services.telegram.bot_token', '');
    }

    /**
     * Send a message to a Telegram chat.
     */
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function sendMessage(int|string $chatId, string $text, array $options = []): array
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ], $options);

        return $this->api('sendMessage', $payload);
    }

    /**
     * Send a message with inline keyboard.
     */
    /**
     * @param  array<int, array<int, array<string, string>>>  $buttons
     * @return array<string, mixed>
     */
    public function sendInlineKeyboard(int|string $chatId, string $text, array $buttons): array
    {
        return $this->api('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]);
    }

    /**
     * Send a message with reply keyboard.
     */
    /**
     * @param  array<int, array<int, array<string, string>>>  $buttons
     * @return array<string, mixed>
     */
    public function sendKeyboard(int|string $chatId, string $text, array $buttons): array
    {
        return $this->api('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'keyboard' => $buttons,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
            ]),
        ]);
    }

    /**
     * Remove keyboard.
     */
    /**
     * @return array<string, mixed>
     */
    public function removeKeyboard(int|string $chatId): array
    {
        return $this->api('sendMessage', [
            'chat_id' => $chatId,
            'text' => '...',
            'reply_markup' => json_encode(['remove_keyboard' => true]),
        ]);
    }

    /**
     * Process incoming webhook update.
     */
    public function handleWebhook(array $update): void
    {
        try {
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallback($update['callback_query']);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram webhook error: '.$e->getMessage());
        }
    }

    /**
     * Handle an incoming message.
     */
    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';

        if (! $chatId || empty($text)) {
            return;
        }

        // Find the user by Telegram chat ID
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (! $user) {
            $this->sendMessage($chatId, "⚠️ Your Telegram account is not linked to any agency.\n\nPlease link your account from the agency settings panel.");

            return;
        }

        $agency = $user->agency;

        if (! $agency) {
            $this->sendMessage($chatId, '⚠️ No agency found for your account.');

            return;
        }

        // Process through AI assistant
        $response = $this->assistant->processCommand($agency, $user, $text);

        $this->sendMessage($chatId, $response['content']);
    }

    /**
     * Handle a callback query (inline button press).
     */
    private function handleCallback(array $callback): void
    {
        $chatId = $callback['message']['chat']['id'] ?? null;
        $data = $callback['data'] ?? '';

        if (! $chatId || empty($data)) {
            return;
        }

        // Handle callback actions
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (! $user) {
            return;
        }

        // Process callback data...
        $this->sendMessage($chatId, "Action received: {$data}");
    }

    /**
     * Set the webhook URL.
     *
     * When a webhook secret is configured it is registered with Telegram so that
     * every incoming update carries the `X-Telegram-Bot-Api-Secret-Token` header,
     * which the webhook controller verifies.
     */
    /**
     * @return array<string, mixed>
     */
    public function setWebhook(string $url): array
    {
        $params = ['url' => $url];

        $secret = (string) config('services.telegram.webhook_secret', '');
        if ($secret !== '') {
            $params['secret_token'] = $secret;
        }

        return $this->api('setWebhook', $params);
    }

    /**
     * Get webhook info.
     */
    /**
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array
    {
        return $this->api('getWebhookInfo');
    }

    /**
     * Make an API call to Telegram.
     */
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function api(string $method, array $params = []): array
    {
        $url = $this->apiBase.$this->botToken.'/'.$method;

        try {
            $response = Http::timeout(30)->post($url, $params);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error("Telegram API error [{$method}]: ".$e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
