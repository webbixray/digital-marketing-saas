<?php

namespace Tests\Feature\Version;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_version_info(): void
    {
        $response = $this->getJson('/api/version');
        $response->assertOk();
        $response->assertJsonStructure(['version', 'latest_release', 'api_versions']);
    }

    public function test_it_checks_for_updates(): void
    {
        $response = $this->getJson('/api/version/check?remote=1.0.0');
        $response->assertOk();
        $response->assertJsonStructure(['current', 'remote', 'update_available']);
    }

    public function test_it_requires_remote_version_for_update_check(): void
    {
        $response = $this->getJson('/api/version/check');
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Remote version required']);
    }

    public function test_it_returns_health_status(): void
    {
        $response = $this->getJson('/health');
        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }
}
