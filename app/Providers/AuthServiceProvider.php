<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\BulkSchedule;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\EmailCampaign;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\Workflow;
use App\Policies\AgencyPolicy;
use App\Policies\BulkSchedulePolicy;
use App\Policies\CampaignPolicy;
use App\Policies\ClientPolicy;
use App\Policies\EmailCampaignPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\SocialAccountPolicy;
use App\Policies\SocialPostPolicy;
use App\Policies\WorkflowPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Agency::class => AgencyPolicy::class,
        BulkSchedule::class => BulkSchedulePolicy::class,
        Campaign::class => CampaignPolicy::class,
        Client::class => ClientPolicy::class,
        EmailCampaign::class => EmailCampaignPolicy::class,
        Invoice::class => InvoicePolicy::class,
        SocialAccount::class => SocialAccountPolicy::class,
        SocialPost::class => SocialPostPolicy::class,
        Workflow::class => WorkflowPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
