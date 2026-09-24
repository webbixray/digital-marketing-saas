<?php

use App\Http\Controllers\AI\AICreditController;
use App\Http\Controllers\Api\ApiAgencyController;
use App\Http\Controllers\Api\ApiAgentController;
use App\Http\Controllers\Api\ApiAgentWorkflowController;
use App\Http\Controllers\Api\ApiAiAuditController;
use App\Http\Controllers\Api\ApiAiController;
use App\Http\Controllers\Api\ApiAnalyticsController;
use App\Http\Controllers\Api\ApiCampaignController;
use App\Http\Controllers\Api\ApiClientController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiDocsController;
use App\Http\Controllers\Api\ApiInvoiceController;
use App\Http\Controllers\Api\ApiReportController;
use App\Http\Controllers\Api\ApiRoleController;
use App\Http\Controllers\Api\ApiSocialAccountController;
use App\Http\Controllers\Api\ApiSocialPostController;
use App\Http\Controllers\Api\ApiWorkflowController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClientReportController;
use App\Http\Controllers\DashboardInsightsController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\PredictiveAnalyticsController;
use App\Http\Controllers\QuotaController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SocialPostController;
use App\Http\Controllers\ContentTranslationController;
use App\Http\Controllers\Integrations\ZapierController;
use App\Http\Controllers\WorkflowWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'agency', 'throttle.api:60,1', 'cache.etag:300'])->as('api.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ApiDashboardController::class, 'index'])->name('dashboard');

    // Resources with dedicated controllers
    Route::apiResource('posts', ApiSocialPostController::class);
    Route::apiResource('accounts', ApiSocialAccountController::class);
    Route::apiResource('campaigns', ApiCampaignController::class);
    Route::apiResource('clients', ApiClientController::class);
    Route::apiResource('invoices', ApiInvoiceController::class);
    Route::apiResource('workflows', ApiWorkflowController::class);

    // Enterprise Reports
    Route::get('/reports/types', [ApiReportController::class, 'types'])->name('reports.types');
    Route::get('/reports/scheduled', [ApiReportController::class, 'scheduled'])->name('reports.scheduled');
    Route::post('/reports/export', [ApiReportController::class, 'export'])->middleware('throttle:10,1')->name('reports.export');
    Route::post('/reports/schedule', [ApiReportController::class, 'schedule'])->middleware('throttle:10,1')->name('reports.schedule');
    Route::apiResource('reports', ApiReportController::class);

    // Agent-powered Report routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::post('/agent-generate', [ReportController::class, 'generateWithAgent'])->middleware('throttle:5,1')->name('agent-generate');
        Route::get('/{report}/agent-recommendations', [ReportController::class, 'getAgentRecommendations'])->middleware('throttle:10,1')->name('agent-recommendations');
        Route::post('/agent-schedule', [ReportController::class, 'scheduleAgentReport'])->middleware('throttle:5,1')->name('agent-schedule');
    });

    // Agent-powered Campaign routes
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::post('/{campaign}/agent-optimize', [CampaignController::class, 'optimizeWithAgent'])->middleware('throttle:5,1')->name('agent-optimize');
        Route::post('/{campaign}/agent-ab-test', [CampaignController::class, 'abTestWithAgent'])->middleware('throttle:5,1')->name('agent-ab-test');
        Route::get('/{campaign}/agent-insights', [CampaignController::class, 'getAgentInsights'])->middleware('throttle:10,1')->name('agent-insights');
    });

    // Agent-powered Social Post routes
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::post('/{post}/agent-schedule', [SocialPostController::class, 'scheduleWithAgent'])->middleware('throttle:5,1')->name('agent-schedule');
        Route::get('/{post}/agent-analyze', [SocialPostController::class, 'analyzeWithAgent'])->middleware('throttle:10,1')->name('agent-analyze');
        Route::post('/{post}/agent-reply-suggestions', [SocialPostController::class, 'replySuggestionsWithAgent'])->middleware('throttle:10,1')->name('agent-reply-suggestions');
    });

    // AI
    Route::post('/ai/generate', [ApiAiController::class, 'generate'])->middleware('throttle:10,1')->name('ai.generate');

    // Content Translation
    Route::post('/translate', [ContentTranslationController::class, 'translate'])->middleware('throttle:10,1')->name('api.translate');
    Route::post('/translate/detect', [ContentTranslationController::class, 'detectLanguage'])->middleware('throttle:20,1')->name('api.translate.detect');

    // Agent Management
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/', [ApiAgentController::class, 'index'])->name('index');
        Route::get('/stats', [ApiAgentController::class, 'stats'])->name('stats');
        Route::get('/health', [ApiAgentController::class, 'health'])->name('health');
        Route::get('/{name}', [ApiAgentController::class, 'show'])->name('show');
        Route::post('/{name}/dispatch', [ApiAgentController::class, 'dispatch'])->middleware('throttle:5,1')->name('dispatch');
        Route::get('/{name}/history', [ApiAgentController::class, 'history'])->name('history');
    });

    // Agent Workflows
    Route::prefix('agent-workflows')->name('agent-workflows.')->group(function () {
        Route::get('/', [ApiAgentWorkflowController::class, 'index'])->name('index');
        Route::post('/{name}/run', [ApiAgentWorkflowController::class, 'run'])->middleware('throttle:5,1')->name('run');
        Route::get('/{name}/status/{executionId}', [ApiAgentWorkflowController::class, 'status'])->name('status');
        Route::delete('/{name}/status/{executionId}', [ApiAgentWorkflowController::class, 'cancel'])->name('cancel');
    });

    // Agency
    Route::get('/agency/settings', [ApiAgencyController::class, 'settings'])->name('agency.settings');
    Route::put('/agency/settings', [ApiAgencyController::class, 'updateSettings'])->name('agency.settings.update');
    Route::get('/agency/team', [ApiAgencyController::class, 'team'])->name('agency.team');
    Route::get('/agency/billing', [ApiAgencyController::class, 'billing'])->name('agency.billing');

    // Enterprise RBAC
    Route::prefix('rbac')->name('rbac.')->group(function () {
        Route::apiResource('roles', ApiRoleController::class);
        Route::post('/roles/assign', [ApiRoleController::class, 'assign'])->name('roles.assign');
        Route::post('/roles/remove', [ApiRoleController::class, 'remove'])->name('roles.remove');
        Route::get('/roles/{roleId}/permissions', [ApiRoleController::class, 'show'])->name('roles.permissions');
        Route::get('/users/{userId}/permissions', [ApiRoleController::class, 'userPermissions'])->name('users.permissions');
        Route::get('/audit-trail', [ApiRoleController::class, 'auditTrail'])->name('audit');
    });

    // Cross-platform analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/cross-platform', [ApiAnalyticsController::class, 'crossPlatform'])->name('cross-platform');
        Route::get('/platform/{platform}', [ApiAnalyticsController::class, 'platform'])->name('platform');
        Route::get('/growth', [ApiAnalyticsController::class, 'growth'])->name('growth');
        Route::get('/optimal-times', [ApiAnalyticsController::class, 'optimalTimes'])->name('optimal-times');
        Route::get('/best-platform', [ApiAnalyticsController::class, 'bestPlatform'])->name('best-platform');
    });

    // Quota management
    Route::get('/quota', [QuotaController::class, 'status'])->name('quota.status');
    Route::post('/quota/check', [QuotaController::class, 'check'])->name('quota.check');

    // AI Credits
    Route::get('/ai/credits/balance', [AICreditController::class, 'balance'])->name('ai.credits.balance');
    Route::post('/ai/credits/purchase', [AICreditController::class, 'purchase'])->name('ai.credits.purchase');
    Route::get('/ai/credits/success', [AICreditController::class, 'success'])->name('ai.credits.success');

    // AI Audit & Compliance (v7.0)
    Route::prefix('ai-audit')->name('ai-audit.')->group(function () {
        Route::get('/', [ApiAiAuditController::class, 'index'])->name('index');
        Route::get('/report', [ApiAiAuditController::class, 'report'])->name('report');
        Route::get('/flagged', [ApiAiAuditController::class, 'flagged'])->name('flagged');
        Route::get('/{id}', [ApiAiAuditController::class, 'show'])->name('show');
        Route::get('/{id}/explain', [ApiAiAuditController::class, 'explain'])->name('explain');
    });

    // Client Reports
    Route::get('client-reports', [ClientReportController::class, 'index'])->name('client-reports.index');
    Route::post('client-reports', [ClientReportController::class, 'generate'])->name('client-reports.generate');
    Route::get('client-reports/{report}', [ClientReportController::class, 'show'])->name('client-reports.show');
    Route::post('client-reports/{report}/publish', [ClientReportController::class, 'publish'])->name('client-reports.publish');
    Route::delete('client-reports/{report}', [ClientReportController::class, 'destroy'])->name('client-reports.destroy');

    // Referrals
    Route::get('/referrals/stats', [ReferralController::class, 'stats'])->name('referrals.stats');

    // Metrics (admin only)
    Route::get('/metrics', [MetricsController::class, 'index'])->name('metrics.index');

    // Dashboard insights
    Route::get('/dashboard/insights', [DashboardInsightsController::class, 'index'])->name('dashboard.insights');

    // Global Search
    Route::get('/search', [ApiSearchController::class, 'search'])->name('search');
    Route::get('/search/recent', [ApiSearchController::class, 'recent'])->name('search.recent');
    Route::get('/search/stats', [ApiSearchController::class, 'stats'])->name('search.stats');
});

