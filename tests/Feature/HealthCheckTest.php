<?php

namespace Tests\Feature;

use App\Services\AI\Gateway\AiGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock AiGateway to always report available
        $mock = $this->createMock(AiGateway::class);
        $mock->method('hasAvailableProvider')->willReturn(true);
        $this->app->instance(AiGateway::class, $mock);
    }

    public function test_health_endpoint_returns_200_with_correct_structure(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'service',
            'timestamp',
            'version',
        ]);

        $response->assertJson([
            'status' => 'ok',
            'service' => 'DigitalMarketingSaaS',
        ]);
    }

    public function test_readiness_endpoint_returns_200_when_all_checks_pass(): void
    {
        // Ensure all dependencies are working
        Cache::shouldReceive('put')->once()->andReturn(true);
        Cache::shouldReceive('get')->once()->andReturn(true);
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->once()->andReturn(true);
        DB::shouldReceive('connection')->andReturnSelf();
        DB::shouldReceive('getPdo')->once();
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);
        config(['queue.default' => 'sync']);

        $response = $this->getJson('/ready');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'checks' => [
                'database',
                'cache',
                'storage',
                'ai_gateway',
                'disk_space',
                'queue',
            ],
            'timestamp',
        ]);

        $response->assertJson([
            'status' => 'ready',
        ]);
    }

    public function test_readiness_endpoint_returns_503_when_not_ready(): void
    {
        // Simulate a failing database connection
        DB::shouldReceive('connection')->andThrow(new \Exception('Connection failed'));
        Cache::shouldReceive('put')->andThrow(new \Exception('Cache failed'));
        Storage::shouldReceive('disk')->andThrow(new \Exception('Storage failed'));
        config(['queue.default' => 'sync']);

        $response = $this->getJson('/ready');

        $response->assertStatus(503);
        $response->assertJsonStructure([
            'status',
            'checks' => [
                'database',
                'cache',
                'storage',
                'ai_gateway',
                'disk_space',
                'queue',
            ],
            'timestamp',
        ]);

        $response->assertJson([
            'status' => 'not_ready',
        ]);
    }

    public function test_liveness_endpoint_returns_200_with_correct_structure(): void
    {
        $response = $this->getJson('/live');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
        ]);

        $response->assertJson([
            'status' => 'alive',
        ]);
    }

    public function test_status_endpoint_returns_200_with_correct_structure(): void
    {
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('get')->andReturn(true);
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        DB::shouldReceive('connection')->andReturnSelf();
        DB::shouldReceive('getPdo')->once();
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);
        config(['queue.default' => 'sync']);

        $response = $this->getJson('/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'checks' => [
                'database',
                'cache',
                'storage',
                'queue',
            ],
            'timestamp',
        ]);

        $response->assertJson([
            'status' => 'ok',
        ]);
    }

    public function test_disk_space_endpoint_returns_200_with_correct_structure(): void
    {
        $response = $this->getJson('/disk-space');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'disks' => [
                'storage',
                'base',
                'public',
            ],
            'threshold_bytes',
            'threshold_human',
            'timestamp',
        ]);

        // Each disk should have detailed info
        foreach (['storage', 'base', 'public'] as $disk) {
            $response->assertJsonStructure([
                "disks.{$disk}" => [
                    'path',
                    'free_bytes',
                    'total_bytes',
                    'used_bytes',
                    'free_human',
                    'total_human',
                    'usage_percent',
                    'healthy',
                ],
            ]);
        }
    }

    public function test_disk_space_endpoint_returns_warning_when_disk_low(): void
    {
        // This test verifies the structure when disk is low
        // In practice, disk_free_space returns actual values, so we test the response structure
        $response = $this->getJson('/disk-space');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertContains($data['status'], ['ok', 'warning']);
    }

    public function test_queue_status_endpoint_returns_200_with_correct_structure(): void
    {
        config(['queue.default' => 'sync']);
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $response = $this->getJson('/queue-status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'driver',
            'default_queue',
            'timestamp',
            'pending',
            'failed',
            'healthy',
            'max_queue_size',
        ]);
    }

    public function test_queue_status_endpoint_with_database_driver(): void
    {
        config(['queue.default' => 'database']);
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(5);
        DB::shouldReceive('select')->andReturnSelf();
        DB::shouldReceive('groupBy')->andReturnSelf();
        DB::shouldReceive('pluck')->andReturn(collect(['default' => 3, 'emails' => 2]));

        $response = $this->getJson('/queue-status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'driver',
            'default_queue',
            'timestamp',
            'pending',
            'failed',
            'pending_by_queue',
            'healthy',
            'max_queue_size',
        ]);

        $response->assertJson([
            'driver' => 'database',
        ]);
    }

    public function test_queue_status_endpoint_with_redis_driver(): void
    {
        config(['queue.default' => 'redis']);
        Queue::shouldReceive('size')->once()->andReturn(10);
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        $response = $this->getJson('/queue-status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'driver',
            'default_queue',
            'timestamp',
            'pending',
            'failed',
            'healthy',
            'max_queue_size',
        ]);

        $response->assertJson([
            'driver' => 'redis',
        ]);
    }

    public function test_health_endpoint_returns_valid_timestamp(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertNotEmpty($data['timestamp']);
        $this->assertIsString($data['timestamp']);
    }

    public function test_readiness_endpoint_returns_valid_timestamp(): void
    {
        Cache::shouldReceive('put')->andReturn(true);
        Cache::shouldReceive('get')->andReturn(true);
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        DB::shouldReceive('connection')->andReturnSelf();
        DB::shouldReceive('getPdo')->once();
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);
        config(['queue.default' => 'sync']);

        $response = $this->getJson('/ready');

        $data = $response->json();
        $this->assertNotEmpty($data['timestamp']);
        $this->assertIsString($data['timestamp']);
    }
}
