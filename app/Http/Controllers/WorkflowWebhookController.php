<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\WorkflowWebhookLog;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkflowWebhookController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
    ) {}

    /**
     * Handle incoming webhook for a workflow.
     */
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

        // Log the webhook
        $log = WorkflowWebhookLog::create([
            'workflow_id' => $workflow->id,
            'event_type' => $request->input('event', 'unknown'),
            'payload' => $request->all(),
            'ip_address' => $request->ip(),
            'status' => 'received',
        ]);

        try {
            // Execute the workflow
            $engine = $this->engine;
            $execution = $engine->execute($workflow, $request->all());

            $log->markAsProcessed('Workflow executed successfully');

            return response()->json([
                'success' => true,
                'message' => 'Workflow triggered successfully',
                'execution_id' => $execution->id,
            ]);
        } catch (\Exception $e) {
            $log->markAsProcessed('Failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Workflow execution failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
