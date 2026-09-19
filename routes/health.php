# Health check endpoint
Route::get('/health', function () {
    $checks = [
        'database' => false,
        'cache' => false,
        'queue' => false,
    ];

    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        //
    }

    try {
        Cache::store()->get('health-check');
        $checks['cache'] = true;
    } catch (\Exception $e) {
        //
    }

    try {
        Redis::ping();
        $checks['queue'] = true;
    } catch (\Exception $e) {
        //
    }

    $healthy = !in_array(false, $checks, true);

    return response()->json([
        'status' => $healthy ? 'ok' : 'degraded',
        'timestamp' => now()->toISOString(),
        'checks' => $checks,
    ], $healthy ? 200 : 503);
});

// Readiness probe (for Kubernetes)
Route::get('/ready', function () {
    return response()->json(['status' => 'ready']);
});

// Liveness probe (for Kubernetes)
Route::get('/live', function () {
    return response()->json(['status' => 'alive']);
});
