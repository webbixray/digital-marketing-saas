<?php

namespace Tests\Unit\Services;

use App\Models\Agency;
use App\Models\SocialPost;
use App\Services\SmartSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartSchedulingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SmartSchedulingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SmartSchedulingService();
    }

    public function test_get_optimal_times_returns_defaults_for_new_agency(): void
    {
        $agency = Agency::factory()->create();
        $times = $this->service->getOptimalTimes($agency->id, 'facebook');
        $this->assertEquals([9, 12, 15], $times);
    }

    public function test_get_optimal_times_returns_platform_defaults(): void
    {
        $agency = Agency::factory()->create();
        
        $platforms = [
            'facebook' => [9, 12, 15],
            'instagram' => [11, 14, 18],
            'twitter' => [8, 12, 17],
            'linkedin' => [8, 12, 17],
            'tiktok' => [12, 16, 20],
            'pinterest' => [14, 18, 21],
        ];

        foreach ($platforms as $platform => $expected) {
            $times = $this->service->getOptimalTimes($agency->id, $platform);
            $this->assertEquals($expected, $times);
        }
    }

    public function test_get_recommendation_returns_structure(): void
    {
        $agency = Agency::factory()->create();
        $recommendation = $this->service->getRecommendation($agency->id, 'facebook');

        $this->assertArrayHasKey('optimal_hours', $recommendation);
        $this->assertArrayHasKey('next_optimal_time', $recommendation);
        $this->assertArrayHasKey('platform', $recommendation);
        $this->assertEquals('facebook', $recommendation['platform']);
    }

    public function test_get_next_optimal_time_returns_future_time(): void
    {
        $agency = Agency::factory()->create();
        $time = $this->service->getNextOptimalTime($agency->id, 'facebook');

        $this->assertTrue($time->isFuture());
    }
}
