<?php

namespace Tests\Feature\Telegram;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Telegram webhook is public (no auth) — it must verify the secret token so
 * that forged updates cannot drive the bot.
 */
class TelegramWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_request_without_secret_when_configured(): void
    {
        config(['services.telegram.webhook_secret' => 'super-secret-token']);

        $response = $this->postJson('/telegram/webhook', ['update_id' => 1]);

        $response->assertForbidden();
    }

    public function test_webhook_rejects_request_with_wrong_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'super-secret-token']);

        $response = $this->postJson(
            '/telegram/webhook',
            ['update_id' => 1],
            ['X-Telegram-Bot-Api-Secret-Token' => 'wrong-token'],
        );

        $response->assertForbidden();
    }

    public function test_webhook_accepts_request_with_correct_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'super-secret-token']);

        $response = $this->postJson(
            '/telegram/webhook',
            ['update_id' => 1],
            ['X-Telegram-Bot-Api-Secret-Token' => 'super-secret-token'],
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_setwebhook_registers_secret_token_when_configured(): void
    {
        config([
            'services.telegram.bot_token' => 'bot-token',
            'services.telegram.webhook_secret' => 'super-secret-token',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);

        app(TelegramBotService::class)->setWebhook('https://example.com/telegram/webhook');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'setWebhook')
                && ($request['secret_token'] ?? null) === 'super-secret-token';
        });
    }
}
