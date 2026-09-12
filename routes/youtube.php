<?php

use App\Http\Controllers\YouTubeController;

// YouTube integration (authenticated users)
Route::middleware(['auth', 'agency'])->prefix('youtube')->name('youtube.')->group(function () {
    Route::get('/', [YouTubeController::class, 'index'])->name('index');
    Route::get('/connect', [YouTubeController::class, 'connect'])->name('connect');
    Route::get('/callback', [YouTubeController::class, 'callback'])->name('callback');
    Route::delete('/disconnect/{accountId}', [YouTubeController::class, 'disconnect'])->name('disconnect');
    Route::post('/toggle/{accountId}', [YouTubeController::class, 'toggle'])->name('toggle');
    Route::get('/{accountId}/metrics', [YouTubeController::class, 'metrics'])->name('metrics');
    Route::post('/{accountId}/refresh', [YouTubeController::class, 'refreshToken'])->name('refresh');
});

// API: YouTube video upload
Route::prefix('api/v1')->middleware(['auth', 'agency', 'throttle:5,1'])->group(function () {
    Route::post('/youtube/upload', [YouTubeController::class, 'apiUpload'])
        ->name('api.youtube.upload');
});