// Zapier Integration routes
Route::prefix('v1/integrations/zapier')->middleware(['auth:sanctum', 'agency'])->as('api.integrations.zapier.')->group(function () {
    Route::get('/triggers', [ZapierController::class, 'triggers'])->name('triggers');
    Route::get('/actions', [ZapierController::class, 'actions'])->name('actions');
    Route::post('/actions/execute', [ZapierController::class, 'executeAction'])->name('actions.execute');
    Route::get('/triggers/{trigger}/data', [ZapierController::class, 'getTriggerData'])->name('triggers.data');
    Route::post('/subscribe', [ZapierController::class, 'subscribe'])->name('subscribe');
    Route::post('/unsubscribe', [ZapierController::class, 'unsubscribe'])->name('unsubscribe');
});

// Public endpoints (no auth)
Route::get('/status', fn () => ['status' => 'ok', 'version' => 'v1'])->name('api.status');

// Public webhook endpoint (no auth)
Route::post('workflows/{workflow}/webhook/{secret}', [WorkflowWebhookController::class, 'handle'])->name('api.workflows.webhook');
Route::get('/health', [HealthCheckController::class, 'check'])->name('api.health');

// Webhook management routes
Route::prefix('v1/webhooks')->middleware(['auth:sanctum', 'agency'])->as('api.webhooks.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ApiWebhookController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Api\ApiWebhookController::class, 'store'])->name('store');
    Route::get('/events', [\App\Http\Controllers\Api\ApiWebhookController::class, 'availableEvents'])->name('events');
    Route::get('/{webhook}', [\App\Http\Controllers\Api\ApiWebhookController::class, 'show'])->name('show');
    Route::put('/{webhook}', [\App\Http\Controllers\Api\ApiWebhookController::class, 'update'])->name('update');
    Route::delete('/{webhook}', [\App\Http\Controllers\Api\ApiWebhookController::class, 'destroy'])->name('destroy');
    Route::post('/{webhook}/regenerate-secret', [\App\Http\Controllers\Api\ApiWebhookController::class, 'regenerateSecret'])->name('regenerate-secret');
    Route::post('/{webhook}/test', [\App\Http\Controllers\Api\ApiWebhookController::class, 'test'])->name('test');
    Route::get('/{webhook}/deliveries', [\App\Http\Controllers\Api\ApiWebhookController::class, 'deliveries'])->name('deliveries');
});

