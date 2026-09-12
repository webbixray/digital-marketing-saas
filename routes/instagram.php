<?php

use App\Http\Controllers\InstagramController;
use App\Http\Controllers\InstagramWebhookController;

// Public webhook endpoint (no auth)
Route::post('/instagram/webhook', [InstagramWebhookController::class, 'handle'])
    ->name('instagram.webhook');

// Instagram Business API integration (authenticated users)
Route::middleware(['auth', 'agency'])->prefix('instagram')->name('instagram.')->group(function () {
    Route::get('/', [InstagramController::class, 'index'])->name('index');
    Route::get('/connect', [InstagramController::class, 'connect'])->name('connect');
    Route::get('/callback', [InstagramController::class, 'callback'])->name('callback');
    Route::delete('/disconnect/{accountId}', [InstagramController::class, 'disconnect'])->name('disconnect');
    Route::post('/toggle/{accountId}', [InstagramController::class, 'toggle'])->name('toggle');
    Route::get('/{accountId}/metrics', [InstagramController::class, 'metrics'])->name('metrics');
    Route::post('/{accountId}/refresh', [InstagramController::class, 'refreshToken'])->name('refresh');
});

// API: Instagram media publishing
Route::prefix('api/v1')->middleware(['auth', 'agency', 'throttle:10,1'])->group(function () {
    Route::post('/instagram/publish', [InstagramController::class, 'apiPublish'])
        ->name('api.instagram.publish');
    Route::get('/instagram/insights', [InstagramController::class, 'apiInsights'])
        ->name('api.instagram.insights');
});
