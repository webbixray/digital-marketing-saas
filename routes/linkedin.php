<?php

use App\Http\Controllers\LinkedInController;

// LinkedIn integration (authenticated users)
Route::middleware(['auth', 'agency'])->prefix('linkedin')->name('linkedin.')->group(function () {
    Route::get('/', [LinkedInController::class, 'index'])->name('index');
    Route::get('/connect', [LinkedInController::class, 'connect'])->name('connect');
    Route::get('/callback', [LinkedInController::class, 'callback'])->name('callback');
    Route::delete('/disconnect/{accountId}', [LinkedInController::class, 'disconnect'])->name('disconnect');
    Route::post('/toggle/{accountId}', [LinkedInController::class, 'toggle'])->name('toggle');
    Route::get('/{accountId}/metrics', [LinkedInController::class, 'metrics'])->name('metrics');
    Route::post('/{accountId}/refresh', [LinkedInController::class, 'refreshToken'])->name('refresh');
});
