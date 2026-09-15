<?php

use App\Http\Controllers\Api\ApiAgencyController;
use App\Http\Controllers\Api\ApiAgentController;
use App\Http\Controllers\Api\ApiAgentWorkflowController;
use App\Http\Controllers\Api\ApiAiController;
use App\Http\Controllers\Api\ApiAnalyticsController;
use App\Http\Controllers\Api\ApiCampaignController;
use App\Http\Controllers\Api\ApiClientController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiInvoiceController;
use App\Http\Controllers\Api\ApiReportController;
use App\Http\Controllers\Api\ApiRoleController;
use App\Http\Controllers\Api\ApiSocialAccountController;
use App\Http\Controllers\Api\ApiSocialPostController;
use App\Http\Controllers\Api\ApiWorkflowController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SocialPostController;
use App\Http\Controllers\WorkflowWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'agency', 'throttle.api:60,1', 'cache.etag:300'])->as('api.')->group(function () {
    Route::get('/status', fn () => ['status' => 'ok', 'version' => 'v1']);

    // Dashboard
    Route::get('/dashboard', [ApiDashboardController::class, 'index']);

    // Resources with dedicated controllers
    Route::apiResource('posts', ApiSocialPostController::class);
    Route::apiResource('accounts', ApiSocialAccountController::class);
    Route::apiResource('campaigns', ApiCampaignController::class);
    Route::apiResource('clients', ApiClientController::class);
    Route::apiResource('invoices', ApiInvoiceController::class);
    Route::apiResource('workflows', ApiWorkflowController::class);

    // Enterprise Reports
    Route::get('/reports/types', [ApiReportController::class, 'types']);
    Route::get('/reports/scheduled', [ApiReportController::class, 'scheduled']);
    Route::post('/reports/export', [ApiReportController::class, 'export'])->middleware('throttle:10,1');
    Route::post('/reports/schedule', [ApiReportController::class, 'schedule'])->middleware('throttle:10,1');
    Route::apiResource('reports', ApiReportController::class);

    // Agent-powered Report routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::post('/agent-generate', [ReportController::class, 'generateWithAgent'])->middleware('throttle:5,1');
        Route::get('/{report}/agent-recommendations', [ReportController::class, 'getAgentRecommendations'])->middleware('throttle:10,1');
        Route::post('/agent-schedule', [ReportController::class, 'scheduleAgentReport'])->middleware('throttle:5,1');
    });

    // Agent-powered Campaign routes
    Route::prefix('campaigns')->name('campaigns.')->group(function () {
        Route::post('/{campaign}/agent-optimize', [CampaignController::class, 'optimizeWithAgent'])->middleware('throttle:5,1');
        Route::post('/{campaign}/agent-ab-test', [CampaignController::class, 'abTestWithAgent'])->middleware('throttle:5,1');
        Route::get('/{campaign}/agent-insights', [CampaignController::class, 'getAgentInsights'])->middleware('throttle:10,1');
    });

    // Agent-powered Social Post routes
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::post('/{post}/agent-schedule', [SocialPostController::class, 'scheduleWithAgent'])->middleware('throttle:5,1');
        Route::get('/{post}/agent-analyze', [SocialPostController::class, 'analyzeWithAgent'])->middleware('throttle:10,1');
        Route::post('/{post}/agent-reply-suggestions', [SocialPostController::class, 'replySuggestionsWithAgent'])->middleware('throttle:10,1');
    });

    // AI
    Route::post('/ai/generate', [ApiAiController::class, 'generate'])->middleware('throttle:10,1');

    // Agent Management
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/', [ApiAgentController::class, 'index']);
        Route::get('/stats', [ApiAgentController::class, 'stats']);
        Route::get('/health', [ApiAgentController::class, 'health']);
        Route::get('/{name}', [ApiAgentController::class, 'show']);
        Route::post('/{name}/dispatch', [ApiAgentController::class, 'dispatch'])->middleware('throttle:5,1');
        Route::get('/{name}/history', [ApiAgentController::class, 'history']);
    });

    // Agent Workflows
    Route::prefix('agent-workflows')->name('agent-workflows.')->group(function () {
        Route::get('/', [ApiAgentWorkflowController::class, 'index']);
        Route::post('/{name}/run', [ApiAgentWorkflowController::class, 'run'])->middleware('throttle:5,1');
        Route::get('/{name}/status/{executionId}', [ApiAgentWorkflowController::class, 'status']);
        Route::delete('/{name}/status/{executionId}', [ApiAgentWorkflowController::class, 'cancel']);
    });

    // Agency
    Route::get('/agency/settings', [ApiAgencyController::class, 'settings']);
    Route::put('/agency/settings', [ApiAgencyController::class, 'updateSettings']);
    Route::get('/agency/team', [ApiAgencyController::class, 'team']);
    Route::get('/agency/billing', [ApiAgencyController::class, 'billing']);

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
        Route::get('/cross-platform', [ApiAnalyticsController::class, 'crossPlatform']);
        Route::get('/platform/{platform}', [ApiAnalyticsController::class, 'platform']);
        Route::get('/growth', [ApiAnalyticsController::class, 'growth']);
        Route::get('/optimal-times', [ApiAnalyticsController::class, 'optimalTimes']);
        Route::get('/best-platform', [ApiAnalyticsController::class, 'bestPlatform']);
    });
});

// Public webhook endpoint (no auth)
Route::post('workflows/{workflow}/webhook/{secret}', [WorkflowWebhookController::class, 'handle'])->name('api.workflows.webhook');
