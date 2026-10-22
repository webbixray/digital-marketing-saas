<?php

namespace App\Listeners\Agent;

use App\Events\ClientCreated;
use App\Models\Agency;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Support\Facades\Log;

class ClientCreatedAgentListener
{
    public function __construct(
        private readonly AgentOrchestrator $orchestrator
    ) {}

    public function handle(ClientCreated $event): void
    {
        $client = $event->client;
        $agencyId = $client->agency_id;

        if (! $agencyId) {
            Log::debug('ClientCreatedAgentListener: no agency_id on client, skipping');

            return;
        }

        $agency = Agency::find($agencyId);

        if (! $agency) {
            Log::debug("ClientCreatedAgentListener: agency [{$agencyId}] not found, skipping");

            return;
        }

        $context = AgentContext::fromAgency($agency);

        // Dispatch SupportAgent for welcome message response suggestion
        $welcomeTask = new AgentTask(
            id: "client_created_welcome_{$client->id}",
            type: 'response_suggest',
            prompt: "Generate a welcome message for new client: {$client->name}",
            data: [
                'message' => "Welcome {$client->name}! We're excited to work with you.",
                'subject' => 'Welcome to our agency',
                'category' => 'onboarding',
                'tone' => 'friendly',
            ],
            preferredAgent: 'support_agent',
        );

        $welcomeResult = $this->orchestrator->dispatch($welcomeTask, $context);
        Log::info('ClientCreatedAgentListener: response_suggest dispatched for welcome', [
            'client_id' => $client->id,
            'success' => $welcomeResult->success,
        ]);

        // Dispatch ContentAgent for onboarding content generation
        $onboardingTask = new AgentTask(
            id: "client_created_onboarding_{$client->id}",
            type: 'content_generate',
            prompt: "Generate onboarding content for new client: {$client->name}",
            data: [
                'content_type' => 'onboarding_email',
                'platform' => 'email',
                'tone' => 'professional',
                'client_name' => $client->name,
            ],
            preferredAgent: 'content_agent',
        );

        $onboardingResult = $this->orchestrator->dispatch($onboardingTask, $context);
        Log::info('ClientCreatedAgentListener: content_generate dispatched for onboarding', [
            'client_id' => $client->id,
            'success' => $onboardingResult->success,
        ]);
    }
}
