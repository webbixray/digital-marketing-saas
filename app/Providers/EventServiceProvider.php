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
use App\Listeners\Agent\CampaignStatusChangedAgentListener;
use App\Listeners\Agent\ClientCreatedAgentListener;
use App\Listeners\Agent\PostPublishedAgentListener;
use App\Listeners\Agent\SubscriptionUpgradedAgentListener;
use App\Listeners\Billing\LogInvoiceActivity;
use App\Listeners\Billing\LogSubscriptionUpgrade;
use App\Listeners\HandlePostFailure;
use App\Listeners\SendWorkflowNotificationListener;
use App\Listeners\Social\ClearPostCache;
use App\Listeners\Social\LogPostActivity;
use App\Listeners\Social\SendPostNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
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
        PostFailed::class => [
            ClearPostCache::class.'@handlePostFailed',
            LogPostActivity::class.'@handlePostFailed',
            SendPostNotification::class.'@handlePostFailed',
            HandlePostFailure::class,
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
        SubscriptionUpgraded::class => [
            LogSubscriptionUpgrade::class.'@handle',
            SubscriptionUpgradedAgentListener::class,
        ],
        AgentWorkflowCompleted::class => [
            SendWorkflowNotificationListener::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
