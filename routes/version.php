<?php

use App\Http\Controllers\VersionController;

// Public API
app('router')->get('/api/version', [VersionController::class, 'latest'])->name('api.version');
app('router')->get('/api/version/check', [VersionController::class, 'check'])->name('api.version.check');

// Authenticated web
app('router')->middleware(['auth', 'agency', 'throttle:60,1'])->group(function () {
    app('router')->get('/changelog', [VersionController::class, 'index'])->name('changelog');
    app('router')->get('/api/changelog', [VersionController::class, 'changelog'])->name('api.changelog');
});