// API Documentation (public)
Route::prefix('v1/docs')->name('api.docs.')->group(function () {
    Route::get('/', [ApiDocsController::class, 'index'])->name('index');
    Route::get('/openapi.json', [ApiDocsController::class, 'openapi'])->name('openapi');
    Route::get('/postman', [ApiDocsController::class, 'postman'])->name('postman');
});

// Onboarding routes (outside cache middleware for real-time updates)
Route::prefix('v1/onboarding')->middleware(['auth:sanctum', 'agency'])->as('api.onboarding.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\OnboardingController::class, 'index'])->name('index');
    Route::post('/{step}/complete', [\App\Http\Controllers\Api\OnboardingController::class, 'complete'])->name('complete');
    Route::post('/auto-detect', [\App\Http\Controllers\Api\OnboardingController::class, 'autoDetect'])->name('auto-detect');
});

// Client Portal 2.0 API
Route::prefix('v1/client-portal')->middleware(['auth:sanctum', 'agency'])->name('api.client-portal.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/campaigns', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'campaigns'])->name('campaigns');
    Route::get('/invoices', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'invoices'])->name('invoices');
    Route::get('/analytics', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'analytics'])->name('analytics');
    Route::get('/settings', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'settings'])->name('settings');
    Route::get('/notifications', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'notifications'])->name('notifications');
    Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'approve'])->name('approve')->middleware('throttle:20,1');
    Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'reject'])->name('reject')->middleware('throttle:20,1');
});

