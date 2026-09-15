<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\Invoice;
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
        \App\Events\AgentWorkflowCompleted::class => [
            \App\Listeners\SendWorkflowNotificationListener::class,
        ],
        \App\Events\CampaignStatusChanged::class => [
            \App\Listeners\Agent\CampaignStatusChangedAgentListener::class,
        ],
        \App\Events\ClientCreated::class => [
            \App\Listeners\Agent\ClientCreatedAgentListener::class,
        ],
        \App\Events\InvoicePaid::class => [
            \App\Listeners\Billing\LogInvoiceActivity::class.'@handle',
        ],
        \App\Events\PostFailed::class => [
            \App\Listeners\Social\ClearPostCache::class.'@handlePostFailed',
            \App\Listeners\Social\LogPostActivity::class.'@handlePostFailed',
            \App\Listeners\Social\SendPostNotification::class.'@handlePostFailed',
            \App\Listeners\HandlePostFailure::class,
        ],
        \App\Events\PostPublished::class => [
            \App\Listeners\Social\ClearPostCache::class.'@handlePostPublished',
            \App\Listeners\Social\LogPostActivity::class.'@handlePostPublished',
            \App\Listeners\Social\SendPostNotification::class.'@handlePostPublished',
            \App\Listeners\Agent\PostPublishedAgentListener::class,
        ],
        \App\Events\PostScheduled::class => [
            \App\Listeners\Social\ClearPostCache::class.'@handlePostScheduled',
            \App\Listeners\Social\LogPostActivity::class.'@handlePostScheduled',
        ],
        \App\Events\SubscriptionUpgraded::class => [
            \App\Listeners\Billing\LogSubscriptionUpgrade::class.'@handle',
            \App\Listeners\Agent\SubscriptionUpgradedAgentListener::class,
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
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(SocialPost::class, SocialPostPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(EmailCampaign::class, EmailCampaignPolicy::class);
        Gate::policy(\App\Models\SocialAccount::class, SocialAccountPolicy::class);
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
