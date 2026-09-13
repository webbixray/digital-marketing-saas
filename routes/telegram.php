<?php

use App\Http\Controllers\TelegramLinkController;
use App\Http\Controllers\TelegramWebhookController;

// Public webhook endpoint (no auth)
app('router')->post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook');

// Webhook management (admin only)
app('router')->middleware(['auth', 'agency'])->prefix('telegram')->name('telegram.')->group(function () {
    app('router')->get('/setup', [TelegramWebhookController::class, 'setupWebhook'])->name('setup');
    app('router')->get('/info', [TelegramWebhookController::class, 'webhookInfo'])->name('info');
});

// Account linking (authenticated users)
app('router')->middleware(['auth', 'agency'])->prefix('telegram/link')->name('telegram.link.')->group(function () {
    app('router')->get('/', [TelegramLinkController::class, 'index'])->name('index');
    app('router')->post('/', [TelegramLinkController::class, 'link'])->name('store');
    app('router')->delete('/', [TelegramLinkController::class, 'unlink'])->name('unlink');
    app('router')->post('/regenerate', [TelegramLinkController::class, 'regenerateCode'])->name('regenerate');
});

// API: Link via bot (rate-limited to prevent brute-force)
app('router')->post('/api/telegram/link', [TelegramLinkController::class, 'linkViaBot'])
    ->middleware(['throttle:10,1'])
    ->name('api.telegram.link');
