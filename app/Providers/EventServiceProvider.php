<?php

namespace App\Providers;

use App\Events\AgentWorkflowCompleted;
use App\Events\CampaignStatusChanged;
use App\Events\ClientCreated;
use App\Events\InvoicePaid;
use App\Events\PostFailed;
use App\Events\PostPublished;
use App\Events\PostScheduled;
use App\Events\SubscriptionUpgraded;
use App\Events\WebhookReceived;
use App\Listeners\Agent\CampaignStatusChangedAgentListener;
use App\Listeners\Agent\ClientCreatedAgentListener;
use App\Listeners\Agent\PostPublishedAgentListener;
use App\Listeners\Agent\SubscriptionUpgradedAgentListener;
use App\Listeners\Billing\LogInvoiceActivity;
use App\Listeners\Billing\LogSubscriptionUpgrade;
use App\Listeners\HandlePostFailure;
use App\Listeners\LogWebhookAttempt;
use App\Listeners\SendWorkflowNotificationListener;
use App\Listeners\Social\ClearPostCache;
use App\Listeners\Social\LogPostActivity;
use App\Listeners\Social\SendPostNotification;
use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\Workflow;
use App\Observers\AuditObserver;
use App\Policies\AgencyPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\ClientPolicy;
use App\Policies\EmailCampaignPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\SocialAccountPolicy;
use App\Policies\SocialPostPolicy;
use App\Policies\WorkflowPolicy;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        AgentWorkflowCompleted::class => [
            SendWorkflowNotificationListener::class,
        ],
        CampaignStatusChanged::class => [
            CampaignStatusChangedAgentListener::class,
        ],
        ClientCreated::class => [
            ClientCreatedAgentListener::class,
        ],
        InvoicePaid::class => [
            LogInvoiceActivity::class.'@handle',
        ],
        PostFailed::class => [
            ClearPostCache::class.'@handlePostFailed',
            LogPostActivity::class.'@handlePostFailed',
            SendPostNotification::class.'@handlePostFailed',
            HandlePostFailure::class,
        ],
        PostPublished::class => [
            ClearPostCache::class.'@handlePostPublished',
            LogPostActivity::class.'@handlePostPublished',
            SendPostNotification::class.'@handlePostPublished',
            PostPublishedAgentListener::class,
        ],
        PostScheduled::class => [
            ClearPostCache::class.'@handlePostScheduled',
            LogPostActivity::class.'@handlePostScheduled',
        ],
        SubscriptionUpgraded::class => [
            LogSubscriptionUpgrade::class.'@handle',
            SubscriptionUpgradedAgentListener::class,
        ],
        WebhookReceived::class => [
            LogWebhookAttempt::class,
        ],
    ];

    public function boot(): void
    {
        // Register audit observer for models with agency_id
        Campaign::observe(AuditObserver::class);
        Client::observe(AuditObserver::class);
        Invoice::observe(AuditObserver::class);
        SocialPost::observe(AuditObserver::class);
        Workflow::observe(AuditObserver::class);
        EmailCampaign::observe(AuditObserver::class);

        // Register policies
        Gate::policy(Agency::class, AgencyPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(ClientReport::class, ClientReportPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(SocialPost::class, SocialPostPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(EmailCampaign::class, EmailCampaignPolicy::class);
        Gate::policy(SocialAccount::class, SocialAccountPolicy::class);
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
