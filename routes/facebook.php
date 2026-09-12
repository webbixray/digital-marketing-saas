<?php

use App\Http\Controllers\FacebookController;

// Facebook integration (authenticated users)
Route::middleware(['auth', 'agency'])->prefix('facebook')->name('facebook.')->group(function () {
    Route::get('/', [FacebookController::class, 'index'])->name('index');
    Route::get('/connect', [FacebookController::class, 'connect'])->name('connect');
    Route::get('/callback', [FacebookController::class, 'callback'])->name('callback');
    Route::delete('/disconnect/{accountId}', [FacebookController::class, 'disconnect'])->name('disconnect');
    Route::post('/toggle/{accountId}', [FacebookController::class, 'toggle'])->name('toggle');
    Route::get('/{accountId}/metrics', [FacebookController::class, 'metrics'])->name('metrics');
    Route::post('/{accountId}/refresh', [FacebookController::class, 'refreshToken'])->name('refresh');
});
