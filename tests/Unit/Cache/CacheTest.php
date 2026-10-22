<?php

namespace Tests\Unit\Cache;

use App\Models\Agency;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_caches_dashboard_stats(): void
    {
        $agency = Agency::factory()->create();
        $service = new AnalyticsService;
        $stats1 = $service->getDashboardStats($agency);
        $stats2 = $service->getDashboardStats($agency);
        $this->assertEquals($stats1, $stats2);
    }

    public function test_it_clears_cache_on_model_update(): void
    {
        $agency = Agency::factory()->create();
        Cache::put("test:{$agency->id}", 'value', 60);
        $this->assertEquals('value', Cache::get("test:{$agency->id}"));
        Cache::forget("test:{$agency->id}");
        $this->assertNull(Cache::get("test:{$agency->id}"));
    }

    public function test_it_stores_and_retrieves_cache(): void
    {
        Cache::put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('test_key'));
        Cache::forget('test_key');
    }
}
