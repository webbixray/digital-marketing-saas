<?php

use App\Http\Controllers\TikTokController;

// TikTok integration (authenticated users)
Route::middleware(['auth', 'agency'])->prefix('tiktok')->name('tiktok.')->group(function () {
    Route::get('/', [TikTokController::class, 'index'])->name('index');
    Route::get('/connect', [TikTokController::class, 'connect'])->name('connect');
    Route::get('/callback', [TikTokController::class, 'callback'])->name('callback');
    Route::delete('/disconnect/{accountId}', [TikTokController::class, 'disconnect'])->name('disconnect');
    Route::post('/toggle/{accountId}', [TikTokController::class, 'toggle'])->name('toggle');
    Route::get('/{accountId}/metrics', [TikTokController::class, 'metrics'])->name('metrics');
    Route::post('/{accountId}/refresh', [TikTokController::class, 'refreshToken'])->name('refresh');
});

// API: TikTok video publishing
Route::prefix('api/v1')->middleware(['auth', 'agency', 'throttle:10,1'])->group(function () {
    Route::post('/tiktok/publish', [TikTokController::class, 'apiPublish'])
        ->name('api.tiktok.publish');
    Route::get('/tiktok/videos', [TikTokController::class, 'apiVideos'])
        ->name('api.tiktok.videos');
});
