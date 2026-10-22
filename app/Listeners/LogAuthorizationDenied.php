<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Support\Facades\Auth;

/**
 * Log every authorization denial to the activity log for security auditing.
 *
 * Captures: user, agency, action, model type/ID, IP address, user agent.
 * This creates an immutable audit trail of all access control decisions.
 */
class LogAuthorizationDenied
{
    /**
     * Handle the event.
     */
    public function handle(GateEvaluated $event): void
    {
        // Only log denials, not successful authorizations
        if ($event->result !== false) {
            return;
        }

        $user = $event->user ?? Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $model = $event->model ?? null;

        ActivityLog::log(
            $user->agency,
            'authorization.denied',
            sprintf(
                'Authorization denied for ability "%s"%s',
                $event->ability,
                $model ? ' on ' . get_class($model) . ' #' . $model->id : ''
            ),
            $user,
            is_object($model) ? get_class($model) : null,
            $model ? $model->id : null,
            [
                'ability' => $event->ability,
                'arguments' => $event->arguments,
                'result' => $event->result,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'route' => request()->route()?->uri(),
                'method' => request()->method(),
            ]
        );
    }
}
