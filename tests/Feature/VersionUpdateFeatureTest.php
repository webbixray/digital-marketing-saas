<?php

namespace Tests\Feature;

use App\Jobs\CheckForUpdates;
use App\Models\FeatureFlag;
use App\Services\VersionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class VersionUpdateFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_version_service_can_compare_versions()
    {
        $service = $this->app->make(VersionService::class);
        $this->assertEquals(-1, $service->compareVersions('1.0.0', '2.0.0'));
        $this->assertEquals(0, $service->compareVersions('1.0.0', '1.0.0'));
        $this->assertEquals(1, $service->compareVersions('2.0.0', '1.0.0'));
    }

    public function test_version_service_gets_current_version()
    {
        $service = $this->app->make(VersionService::class);
        $this->assertEquals('1.0.0', $service->getVersion());
    }

    public function test_version_service_detects_update_available()
    {
        $service = $this->app->make(VersionService::class);
        $this->assertTrue($service->isUpdateAvailable('2.0.0'));
        $this->assertFalse($service->isUpdateAvailable('0.9.0'));
    }

    public function test_version_config_exists()
    {
        $this->assertNotNull(config('version.version'));
        $this->assertNotNull(config('version.codename'));
    }

    public function test_changelog_file_exists()
    {
        $this->assertFileExists(base_path('CHANGELOG.md'));
    }

    public function test_version_json_exists()
    {
        $this->assertFileExists(base_path('version.json'));
    }

    public function test_feature_flag_model_exists()
    {
        $this->assertTrue(class_exists(FeatureFlag::class));
    }

    public function test_check_for_updates_job_exists()
    {
        $this->assertTrue(class_exists(CheckForUpdates::class));
    }
}
