<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowWebhookLog;
use App\Services\Webhooks\WebhookProcessor;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkflowWebhookController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
        private readonly WebhookProcessor $processor,
    ) {}

    public function handle(Request $request, string $workflowId, string $secret)
    {
        $workflow = Workflow::findOrFail($workflowId);

        // Validate secret
        if ($workflow->webhook_secret !== $secret) {
            return response()->json(['error' => 'Invalid webhook secret'], 401);
        }

        // Check if workflow is active
        if ($workflow->status !== 'active') {
            return response()->json(['error' => 'Workflow is not active'], 400);
        }

        $payload = $request->all();

        // Process through WebhookProcessor
        $log = $this->processor->process(
            platform: 'workflow',
            eventType: $payload['event'] ?? 'unknown',
            payload: $payload,
            signature: null,
            handler: function (array $payload) use ($workflow) {
                $engine = $this->engine;
                $execution = $engine->execute($workflow, $payload);
                return $execution;
            },
        );

        // Also log to workflow-specific log for backward compatibility
        $wfLog = WorkflowWebhookLog::create([
            'workflow_id' => $workflow->id,
            'event_type' => $payload['event'] ?? 'unknown',
            'payload' => $payload,
            'ip_address' => $request->ip(),
            'status' => 'received',
        ]);

        try {
            $wfLog->markAsProcessed('Workflow executed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Workflow triggered successfully',
                'execution_id' => $log->id,
            ]);
        } catch (\Exception $e) {
            $wfLog->markAsProcessed('Failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Workflow execution failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