// Team Chat routes
Route::prefix('v1/chat')->middleware(['auth:sanctum', 'agency'])->as('api.chat.')->group(function () {
    Route::get('/channels', [\App\Http\Controllers\Api\ApiChatController::class, 'channels'])->name('channels');
    Route::post('/channels', [\App\Http\Controllers\Api\ApiChatController::class, 'createChannel'])->name('channels.store');
    Route::get('/channels/{channel}/messages', [\App\Http\Controllers\Api\ApiChatController::class, 'messages'])->name('messages');
    Route::post('/channels/{channel}/messages', [\App\Http\Controllers\Api\ApiChatController::class, 'sendMessage'])->name('messages.store');
    Route::post('/channels/{channel}/read', [\App\Http\Controllers\Api\ApiChatController::class, 'markAsRead'])->name('mark-read');
    Route::post('/messages/{message}/reactions', [\App\Http\Controllers\Api\ApiChatController::class, 'addReaction'])->name('reactions.add');
    Route::delete('/messages/{message}/reactions/{emoji}', [\App\Http\Controllers\Api\ApiChatController::class, 'removeReaction'])->name('reactions.remove');
    Route::put('/messages/{message}', [\App\Http\Controllers\Api\ApiChatController::class, 'editMessage'])->name('messages.update');
    Route::delete('/messages/{message}', [\App\Http\Controllers\Api\ApiChatController::class, 'deleteMessage'])->name('messages.destroy');
});

// Analytics routes (dedicated analytics controller)
Route::prefix('v1/analytics')->middleware(['auth:sanctum', 'agency'])->as('api.analytics.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\ApiAnalyticsController::class, 'dashboard'])->name('dashboard');
    Route::get('/daily', [\App\Http\Controllers\Api\ApiAnalyticsController::class, 'daily'])->name('daily');
    Route::get('/top-events', [\App\Http\Controllers\Api\ApiAnalyticsController::class, 'topEvents'])->name('top-events');
});

