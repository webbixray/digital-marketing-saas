<?php

namespace Tests;

use App\Models\User;
use Illuminate\Testing\TestResponse;

trait ApiTestHelpers
{
    protected function actingAsApi(User $user): self
    {
        return $this->actingAs($user, 'sanctum');
    }

    protected function assertValidationError(TestResponse $response, string $field): void
    {
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$field]);
    }

    protected function assertUnauthorized(TestResponse $response): void
    {
        $response->assertStatus(401);
        $response->assertJson(['error' => 'authentication_required']);
    }

    protected function assertForbidden(TestResponse $response): void
    {
        $response->assertStatus(403);
        $response->assertJson(['error' => 'forbidden']);
    }

    protected function assertNotFound(TestResponse $response): void
    {
        $response->assertStatus(404);
        $response->assertJson(['error' => 'not_found']);
    }
}
