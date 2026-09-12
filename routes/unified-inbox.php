<?php

use App\Http\Controllers\UnifiedInboxController;

// Unified inbox (web routes)
Route::middleware(['auth', 'agency'])->prefix('inbox')->name('unified-inbox.')->group(function () {
    Route::get('/', [UnifiedInboxController::class, 'index'])->name('index');
    Route::get('/{messageId}', [UnifiedInboxController::class, 'show'])->name('show');
});

// Unified inbox API
Route::prefix('api/v1/inbox')->middleware(['auth', 'agency'])->name('api.unified-inbox.')->group(function () {
    Route::get('/', [UnifiedInboxController::class, 'apiIndex'])->name('index');
    Route::post('/{messageId}/read', [UnifiedInboxController::class, 'apiMarkRead'])->name('mark-read');
    Route::post('/read-all', [UnifiedInboxController::class, 'apiMarkAllRead'])->name('mark-all-read');
    Route::post('/{messageId}/reply', [UnifiedInboxController::class, 'apiReply'])->name('reply');
    Route::delete('/{messageId}', [UnifiedInboxController::class, 'apiDelete'])->name('delete');
});