// Media AI API routes (v7.0)
Route::prefix('v1')->middleware(['auth:sanctum', 'agency'])->group(function () {
    Route::post('/media/ai/generate', [\App\Http\Controllers\Media\MediaAiController::class, 'generate'])->name('api.media.ai.generate')->middleware('throttle:10,1');
    Route::get('/media/analytics', [\App\Http\Controllers\Media\MediaAiController::class, 'analytics'])->name('api.media.analytics');
});

// Predictive Analytics API (v7.0)
Route::prefix('v1/predictive')->middleware(['auth:sanctum', 'agency'])->name('api.predictive.')->group(function () {
    Route::get('/churn', [PredictiveAnalyticsController::class, 'churn'])->name('churn');
    Route::get('/revenue', [PredictiveAnalyticsController::class, 'revenue'])->name('revenue');
    Route::get('/trends', [PredictiveAnalyticsController::class, 'trends'])->name('trends');
    Route::get('/optimal-times', [PredictiveAnalyticsController::class, 'optimalTimes'])->name('optimal-times');
    Route::post('/predict', [PredictiveAnalyticsController::class, 'predict'])->name('predict');
});

// AI Agent Marketplace API (v7.0)
Route::prefix('v1/agent-marketplace')->middleware(['auth:sanctum', 'agency'])->as('api.agent-marketplace.')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ApiAgentMarketplaceController::class, 'index'])->name('index');
    Route::get('/search', [App\Http\Controllers\Api\ApiAgentMarketplaceController::class, 'search'])->name('search');
    Route::get('/my-agents', [App\Http\Controllers\Api\ApiAgentMarketplaceController::class, 'myAgents'])->name('my-agents');
    Route::get('/{slug}', [App\Http\Controllers\Api\ApiAgentMarketplaceController::class, 'show'])->name('show');
    Route::post('/{item}/install', [App\Http\Controllers\Api\ApiAgentMarketplaceController::class, 'install'])->name('install');
    Route::post('/{item}/configure', [App\Http\Controllers\Api\ApiAgentMarketplaceController::class, 'configure'])->name('configure');
});

// Products API Resource
Route::apiResource('products', \App\Http\Controllers\Api\ApiProductController::class);

// Social Commerce API
Route::prefix('v1/social-commerce')->name('api.social-commerce.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'store'])->name('store');
    Route::get('/{post}', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'show'])->name('show');
    Route::put('/{post}', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'update'])->name('update');
    Route::delete('/{post}', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'destroy'])->name('destroy');
    Route::get('/{post}/analytics', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'analytics'])->name('analytics');
    Route::post('/shopify/connect', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'shopifyConnect'])->name('shopify-connect');
    Route::delete('/shopify/disconnect', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'shopifyDisconnect'])->name('shopify-disconnect');
    Route::post('/woo/connect', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'wooConnect'])->name('woo-connect');
    Route::delete('/woo/disconnect', [\App\Http\Controllers\Api\ApiSocialCommerceController::class, 'wooDisconnect'])->name('woo-disconnect');
});

// Billing Credits API
Route::get('/billing/credits', [\App\Http\Controllers\CreditController::class, 'index'])->name('api.billing.credits');

// Billing Usage API
Route::get('/billing/usage', [\App\Http\Controllers\UsageController::class, 'index'])->name('api.billing.usage');

// Client Portal 2.0 API
Route::prefix('v1/client-portal')->name('api.client-portal.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/campaigns', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'campaigns'])->name('campaigns');
    Route::get('/invoices', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'invoices'])->name('invoices');
    Route::get('/analytics', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'analytics'])->name('analytics');
    Route::get('/settings', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'settings'])->name('settings');
    Route::get('/notifications', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'notifications'])->name('notifications');
    Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'approve'])->name('approve')->middleware('throttle:20,1');
    Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'reject'])->name('reject')->middleware('throttle:20,1');
    Route::get('/activity', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'activity'])->name('activity');
    Route::get('/profile', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'profile'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\Api\ApiClientPortalController::class, 'updateProfile'])->name('profile.update');
});
