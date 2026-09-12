<?php

use App\Http\Controllers\HealthCheckController;

// Health Checks (public)
Route::get('/health', [HealthCheckController::class, 'index'])->name('health');
Route::get('/ready', [HealthCheckController::class, 'readiness'])->name('ready');
Route::get('/live', [HealthCheckController::class, 'liveness'])->name('live');
Route::get('/status', [HealthCheckController::class, 'status'])->name('status');

// security.txt (RFC 9116)
Route::get('/.well-known/security.txt', function () {
    $expiry = now()->addYear()->toIso8601String();
    $content = "Contact: mailto:security@digitalmarketingsaas.com\n";
    $content .= "Expires: {$expiry}\n";
    $content .= "Preferred-Languages: en\n";
    $content .= "Canonical: https://your-domain.com/.well-known/security.txt\n";

    return response($content, 200, [
        'Content-Type' => 'text/plain; charset=utf-8',
    ]);
})->name('security.txt');
