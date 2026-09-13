<?php

namespace Tests\Feature;

use App\Jobs\RetryFailedPost;
use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetryFailedPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_job_creates_correctly(): void
    {
        $job = new RetryFailedPost(1, 1);
        $this->assertEquals(1, $job->postId);
        $this->assertEquals(1, $job->attempt);
        $this->assertEquals(3, $job->tries);
    }

    public function test_max_retries_constant(): void
    {
        $this->assertEquals(3, RetryFailedPost::MAX_RETRIES);
        $this->assertEquals(60, RetryFailedPost::BASE_DELAY);
    }

    public function test_exponential_backoff_delays(): void
    {
        // Attempt 1: 60 * 2^1 = 120s
        // Attempt 2: 60 * 2^2 = 240s
        // Attempt 3: 60 * 2^3 = 480s
        $this->assertEquals(120, RetryFailedPost::BASE_DELAY * pow(2, 1));
        $this->assertEquals(240, RetryFailedPost::BASE_DELAY * pow(2, 2));
        $this->assertEquals(480, RetryFailedPost::BASE_DELAY * pow(2, 3));
    }
}
