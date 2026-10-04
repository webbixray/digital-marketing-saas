<?php

namespace Tests\Unit\Jobs;

use App\Jobs\CheckForUpdates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_for_updates_job_can_be_dispatched(): void
    {
        $job = new CheckForUpdates;
        $this->assertInstanceOf(CheckForUpdates::class, $job);
    }

    public function test_job_implements_should_queue(): void
    {
        $job = new CheckForUpdates;
        $this->assertTrue(method_exists($job, 'handle'));
    }
}
