<?php

use App\Http\Controllers\PinterestController;

// Pinterest integration (authenticated users)
Route::middleware(['auth', 'agency'])->prefix('pinterest')->name('pinterest.')->group(function () {
    Route::get('/', [PinterestController::class, 'index'])->name('index');
    Route::get('/connect', [PinterestController::class, 'connect'])->name('connect');
    Route::get('/callback', [PinterestController::class, 'callback'])->name('callback');
    Route::delete('/disconnect/{accountId}', [PinterestController::class, 'disconnect'])->name('disconnect');
    Route::post('/toggle/{accountId}', [PinterestController::class, 'toggle'])->name('toggle');
    Route::get('/{accountId}/metrics', [PinterestController::class, 'metrics'])->name('metrics');
    Route::post('/{accountId}/refresh', [PinterestController::class, 'refreshToken'])->name('refresh');
});

// API: Pinterest pin creation
Route::prefix('api/v1')->middleware(['auth', 'agency', 'throttle:10,1'])->group(function () {
    Route::post('/pinterest/pins', [PinterestController::class, 'apiCreatePin'])
        ->name('api.pinterest.createPin');
});
