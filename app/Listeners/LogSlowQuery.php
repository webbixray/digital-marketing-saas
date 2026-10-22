<?php

namespace App\Listeners;

use App\Events\SlowQueryDetected;
use Illuminate\Support\Facades\Log;

class LogSlowQuery
{
    public function handle(SlowQueryDetected $event): void
    {
        Log::channel(config('database-slow-queries.query_log_channel', 'stack'))->warning(
            'Slow query detected',
            [
                'query' => $event->query,
                'bindings' => $event->bindings,
                'time_ms' => $event->time,
                'connection' => $event->connection,
                'file' => $event->file ?? null,
                'line' => $event->line ?? null,
                'trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10))
                    ->map(fn (array $frame) => ($frame['file'] ?? 'unknown') . ':' . ($frame['line'] ?? 0) . ' ' . $frame['function'])
                    ->take(10)
                    ->implode("\n"),
            ]
        );
    }
}
