<?php

use App\Http\Controllers\AdminDashboardController;

// Admin dashboard (owner/admin only)
Route::middleware(['auth', 'agency', 'role:owner|admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/health', [AdminDashboardController::class, 'health'])->name('health');
    Route::get('/failed-jobs', [AdminDashboardController::class, 'failedJobs'])->name('failed-jobs');
    Route::post('/failed-jobs/{jobId}/retry', [AdminDashboardController::class, 'retryJob'])->name('retry-job');
    Route::delete('/failed-jobs/{jobId}', [AdminDashboardController::class, 'deleteFailedJob'])->name('delete-failed-job');
});
