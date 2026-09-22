<?php

use App\Http\Controllers\AbTestController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\AiContentController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CancellationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContentCalendarController;
use App\Http\Controllers\ContentLibraryController;
use App\Http\Controllers\ContentTemplateController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Email\EmailCampaignController;
use App\Http\Controllers\Email\EmailTemplateController;
use App\Http\Controllers\Email\TrackingController;
use App\Http\Controllers\Email\UnsubscribeController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\FeatureFlagController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\GdprController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\MediaLibraryController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PublicClientReportController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\SocialPostController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\SystemBackupController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\TeamActivityController;
use App\Http\Controllers\TwitterController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhiteLabelController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'landing'])->name('public.landing');
Route::get('/pricing', [PublicController::class, 'pricing'])->name('public.pricing');
Route::get('/features', [PublicController::class, 'features'])->name('public.features');
Route::get('/docs', [PublicController::class, 'docs'])->name('public.docs');
Route::get('/blog', [PublicController::class, 'blog'])->name('public.blog');
Route::get('/welcome', [PublicController::class, 'welcome'])->name('public.welcome');
Route::get('/terms', [PublicController::class, 'terms'])->name('public.terms');
Route::get('/privacy', [PublicController::class, 'privacy'])->name('public.privacy');
Route::get('/contact', [PublicController::class, 'contact'])->name('public.contact');
Route::post('/contact', [PublicController::class, 'contactSubmit'])->name('public.contact.submit');
Route::get('landing/{slug}', [LandingPageController::class, 'render'])->name('public.landing-page');
Route::post('/newsletter', [PublicController::class, 'newsletter'])->name('public.newsletter');

// Email unsubscribe (public, no auth)
Route::get('/email/unsubscribe/{recipient}', [UnsubscribeController::class, 'show'])->name('email.unsubscribe');
Route::post('/email/unsubscribe/{recipient}', [UnsubscribeController::class, 'confirm'])->name('email.unsubscribe.confirm');

