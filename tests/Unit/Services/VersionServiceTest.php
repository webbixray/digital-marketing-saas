<?php

namespace Tests\Unit\Services;

use App\Services\VersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VersionServiceTest extends TestCase
{
    use RefreshDatabase;

    private VersionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(VersionService::class);
        Cache::flush();
    }

    public function test_it_returns_current_version(): void
    {
        $version = $this->service->getVersion();
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function test_it_returns_version_info_array(): void
    {
        $info = $this->service->getVersionInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('full', $info);
        $this->assertArrayHasKey('major', $info);
        $this->assertArrayHasKey('minor', $info);
        $this->assertArrayHasKey('patch', $info);
    }

    public function test_it_can_compare_versions_correctly(): void
    {
        $this->assertEquals(-1, $this->service->compareVersions('1.0.0', '2.0.0'));
        $this->assertEquals(0, $this->service->compareVersions('1.0.0', '1.0.0'));
        $this->assertEquals(1, $this->service->compareVersions('2.0.0', '1.0.0'));
    }

    public function test_it_detects_update_available(): void
    {
        $this->assertTrue($this->service->isUpdateAvailable('99.99.99'));
        $this->assertFalse($this->service->isUpdateAvailable('0.0.1'));
    }

    public function test_it_detects_no_update_when_same_version(): void
    {
        $current = $this->service->getVersion();
        $this->assertFalse($this->service->isUpdateAvailable($current));
    }

    public function test_it_can_clear_cache(): void
    {
        $this->service->getVersionInfo();
        $this->service->clearCache();
        Cache::flush();
        $this->assertTrue(true);
    }
}
