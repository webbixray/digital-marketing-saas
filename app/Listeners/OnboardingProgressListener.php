<?php

namespace App\Listeners;

use App\Events\AiGenerationCompleted;
use App\Events\CampaignCreated;
use App\Events\PostPublished;
use App\Events\SocialAccountCreated;
use App\Events\TeamMemberInvited;
use App\Services\OnboardingEngine;
use Illuminate\Support\Facades\Log;

class OnboardingProgressListener
{
    public function __construct(
        private OnboardingEngine $engine
    ) {}

    /**
     * Handle social account connected event
     */
    public function handleSocialAccountConnected($event): void
    {
        $agency = $event->socialAccount->agency ?? null;
        if ($agency) {
            $this->engine->completeStep($agency, 'social_connected');
            Log::info("Onboarding: social_connected completed for agency {$agency->id}");
        }
    }

    /**
     * Handle team member invited event
     */
    public function handleTeamMemberInvited($event): void
    {
        $agency = $event->agency ?? null;
        if ($agency) {
            $this->engine->completeStep($agency, 'team_invited');
            Log::info("Onboarding: team_invited completed for agency {$agency->id}");
        }
    }

    /**
     * Handle campaign created event
     */
    public function handleCampaignCreated($event): void
    {
        $campaign = $event->campaign ?? null;
        $agency = $campaign->agency ?? null;
        if ($agency) {
            $this->engine->completeStep($agency, 'campaign_created');
            Log::info("Onboarding: campaign_created completed for agency {$agency->id}");
        }
    }

    /**
     * Handle post published event
     */
    public function handlePostPublished($event): void
    {
        $post = $event->post ?? null;
        $agency = $post->agency ?? null;
        if ($agency) {
            $this->engine->completeStep($agency, 'post_published');
            Log::info("Onboarding: post_published completed for agency {$agency->id}");
        }
    }

    /**
     * Handle AI generation event
     */
    public function handleAiGenerated($event): void
    {
        $agency = $event->agency ?? null;
        if ($agency) {
            $this->engine->completeStep($agency, 'ai_activated');
            Log::info("Onboarding: ai_activated completed for agency {$agency->id}");
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            SocialAccountCreated::class => 'handleSocialAccountConnected',
            TeamMemberInvited::class => 'handleTeamMemberInvited',
            CampaignCreated::class => 'handleCampaignCreated',
            PostPublished::class => 'handlePostPublished',
            AiGenerationCompleted::class => 'handleAiGenerated',
        ];
    }
}
