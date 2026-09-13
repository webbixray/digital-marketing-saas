<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->get('/health');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_readiness_endpoint_returns_200(): void
    {
        $response = $this->get('/ready');
        $response->assertStatus(200);
    }

    public function test_liveness_endpoint_returns_200(): void
    {
        $response = $this->get('/live');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'alive']);
    }

    public function test_status_endpoint_returns_200(): void
    {
        $response = $this->get('/status');
        $response->assertStatus(200);
    }

    public function test_disk_space_endpoint_returns_200(): void
    {
        $response = $this->get('/disk-space');
        $response->assertStatus(200);
    }
}
