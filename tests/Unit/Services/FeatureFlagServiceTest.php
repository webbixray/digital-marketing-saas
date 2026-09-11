<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Services\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeatureFlagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureFlagService::class);
    }

    public function test_is_enabled_returns_true_for_enterprise_plan(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'enterprise']);
        $this->assertTrue($this->service->isEnabled($agency, 'white_label'));
    }

    public function test_is_enabled_returns_true_for_feature_in_plan(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $this->assertTrue($this->service->isEnabled($agency, 'workflow_automation'));
    }

    public function test_is_enabled_returns_false_for_feature_not_in_plan(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'free']);
        $this->assertFalse($this->service->isEnabled($agency, 'analytics'));
    }
}
