<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Testing\TestResponse;

trait ApiTestHelpers
{
    /**
     * Perform an authenticated (or unauthenticated) JSON GET request.
     */
    protected function apiGet(string $url, ?User $user = null): TestResponse
    {
        if ($user !== null) {
            return $this->actingAs($user)->getJson($url);
        }

        return $this->getJson($url);
    }

    /**
     * Perform an authenticated (or unauthenticated) JSON POST request.
     */
    protected function apiPost(string $url, array $data = [], ?User $user = null): TestResponse
    {
        if ($user !== null) {
            return $this->actingAs($user)->postJson($url, $data);
        }

        return $this->postJson($url, $data);
    }

    /**
     * Assert the API response has a successful (2xx) status code.
     */
    protected function apiAssertSuccess(TestResponse $response): void
    {
        $status = $response->status();

        $this->assertTrue(
            $status >= 200 && $status < 300,
            "Expected success status (2xx), got {$status}."
        );
    }

    /**
     * Assert the API response is a 401 Unauthorized.
     */
    protected function apiAssertUnauthorized(TestResponse $response): void
    {
        $response->assertUnauthorized();
    }
}
