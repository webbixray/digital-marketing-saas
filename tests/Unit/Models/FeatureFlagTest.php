<?php

namespace Tests\Unit\Models;

use App\Models\FeatureFlag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_flag_can_be_created(): void
    {
        $flag = FeatureFlag::create([
            'feature_key' => 'test_feature',
            'feature_name' => 'Test Feature',
            'enabled' => true,
            'required_plan' => 1,
        ]);

        $this->assertDatabaseHas('feature_flags', [
            'feature_key' => 'test_feature',
            'enabled' => 1,
        ]);
    }

    public function test_enabled_casts_to_boolean(): void
    {
        $flag = FeatureFlag::create([
            'feature_key' => 'test_feature',
            'feature_name' => 'Test Feature',
            'enabled' => 'true',
        ]);

        $this->assertIsBool($flag->enabled);
        $this->assertTrue($flag->enabled);
    }

    public function test_scope_enabled_returns_only_enabled(): void
    {
        FeatureFlag::create([
            'feature_key' => 'enabled_feature',
            'feature_name' => 'Enabled',
            'enabled' => true,
        ]);
        FeatureFlag::create([
            'feature_key' => 'disabled_feature',
            'feature_name' => 'Disabled',
            'enabled' => false,
        ]);

        $enabled = FeatureFlag::enabled()->get();
        $this->assertCount(1, $enabled);
        $this->assertEquals('enabled_feature', $enabled->first()->feature_key);
    }
}
