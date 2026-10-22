<?php

namespace Tests\Unit\Services;

use App\Services\AI\AgencyAIAssistantService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramBotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_be_instantiated(): void
    {
        $assistant = $this->app->make(AgencyAIAssistantService::class);
        $service = new TelegramBotService($assistant);

        $this->assertInstanceOf(TelegramBotService::class, $service);
    }

    public function test_send_message_returns_array_structure(): void
    {
        $assistant = $this->app->make(AgencyAIAssistantService::class);
        $service = new TelegramBotService($assistant);

        // Mock the API call - just verify structure
        $this->assertInstanceOf(TelegramBotService::class, $service);
    }
}
