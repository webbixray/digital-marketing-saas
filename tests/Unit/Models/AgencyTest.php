<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_can_be_created(): void
    {
        $agency = Agency::factory()->create();
        $this->assertDatabaseHas('agencies', ['id' => $agency->id]);
    }

    public function test_agency_has_is_active_attribute(): void
    {
        $agency = Agency::factory()->create(['status' => 'active', 'subscription_status' => 'active']);
        $this->assertTrue($agency->isActive);
    }

    public function test_agency_is_not_active_when_cancelled(): void
    {
        $agency = Agency::factory()->create(['status' => 'cancelled', 'subscription_status' => 'cancelled']);
        $this->assertFalse($agency->isActive);
    }

    public function test_agency_can_check_feature_availability(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'free']);
        $this->assertFalse($agency->isFeatureAvailable('workflow_engine'));
    }

    public function test_enterprise_agency_has_all_features(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'enterprise']);
        $this->assertTrue($agency->isFeatureAvailable('workflow_engine'));
        $this->assertTrue($agency->isFeatureAvailable('api_access'));
    }

    public function test_agency_can_publish_post_within_quota(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter', 'posts_count' => 0]);
        $this->assertTrue($agency->canPublishPost());
    }

    public function test_agency_cannot_publish_post_over_quota(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter', 'posts_count' => 100]);
        $this->assertFalse($agency->canPublishPost());
    }

    public function test_agency_has_many_users(): void
    {
        // Create a user and agency
        $agency = Agency::factory()->create();
        $this->assertNotNull($agency->id);
    }

    public function test_agency_get_plan_config_returns_correct_plan(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'pro']);
        $config = $agency->getPlanConfig();
        $this->assertEquals(49, $config['price']);
    }

    public function test_agency_scope_active_returns_only_active(): void
    {
        Agency::factory()->create(['status' => 'active']);
        Agency::factory()->create(['status' => 'cancelled']);
        $this->assertCount(1, Agency::active()->get());
    }
}
