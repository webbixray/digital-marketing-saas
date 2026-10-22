<?php

namespace App\Providers;

use App\Models\Agency;
use App\Models\ActivityLog;
use App\Models\AiProviderKey;
use App\Models\BulkSchedule;
use App\Models\Campaign;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\Client;
use App\Models\ContentTemplate;
use App\Models\CustomField;
use App\Models\EmailCampaign;
use App\Models\Feature;
use App\Models\Invoice;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\Report;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\Team;
use App\Models\Workflow;
use App\Policies\ActivityLogPolicy;
use App\Policies\AgencyPolicy;
use App\Policies\AiProviderKeyPolicy;
use App\Policies\BulkSchedulePolicy;
use App\Policies\CampaignPolicy;
use App\Policies\ChatChannelPolicy;
use App\Policies\ChatMessagePolicy;
use App\Policies\ClientPolicy;
use App\Policies\ContentTemplatePolicy;
use App\Policies\CustomFieldPolicy;
use App\Policies\EmailCampaignPolicy;
use App\Policies\FeaturePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LandingPagePolicy;
use App\Policies\ProductPolicy;
use App\Policies\ReportPolicy;
use App\Policies\SocialAccountPolicy;
use App\Policies\SocialPostPolicy;
use App\Policies\TeamPolicy;
use App\Policies\WorkflowPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Agency::class => AgencyPolicy::class,
        ActivityLog::class => ActivityLogPolicy::class,
        AiProviderKey::class => AiProviderKeyPolicy::class,
        BulkSchedule::class => BulkSchedulePolicy::class,
        Campaign::class => CampaignPolicy::class,
        ChatChannel::class => ChatChannelPolicy::class,
        ChatMessage::class => ChatMessagePolicy::class,
        Client::class => ClientPolicy::class,
        ContentTemplate::class => ContentTemplatePolicy::class,
        CustomField::class => CustomFieldPolicy::class,
        EmailCampaign::class => EmailCampaignPolicy::class,
        Feature::class => FeaturePolicy::class,
        Invoice::class => InvoicePolicy::class,
        LandingPage::class => LandingPagePolicy::class,
        Product::class => ProductPolicy::class,
        Report::class => ReportPolicy::class,
        SocialAccount::class => SocialAccountPolicy::class,
        SocialPost::class => SocialPostPolicy::class,
        Team::class => TeamPolicy::class,
        Workflow::class => WorkflowPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
