<?php

namespace Tests\Concerns;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

trait TestHelpers
{
    /**
     * Create a new agency with the given attributes.
     */
    protected function createAgency(array $attrs = []): Agency
    {
        return Agency::factory()->create($attrs);
    }

    /**
     * Create a user associated with an agency.
     *
     * @param  Agency|null  $agency  When null, a new agency is created automatically.
     */
    protected function createAuthenticatedUser($agency = null, array $userAttrs = []): User
    {
        if ($agency === null) {
            $agency = $this->createAgency();
        }

        return User::factory()->create(array_merge(
            ['agency_id' => $agency->id],
            $userAttrs
        ));
    }

    /**
     * Assert that a model belongs to the given agency.
     *
     * @param  Model  $model
     */
    protected function assertAgencyScoped($model, int $agencyId): void
    {
        $this->assertEquals($agencyId, $model->agency_id);
    }

    /**
     * Assert the response indicates an authorized / successful request (2xx).
     */
    protected function assertAuthorized(TestResponse $response): void
    {
        $status = $response->status();

        $this->assertTrue(
            $status >= 200 && $status < 300,
            "Expected authorized status (2xx), got {$status}."
        );
    }

    /**
     * Assert the response is a 403 Forbidden.
     */
    protected function assertForbidden(TestResponse $response): void
    {
        $response->assertForbidden();
    }

    /**
     * Assert the response is a 422 validation error on the given fields.
     */
    protected function assertValidationError(TestResponse $response, array $fields): void
    {
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($fields);
    }

    /**
     * Create a collection of users belonging to the same agency, forming a "team".
     *
     * Returns a collection containing one manager and ($memberCount - 1) members.
     * If $memberCount is 1, only a manager is returned.
     */
    protected function createTeamWithMembers(Agency $agency, int $memberCount = 3): Collection
    {
        $team = collect();

        $team->push(User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'manager',
        ]));

        $remaining = $memberCount - 1;

        if ($remaining > 0) {
            $members = User::factory()->count($remaining)->create([
                'agency_id' => $agency->id,
                'role' => 'member',
            ]);

            $team = $team->merge($members);
        }

        return $team;
    }
}