// Email tracking (public, no auth)
Route::get('/email/track/open/{recipient}', [TrackingController::class, 'open'])->name('email.track.open');
Route::get('/email/track/click/{recipient}', [TrackingController::class, 'click'])->name('email.track.click');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Email Verification Routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect()->route('dashboard')->with('success', 'Email verified successfully!');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('/password/reset', [ResetPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [ResetPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:5,1');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::get('/auth/{provider}', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

Route::middleware(['auth', 'agency'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    // Comments (morph type)
    Route::prefix('comments')->name('comments.')->group(function () {
        Route::get('/', [CommentController::class, 'index'])->name('index');
        Route::get('/{comment}', [CommentController::class, 'show'])->name('show');
        Route::post('/', [CommentController::class, 'store'])->name('store');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('social')->name('social.')->group(function () {
        Route::resource('accounts', SocialAccountController::class)->except('show');
        Route::post('accounts/{account}/toggle', [SocialAccountController::class, 'toggle'])->name('accounts.toggle');
        Route::resource('posts', SocialPostController::class);
        Route::post('posts/{post}/publish', [SocialPostController::class, 'publish'])->name('posts.publish');
        Route::post('posts/{post}/retry', [SocialPostController::class, 'retry'])->name('posts.retry');
        Route::post('posts/{post}/score', [SocialPostController::class, 'score'])->name('posts.score');
        Route::post('posts/{post}/agent-schedule', [SocialPostController::class, 'scheduleWithAgent'])->name('posts.agent-schedule');
        Route::get('posts/{post}/agent-analyze', [SocialPostController::class, 'analyzeWithAgent'])->name('posts.agent-analyze');
        Route::post('posts/{post}/agent-reply-suggestions', [SocialPostController::class, 'replySuggestionsWithAgent'])->name('posts.agent-reply-suggestions');
    });

    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/status', [CampaignController::class, 'changeStatus'])->name('campaigns.status');
    Route::post('campaigns/{campaign}/agent-optimize', [CampaignController::class, 'optimizeWithAgent'])->name('campaigns.agent-optimize');
    Route::post('campaigns/{campaign}/agent-ab-test', [CampaignController::class, 'abTestWithAgent'])->name('campaigns.agent-ab-test');
    Route::get('campaigns/{campaign}/agent-insights', [CampaignController::class, 'getAgentInsights'])->name('campaigns.agent-insights');

    Route::resource('clients', ClientController::class);

    // Cancellation Flow
    Route::prefix('cancel')->name('cancellation.')->group(function () {
        Route::get('/', [CancellationController::class, 'survey'])->name('survey');
        Route::post('/survey', [CancellationController::class, 'submitSurvey'])->name('submit');
        Route::get('/confirm', [CancellationController::class, 'confirm'])->name('confirm');
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/', [AiContentController::class, 'index'])->name('index');
        Route::post('/generate', [AiContentController::class, 'generate'])->name('generate');
        Route::post('/rewrite', [AiContentController::class, 'rewrite'])->name('rewrite');
        Route::post('/hashtags', [AiContentController::class, 'hashtags'])->name('hashtags');
        Route::post('/ideas', [AiContentController::class, 'ideas'])->name('ideas');
    });

    // Agent Management
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index');
        Route::get('/stats', [AgentController::class, 'stats'])->name('stats');
        Route::post('/dispatch', [AgentController::class, 'dispatch'])->name('dispatch');
        Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/workflows', [AgentController::class, 'workflows'])->name('workflows');
        Route::post('/run-workflow', [AgentController::class, 'runWorkflow'])->name('run-workflow');
        Route::get('/{agentName}', [AgentController::class, 'agentDetail'])->name('show');
    });

    Route::get('workflows/builder', [WorkflowController::class, 'builder'])->name('workflows.builder');
    Route::get('workflows/builder/{workflow}', [WorkflowController::class, 'builder'])->name('workflows.builder.edit');
    Route::post('workflows/builder/save', [WorkflowController::class, 'storeFromBuilder'])->name('workflows.builder.save');
    Route::post('workflows/builder/update/{workflow}', [WorkflowController::class, 'updateFromBuilder'])->name('workflows.builder.update');
    Route::post('workflows/{workflow}/execute', [WorkflowController::class, 'execute'])->name('workflows.execute');
    Route::get('workflows/templates/{template}', [WorkflowController::class, 'createFromTemplate'])->name('workflows.templates.use');
    Route::resource('workflows', WorkflowController::class);
    Route::post('workflows/{workflow}/toggle', [WorkflowController::class, 'toggleStatus'])->name('workflows.toggle');
    Route::get('workflows/{workflow}/versions', [WorkflowController::class, 'versions'])->name('workflows.versions');
    Route::post('workflows/{workflow}/versions/{version}/restore', [WorkflowController::class, 'restoreVersion'])->name('workflows.versions.restore');
    Route::get('workflows/{workflow}/webhook', [WorkflowController::class, 'webhookInfo'])->name('workflows.webhook');
    Route::post('workflows/{workflow}/webhook/regenerate', [WorkflowController::class, 'regenerateWebhook'])->name('workflows.webhook.regenerate');

    // Legacy inbox (redirects to unified inbox)
    Route::get('/inbox/legacy', [InboxController::class, 'index'])->name('inbox.index');
    Route::post('inbox/{message}/triage', [InboxController::class, 'triage'])->name('inbox.triage');
    Route::post('inbox/{message}/reply', [InboxController::class, 'reply'])->name('inbox.reply');

    Route::resource('content', ContentLibraryController::class);

    Route::resource('landing-pages', LandingPageController::class)->except('destroy');
    Route::delete('landing-pages/{page}', [LandingPageController::class, 'destroy'])->name('landing-pages.destroy');
    Route::post('landing-pages/{page}/toggle', [LandingPageController::class, 'togglePublish'])->name('landing-pages.toggle');

    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.paid');

    Route::resource('activity', ActivityLogController::class)->except('create', 'store', 'edit', 'update');
    Route::resource('forms', FormController::class);

    // Media Library
    Route::resource('media', MediaLibraryController::class);
    Route::get('media/{asset}/download', [MediaLibraryController::class, 'download'])->name('media.download');
    Route::post('media/{asset}/duplicate', [MediaLibraryController::class, 'duplicate'])->name('media.duplicate');
    Route::delete('media/bulk-delete', [MediaLibraryController::class, 'bulkDelete'])->name('media.bulk-delete');

    // Support Tickets
    Route::resource('support', SupportTicketController::class);
    Route::post('support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');

    // System Status
    Route::get('/system-status', [SystemStatusController::class, 'index'])->name('system.status');

    // Backup Management
    Route::get('/system-backup', [SystemBackupController::class, 'index'])->name('system.backup.index');
    Route::post('/system-backup', [SystemBackupController::class, 'store'])->name('system.backup.store');
    Route::post('/system-backup/{filename}/restore', [SystemBackupController::class, 'restore'])->name('system.backup.restore');

    // A/B Testing
    Route::prefix('ab-testing')->name('ab-testing.')->group(function () {
        Route::get('/', [AbTestController::class, 'index'])->name('index');
        Route::get('/create', [AbTestController::class, 'create'])->name('create');
        Route::post('/', [AbTestController::class, 'store'])->name('store');
        Route::get('/{test}', [AbTestController::class, 'show'])->name('show');
        Route::post('/{test}/start', [AbTestController::class, 'start'])->name('start');
        Route::post('/{test}/pause', [AbTestController::class, 'pause'])->name('pause');
        Route::post('/{test}/complete', [AbTestController::class, 'complete'])->name('complete');
        Route::post('/{test}/track/{variant}/{event}', [AbTestController::class, 'trackEvent'])->name('track');
    });

    // Activity
    Route::get('/team-activity', [TeamActivityController::class, 'index'])->name('team.activity');

    // Twitter/X Integration
    Route::prefix('twitter')->name('twitter.')->middleware('platform.rate_limit:twitter')->group(function () {
        Route::get('/', [TwitterController::class, 'index'])->name('index');
        Route::get('/connect', [TwitterController::class, 'connect'])->name('connect');
        Route::get('/callback', [TwitterController::class, 'callback'])->name('callback');
        Route::post('/disconnect/{accountId}', [TwitterController::class, 'disconnect'])->name('disconnect');
        Route::get('/{accountId}/metrics', [TwitterController::class, 'metrics'])->name('metrics');
        Route::post('/post', [TwitterController::class, 'postTweet'])->name('post');
        Route::get('/{accountId}/timeline', [TwitterController::class, 'timeline'])->name('timeline');
    });
    Route::post('forms/{form}/toggle', [FormController::class, 'togglePublish'])->name('forms.toggle');
    Route::resource('webhooks', WebhookController::class);

    // Content Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

    // Legacy calendar routes (ContentCalendarController for drag-drop updates)
    Route::prefix('calendar')->name('content-calendar.')->group(function () {
        Route::post('/update-schedule', [ContentCalendarController::class, 'updateSchedule'])->name('update-schedule');
    });

    // Content Templates
    Route::resource('content-templates', ContentTemplateController::class);

    // Custom Fields
    Route::resource('custom-fields', CustomFieldController::class);

    // Feature Flags
    Route::prefix('admin-features')->name('features.')->group(function () {
        Route::resource('flags', FeatureController::class)->parameters(['flags' => 'feature']);
    });
    Route::prefix('feature-flags')->name('feature-flags.')->group(function () {
        Route::get('/', [FeatureFlagController::class, 'index'])->name('index');
        Route::get('/create', [FeatureFlagController::class, 'create'])->name('create');
        Route::post('/', [FeatureFlagController::class, 'store'])->name('store');
        Route::get('/{flag}', [FeatureFlagController::class, 'show'])->name('show');
        Route::get('/{flag}/edit', [FeatureFlagController::class, 'edit'])->name('edit');
        Route::put('/{flag}', [FeatureFlagController::class, 'update'])->name('update');
        Route::delete('/{flag}', [FeatureFlagController::class, 'destroy'])->name('destroy');
    });

    // Referral Program
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/ref/{code}', [ReferralController::class, 'track'])->name('referrals.track');

// Reports
    Route::resource('reports', ReportController::class, ['where' => ['report' => '[0-9]+']]);
    Route::get('reports/{report}/download', [ReportController::class, 'download'])->whereNumber('report')->name('reports.download');
    Route::post('reports/{report}/generate', [ReportController::class, 'generate'])->whereNumber('report')->name('reports.generate');
    Route::post('reports/agent-generate', [ReportController::class, 'generateWithAgent'])->name('reports.agent-generate');
    Route::get('reports/{report}/agent-recommendations', [ReportController::class, 'getAgentRecommendations'])->whereNumber('report')->name('reports.agent-recommendations');
    Route::post('reports/agent-schedule', [ReportController::class, 'scheduleAgentReport'])->name('reports.agent-schedule');

    // Email Campaigns
    Route::prefix('email')->name('email.')->group(function () {
        Route::get('/campaigns', [EmailCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/create', [EmailCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('/campaigns', [EmailCampaignController::class, 'store'])->name('campaigns.store');
        Route::get('/campaigns/{campaign}', [EmailCampaignController::class, 'show'])->name('campaigns.show');
        Route::get('/campaigns/{campaign}/edit', [EmailCampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('/campaigns/{campaign}', [EmailCampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('/campaigns/{campaign}', [EmailCampaignController::class, 'destroy'])->name('campaigns.destroy');
        Route::post('/campaigns/{campaign}/send', [EmailCampaignController::class, 'send'])->name('campaigns.send');
        Route::post('/campaigns/{campaign}/add-clients', [EmailCampaignController::class, 'addClients'])->name('campaigns.add-clients');
    });

    // Email Templates
    Route::prefix('templates')->name('email.templates.')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
        Route::get('/create', [EmailTemplateController::class, 'create'])->name('create');
        Route::post('/', [EmailTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [EmailTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [EmailTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [EmailTemplateController::class, 'destroy'])->name('destroy');
        Route::get('/{template}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
        Route::get('/{template}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('duplicate');
    });

    // White-Label
    Route::get('white-label', [WhiteLabelController::class, 'index'])->name('white-label.index');
    Route::post('white-label', [WhiteLabelController::class, 'update'])->name('white-label.update');
    Route::post('white-label/domain', [WhiteLabelController::class, 'setupDomain'])->name('white-label.domain');
    Route::post('white-label/validate-domain', [WhiteLabelController::class, 'validateDomain'])->name('white-label.validate-domain');

    // GDPR
    Route::prefix('gdpr')->name('gdpr.')->group(function () {
        Route::get('/', [GdprController::class, 'index'])->name('index');
        Route::post('/export', [GdprController::class, 'requestExport'])->name('export');
        Route::post('/delete', [GdprController::class, 'requestDeletion'])->name('delete');
        Route::post('/consent', [GdprController::class, 'updateConsent'])->name('consent');
    });

    // Billing & Subscription
    Route::get('agency/billing', [BillingController::class, 'index'])->name('agency.billing');
    Route::get('agency/billing/upgrade', [BillingController::class, 'upgrade'])->name('billing.upgrade');
    Route::get('agency/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('agency/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('agency/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('agency/billing/cancel-subscription', [BillingController::class, 'cancelSubscription'])->name('billing.cancel-subscription');
    Route::get('agency/invoices', [BillingController::class, 'invoices'])->name('agency.invoices');
    Route::get('agency/invoices/{invoice}/download', [BillingController::class, 'downloadInvoice'])->name('billing.invoice.download');
});

// Billing webhook (public - Stripe can't authenticate)
Route::post('billing/webhook', [BillingController::class, 'webhook'])->name('billing.webhook');

// Public client report (no auth required) - MUST be outside auth middleware
Route::get('r/{slug}/{token}', [PublicClientReportController::class, 'show'])
    ->name('public.client-report');

Route::middleware(['auth', 'agency'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // Onboarding (auth only — no agency required yet)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', function () {
            return view('onboarding');
        });

        Route::get('/step1', [OnboardingController::class, 'step1_createAgency'])->name('step1');
        Route::post('/step1', [OnboardingController::class, 'step1_createAgency'])->name('step1.post');
        Route::get('/step2', [OnboardingController::class, 'step2_connectSocial'])->name('step2');
        Route::post('/step2', [OnboardingController::class, 'step2_connectSocial'])->name('step2.post');
        Route::get('/step3', [OnboardingController::class, 'step3_inviteTeam'])->name('step3');
        Route::post('/step3', [OnboardingController::class, 'step3_inviteTeam'])->name('step3.post');
        Route::get('/step4', [OnboardingController::class, 'step4_createCampaign'])->name('step4');
        Route::post('/step4', [OnboardingController::class, 'step4_createCampaign'])->name('step4.post');
        Route::get('/step5', [OnboardingController::class, 'step5_activateAI'])->name('step5');
        Route::post('/step5', [OnboardingController::class, 'step5_activateAI'])->name('step5.post');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
        Route::post('/quick-start', [OnboardingController::class, 'quickStart'])->name('quickStart');
    });

    Route::prefix('agency')->name('agency.')->group(function () {
        Route::get('settings', [AgencyController::class, 'settings'])->name('settings');
        Route::put('settings', [AgencyController::class, 'updateSettings'])->name('settings.update');
        Route::get('team', [AgencyController::class, 'team'])->name('team');
        Route::post('team/invite', [AgencyController::class, 'inviteMember'])->name('team.invite');
        Route::put('team/{member}/role', [AgencyController::class, 'updateMemberRole'])->name('team.role');
        Route::delete('team/{member}', [AgencyController::class, 'removeMember'])->name('team.remove');
        Route::post('billing/upgrade', [AgencyController::class, 'upgrade'])->name('billing.upgrade');
    });

    // Enterprise RBAC
    Route::resource('roles', RoleController::class);
    Route::post('roles/assign', [RoleController::class, 'assign'])->name('roles.assign');
    Route::post('roles/remove', [RoleController::class, 'remove'])->name('roles.remove');
    Route::get('roles/audit', [RoleController::class, 'auditTrail'])->name('roles.audit');

});

// Version/Changelog (PUBLIC - no auth required)
require __DIR__.'/version.php';

// Telegram integration
require __DIR__.'/telegram.php';

// API Documentation (public)
require __DIR__.'/docs.php';

// Instagram integration
require __DIR__.'/instagram.php';

// Facebook integration
require __DIR__.'/facebook.php';

// LinkedIn integration
require __DIR__.'/linkedin.php';

// TikTok integration
require __DIR__.'/tiktok.php';

// Pinterest integration
require __DIR__.'/pinterest.php';

// YouTube integration
require __DIR__.'/youtube.php';

// Unified inbox
require __DIR__.'/unified-inbox.php';

// Platform webhooks
require __DIR__.'/webhooks.php';

// Admin dashboard
require __DIR__.'/admin.php';

// security.txt (RFC 9116)
Route::get('/.well-known/security.txt', function () {
    $expiry = now()->addYear()->toIso8601String();
    $content = "Contact: mailto:security@digitalmarketingsaas.com\n";
    $content .= "Expires: {$expiry}\n";
    $content .= "Preferred-Languages: en\n";
    $content .= "Canonical: https://digitalmarketingsaas.com/.well-known/security.txt\n";

    return response($content, 200, [
        'Content-Type' => 'text/plain; charset=utf-8',
    ]);
})->name('security.txt');

// Health Checks (public)
Route::get('/health', [HealthCheckController::class, 'index'])->name('health');
Route::get('/ready', [HealthCheckController::class, 'readiness'])->name('ready');
Route::get('/live', [HealthCheckController::class, 'liveness'])->name('live');
Route::get('/status', [HealthCheckController::class, 'status'])->name('status');
Route::get('/disk-space', [HealthCheckController::class, 'diskSpace'])->name('disk-space');
Route::get('/queue-status', [HealthCheckController::class, 'queueStatus'])->name('queue-status');

// Client Approval Workflow
Route::middleware(['auth', 'agency'])->prefix('approvals')->name('approvals.')->group(function () {
    Route::post('/posts/{post}/submit', [ApprovalController::class, 'submit'])->name('submit');
    Route::post('/posts/{post}/approve', [ApprovalController::class, 'approve'])->name('approve');
    Route::post('/posts/{post}/reject', [ApprovalController::class, 'reject'])->name('reject');
    Route::get('/pending', [ApprovalController::class, 'pending'])->name('pending');
    Route::get('/client/{client}/posts', [ApprovalController::class, 'clientPosts'])->name('client.posts');
});

// Client Portal (agency admin settings)
Route::middleware(['auth', 'agency'])->prefix('client-portal')->name('client-portal.')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\ClientPortalController::class, 'settings'])->name('settings');
    Route::put('/settings', [\App\Http\Controllers\ClientPortalController::class, 'updateSettings'])->name('settings.update');
    Route::post('/clients/{client}/generate-token', [\App\Http\Controllers\ClientPortalController::class, 'generateToken'])->name('generate-token');
    Route::delete('/clients/{client}/tokens/{accessToken}', [\App\Http\Controllers\ClientPortalController::class, 'revokeToken'])->name('revoke-token');
});

// Public client portal (token-based access, no auth required)
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\ClientPortalController::class, 'show'])->name('show');
    Route::post('/{token}/posts/{post}/approve', [\App\Http\Controllers\ClientPortalController::class, 'approvePost'])->name('approve-post');
    Route::post('/{token}/posts/{post}/reject', [\App\Http\Controllers\ClientPortalController::class, 'rejectPost'])->name('reject-post');
});

// Team Chat
Route::middleware(['auth', 'agency'])->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ChatController::class, 'index'])->name('index');
    Route::get('/{channel}', [\App\Http\Controllers\ChatController::class, 'show'])->name('show');
});
