<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_remaining_campaigns_returns_integer(): void
    {
        $agency = Agency::factory()->create();
        $service = new QuotaService;

        $remaining = $service->remainingCampaigns($agency);
        $this->assertIsInt($remaining);
    }

    public function test_remaining_posts_returns_integer(): void
    {
        $agency = Agency::factory()->create();
        $service = new QuotaService;

        $remaining = $service->remainingPosts($agency);
        $this->assertIsInt($remaining);
    }

    public function test_enterprise_has_unlimited_quota(): void
    {
        $agency = Agency::factory()->enterprise()->create();
        $service = new QuotaService;

        $limit = $service->getLimit($agency, 'campaigns');
        $this->assertEquals(-1, $limit);
    }

    public function test_free_plan_has_one_quota(): void
    {
        $agency = Agency::factory()->free()->create();
        $service = new QuotaService;

        $remaining = $service->remainingCampaigns($agency);
        $this->assertEquals(1, $remaining);
    }

    public function test_starter_plan_has_higher_limit_than_free(): void
    {
        $freeAgency = Agency::factory()->free()->create();
        $starterAgency = Agency::factory()->create();
        $service = new QuotaService;

        $freeLimit = $service->getLimit($freeAgency, 'campaigns');
        $starterLimit = $service->getLimit($starterAgency, 'campaigns');

        $this->assertGreaterThan($freeLimit, $starterLimit);
    }

    public function test_usage_percentage_returns_float(): void
    {
        $agency = Agency::factory()->create();
        $service = new QuotaService;

        $percentage = $service->usagePercentage($agency, 'campaigns');
        $this->assertIsFloat($percentage);
        $this->assertGreaterThanOrEqual(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);
    }

    public function test_can_publish_post_returns_false_when_quota_zero(): void
    {
        $agency = Agency::factory()->free()->create(['posts_count' => 100]);
        $service = new QuotaService;

        $canPublish = $service->canPublishPost($agency);
        $this->assertFalse($canPublish);
    }

    public function test_is_over_quota_returns_boolean(): void
    {
        $agency = Agency::factory()->create();
        $service = new QuotaService;

        $result = $service->isOverQuota($agency, 'campaigns');
        $this->assertIsBool($result);
    }

    public function test_get_quota_status_returns_array(): void
    {
        $agency = Agency::factory()->create();
        $service = new QuotaService;

        $status = $service->getQuotaStatus($agency);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('posts', $status);
        $this->assertArrayHasKey('campaigns', $status);
        $this->assertArrayHasKey('clients', $status);
    }

    public function test_increment_post_count_increments(): void
    {
        $agency = Agency::factory()->create(['posts_count' => 5]);
        $service = new QuotaService;

        $service->incrementPostCount($agency);

        $agency->refresh();
        $this->assertEquals(6, $agency->posts_count);
    }

    public function test_decrement_post_count_decrements(): void
    {
        $agency = Agency::factory()->create(['posts_count' => 5]);
        $service = new QuotaService;

        $service->decrementPostCount($agency);

        $agency->refresh();
        $this->assertEquals(4, $agency->posts_count);
    }
}
