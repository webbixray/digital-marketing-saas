<?php

use App\Http\Controllers\FacebookWebhookController;
use App\Http\Controllers\LinkedInWebhookController;
use App\Http\Controllers\TikTokWebhookController;
use App\Http\Controllers\YouTubeWebhookController;

// Facebook webhooks (public) — throttled to absorb platform retries and abuse
Route::get('/webhook/facebook', [FacebookWebhookController::class, 'verify'])->name('webhook.facebook.verify');
Route::post('/webhook/facebook', [FacebookWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('webhook.facebook.handle');

// LinkedIn webhooks (public)
Route::get('/webhook/linkedin', [LinkedInWebhookController::class, 'verify'])->name('webhook.linkedin.verify');
Route::post('/webhook/linkedin', [LinkedInWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('webhook.linkedin.handle');

// TikTok webhooks (public)
Route::get('/webhook/tiktok', [TikTokWebhookController::class, 'verify'])->name('webhook.tiktok.verify');
Route::post('/webhook/tiktok', [TikTokWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('webhook.tiktok.handle');

// YouTube webhooks (PubSubHubbub)
Route::get('/webhook/youtube', [YouTubeWebhookController::class, 'verify'])->name('webhook.youtube.verify');
Route::post('/webhook/youtube', [YouTubeWebhookController::class, 'handle'])->middleware('throttle:120,1')->name('webhook.youtube.handle');
