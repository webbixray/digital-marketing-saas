<?php

namespace App\Listeners\Agent;

use App\Events\CampaignStatusChanged;
use App\Models\ActivityFeed;
use App\Models\Agency;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Support\Facades\Log;

class CampaignStatusChangedAgentListener
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator
    ) {}

    public function handle(CampaignStatusChanged $event): void
    {
        $campaign = $event->campaign;
        $agencyId = $campaign->agency_id;

        if (! $agencyId) {
            Log::debug('CampaignStatusChangedAgentListener: no agency_id on campaign, skipping');

            return;
        }

        $agency = Agency::find($agencyId);

        if (! $agency) {
            Log::debug("CampaignStatusChangedAgentListener: agency [{$agencyId}] not found, skipping");

            return;
        }

        $context = AgentContext::fromAgency($agency);
        $result = null;
        $taskType = null;

        if ($event->newStatus === 'completed') {
            $taskType = 'campaign_optimize';
            $task = new AgentTask(
                id: "campaign_completed_optimize_{$campaign->id}",
                type: 'campaign_optimize',
                prompt: 'Analyze completed campaign and provide optimization insights',
                data: [
                    'campaign_id' => $campaign->id,
                    'goals' => ['engagement', 'reach'],
                ],
                preferredAgent: 'campaign_agent',
            );

            $result = $this->orchestrator->dispatch($task, $context);
            Log::info('CampaignStatusChangedAgentListener: campaign_optimize dispatched', [
                'campaign_id' => $campaign->id,
                'success' => $result->success,
            ]);
        } elseif ($event->newStatus === 'active') {
            $taskType = 'budget_allocate';
            $task = new AgentTask(
                id: "campaign_active_budget_{$campaign->id}",
                type: 'budget_allocate',
                prompt: 'Allocate budget across platforms for active campaign',
                data: [
                    'campaign_id' => $campaign->id,
                    'total_budget' => $campaign->budget ?? 0,
                    'platforms' => ['instagram', 'facebook'],
                ],
                preferredAgent: 'campaign_agent',
            );

            $result = $this->orchestrator->dispatch($task, $context);
            Log::info('CampaignStatusChangedAgentListener: budget_allocate dispatched', [
                'campaign_id' => $campaign->id,
                'success' => $result->success,
            ]);
        }

        // Log results to activity feed
        if ($result && $taskType) {
            ActivityFeed::create([
                'agency_id' => $agencyId,
                'user_id' => null,
                'action' => 'workflow_executed',
                'subject_type' => Campaign::class,
                'subject_id' => $campaign->id,
                'metadata' => [
                    'task_type' => $taskType,
                    'agent_name' => $result->agentName,
                    'success' => $result->success,
                    'cost_usd' => $result->costUsd,
                    'old_status' => $event->oldStatus,
                    'new_status' => $event->newStatus,
                ],
            ]);
        }
    }
}
