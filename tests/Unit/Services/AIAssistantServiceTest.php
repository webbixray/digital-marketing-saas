<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\User;
use App\Services\AI\AgencyAIAssistantService;
use App\Services\AI\AiContentService;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIAssistantServiceTest extends TestCase
{
    use RefreshDatabase;

    private AgencyAIAssistantService $service;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $quota = $this->app->make(QuotaService::class);
        $aiContent = $this->app->make(AiContentService::class);
        $this->service = new AgencyAIAssistantService($quota, $aiContent);

        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    public function test_it_can_process_greeting_command(): void
    {
        $result = $this->service->processCommand($this->agency, $this->user, 'Hello');

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('text', $result['type']);
        $this->assertArrayHasKey('content', $result);
    }

    public function test_it_can_process_help_command(): void
    {
        $result = $this->service->processCommand($this->agency, $this->user, 'help');

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('text', $result['type']);
        $this->assertStringContainsString('commands', strtolower($result['content']));
    }

    public function test_it_can_process_quota_status_command(): void
    {
        $result = $this->service->processCommand($this->agency, $this->user, 'quota status');

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('text', $result['type']);
    }

    public function test_it_returns_fallback_for_unknown_commands(): void
    {
        $result = $this->service->processCommand($this->agency, $this->user, 'xyzzy123unknown');

        $this->assertArrayHasKey('type', $result);
        $this->assertEquals('text', $result['type']);
    }

    public function test_greeting_contains_user_name(): void
    {
        $result = $this->service->processCommand($this->agency, $this->user, 'hi');
        $this->assertStringContainsString($this->user->name, $result['content']);
    }
}
