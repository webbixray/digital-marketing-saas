<?php

namespace Tests\Feature\Middleware;

use App\Listeners\LogAuthorizationDenied;
use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogAuthorizationDeniedTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_class_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Listeners\LogAuthorizationDenied::class),
            'LogAuthorizationDenied listener class must exist'
        );
    }

    public function test_listener_has_handle_method(): void
    {
        $listener = new LogAuthorizationDenied();
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_activity_log_can_record_authorization_denied(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'member',
        ]);

        // Directly test ActivityLog::log (which the listener calls)
        ActivityLog::log(
            $agency,
            'authorization.denied',
            'Authorization denied for ability "view"',
            $user,
            null,
            null,
            ['ability' => 'view', 'result' => false]
        );

        $this->assertTrue(
            ActivityLog::where('action', 'authorization.denied')->exists(),
            'Authorization denial should be logged to ActivityLog'
        );

        $log = ActivityLog::where('action', 'authorization.denied')->first();
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals($agency->id, $log->agency_id);
        $this->assertNotNull($log->metadata);
        $this->assertArrayHasKey('ability', $log->metadata);
        $this->assertEquals('view', $log->metadata['ability']);
    }

    public function test_gate_evaluated_allowed_is_not_logged(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'owner',
        ]);

        $user->load('agency');

        $this->actingAs($user);

        $listener = new LogAuthorizationDenied();
        $listener->handle(new GateEvaluated(
            $user,
            'view',
            [],
            true,
            $agency
        ));

        $this->assertFalse(
            ActivityLog::where('action', 'authorization.denied')->exists(),
            'Authorized access should not be logged as denial'
        );
    }
}
