<?php

namespace App\Listeners\Agent;

use App\Events\SubscriptionUpgraded;
use App\Models\ActivityFeed;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Support\Facades\Log;

class SubscriptionUpgradedAgentListener
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator
    ) {}

    public function handle(SubscriptionUpgraded $event): void
    {
        $agency = $event->agency;
        $context = AgentContext::fromAgency($agency);

        // Dispatch AnalyticsAgent for feature recommendations
        $recommendationTask = new AgentTask(
            id: "subscription_upgraded_recommend_{$agency->id}",
            type: 'recommendation',
            prompt: "Suggest new features for agency upgraded from {$event->oldPlan} to {$event->newPlan}",
            data: [
                'platform' => 'instagram',
                'goals' => ['engagement', 'reach'],
                'old_plan' => $event->oldPlan,
                'new_plan' => $event->newPlan,
            ],
            preferredAgent: 'analytics_agent',
        );

        $result = $this->orchestrator->dispatch($recommendationTask, $context);
        Log::info('SubscriptionUpgradedAgentListener: recommendation dispatched', [
            'agency_id' => $agency->id,
            'old_plan' => $event->oldPlan,
            'new_plan' => $event->newPlan,
            'success' => $result->success,
        ]);

        // Log upgrade activity
        ActivityFeed::create([
            'agency_id' => $agency->id,
            'user_id' => null,
            'action' => 'workflow_executed',
            'subject_type' => Agency::class,
            'subject_id' => $agency->id,
            'metadata' => [
                'event' => 'subscription_upgraded',
                'old_plan' => $event->oldPlan,
                'new_plan' => $event->newPlan,
                'agent_name' => $result->agentName,
                'success' => $result->success,
                'cost_usd' => $result->costUsd,
            ],
        ]);
    }
}
