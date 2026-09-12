<?php

namespace App\Listeners\Agent;

use App\Events\PostPublished;
use App\Models\Agency;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Support\Facades\Log;

class PostPublishedAgentListener
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator
    ) {}

    public function handle(PostPublished $event): void
    {
        $post = $event->post;
        $agencyId = $post->agency_id;

        if (! $agencyId) {
            Log::debug('PostPublishedAgentListener: no agency_id on post, skipping');

            return;
        }

        $agency = Agency::find($agencyId);

        if (! $agency) {
            Log::debug("PostPublishedAgentListener: agency [{$agencyId}] not found, skipping");

            return;
        }

        // Only run if agency has 'workflow_engine' feature enabled
        if (! $agency->isFeatureAvailable('workflow_engine')) {
            Log::debug("PostPublishedAgentListener: workflow_engine not available for agency [{$agencyId}], skipping");

            return;
        }

        $context = AgentContext::fromAgency($agency);

        // Dispatch to SocialMediaAgent for engagement_analysis
        $engagementTask = new AgentTask(
            id: "post_published_engagement_{$post->id}",
            type: 'engagement_analysis',
            prompt: "Analyze engagement for post: {$post->content}",
            data: [
                'platform' => $post->platform,
                'post_id' => $post->id,
                'date_range' => '30 days',
            ],
            preferredAgent: 'social_media_agent',
        );

        $engagementResult = $this->orchestrator->dispatch($engagementTask, $context);
        Log::info('PostPublishedAgentListener: engagement_analysis dispatched', [
            'post_id' => $post->id,
            'success' => $engagementResult->success,
        ]);

        // Dispatch to AnalyticsAgent for trend_detection
        $trendTask = new AgentTask(
            id: "post_published_trend_{$post->id}",
            type: 'trend_detection',
            prompt: 'Detect trends based on recent post performance',
            data: [
                'platform' => $post->platform,
                'niche' => 'general',
            ],
            preferredAgent: 'analytics_agent',
        );

        $trendResult = $this->orchestrator->dispatch($trendTask, $context);
        Log::info('PostPublishedAgentListener: trend_detection dispatched', [
            'post_id' => $post->id,
            'success' => $trendResult->success,
        ]);

        // Dispatch to ContentAgent for content_optimize
        $optimizeTask = new AgentTask(
            id: "post_published_optimize_{$post->id}",
            type: 'content_optimize',
            prompt: 'Optimize content for better engagement',
            data: [
                'content' => $post->content,
                'platform' => $post->platform,
                'goals' => ['engagement'],
            ],
            preferredAgent: 'content_agent',
        );

        $optimizeResult = $this->orchestrator->dispatch($optimizeTask, $context);
        Log::info('PostPublishedAgentListener: content_optimize dispatched', [
            'post_id' => $post->id,
            'success' => $optimizeResult->success,
        ]);
    }
}
