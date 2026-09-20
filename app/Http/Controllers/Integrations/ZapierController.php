<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\ZapierSubscription;
use App\Services\Integrations\ZapierIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZapierController extends Controller
{
    public function __construct(private ZapierIntegrationService $zapierService) {}

    /**
     * GET /api/v1/integrations/zapier/triggers
     * List all available triggers.
     */
    public function triggers(): JsonResponse
    {
        $triggers = $this->zapierService->getTriggers();

        return response()->json([
            'success' => true,
            'data' => $triggers,
            'count' => count($triggers),
        ]);
    }

    /**
     * GET /api/v1/integrations/zapier/actions
     * List all available actions.
     */
    public function actions(): JsonResponse
    {
        $actions = $this->zapierService->getActions();

        return response()->json([
            'success' => true,
            'data' => $actions,
            'count' => count($actions),
        ]);
    }

    /**
     * POST /api/v1/integrations/zapier/actions/execute
     * Execute a specific action.
     */
    public function executeAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string',
            'data' => 'required|array',
        ]);

        $agencyId = $request->user()->agency_id;
        $data = array_merge($validated['data'], ['agency_id' => $agencyId]);

        $result = $this->zapierService->executeAction($validated['action'], $data);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * GET /api/v1/integrations/zapier/triggers/{trigger}/data
     * Fetch recent data for a specific trigger.
     */
    public function getTriggerData(Request $request, string $trigger): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $data = $this->zapierService->getTriggerData($trigger, $agencyId);

        return response()->json([
            'success' => true,
            'trigger' => $trigger,
            'data' => $data,
            'count' => count($data),
        ]);
    }

    /**
     * POST /api/v1/integrations/zapier/subscribe
     * Register a webhook URL for a trigger.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'webhook_url' => 'required|url',
            'trigger_type' => 'required|string',
        ]);

        $agencyId = $request->user()->agency_id;

        $subscription = ZapierSubscription::create([
            'agency_id' => $agencyId,
            'webhook_url' => $validated['webhook_url'],
            'trigger_type' => $validated['trigger_type'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook subscription created successfully',
            'data' => $subscription,
        ], 201);
    }

    /**
     * POST /api/v1/integrations/zapier/unsubscribe
     * Remove a webhook subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'webhook_url' => 'required|url',
            'trigger_type' => 'required|string',
        ]);

        $agencyId = $request->user()->agency_id;

        $deleted = ZapierSubscription::where('agency_id', $agencyId)
            ->where('webhook_url', $validated['webhook_url'])
            ->where('trigger_type', $validated['trigger_type'])
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook subscription removed successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Subscription not found',
        ], 404);
    }
}
