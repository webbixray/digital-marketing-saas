<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiWebhookController extends Controller
{
    /**
     * List all webhooks for the agency
     */
    public function index(): JsonResponse
    {
        $webhooks = Webhook::forCurrentAgency()
            ->with(['logs' => fn($q) => $q->latest()->limit(5)])
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'url' => $w->url,
                'events' => $w->events,
                'is_active' => $w->is_active,
                'total_calls' => $w->total_calls,
                'failed_calls' => $w->failed_calls,
                'last_triggered_at' => $w->last_triggered_at?->toISOString(),
                'created_at' => $w->created_at->toISOString(),
                'recent_deliveries' => $w->logs->map(fn($d) => [
                    'id' => $d->id,
                    'event_type' => $d->event_type,
                    'status' => $d->status,
                    'response_code' => $d->response_code,
                    'attempts' => $d->attempts,
                    'delivered_at' => $d->delivered_at?->toISOString(),
                    'created_at' => $d->created_at->toISOString(),
                ]),
            ]);

        return response()->json([
            'webhooks' => $webhooks,
            'available_events' => Webhook::$availableEvents,
        ]);
    }

    /**
     * Get a single webhook
     */
    public function show(Webhook $webhook): JsonResponse
    {
        $this->authorize('view', $webhook);

        return response()->json([
            'webhook' => $webhook,
            'available_events' => Webhook::$availableEvents,
        ]);
    }

    /**
     * Create a new webhook
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => 'in:' . implode(',', array_keys(Webhook::$availableEvents)),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $webhook = Webhook::create([
            'agency_id' => auth()->user()->agency_id,
            'name' => $request->name,
            'url' => $request->url,
            'secret' => Str::random(40),
            'events' => $request->events,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'webhook' => $webhook,
            'message' => 'Webhook created successfully',
        ], 201);
    }

    /**
     * Update a webhook
     */
    public function update(Request $request, Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|max:500',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'in:' . implode(',', array_keys(Webhook::$availableEvents)),
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $webhook->update($request->only(['name', 'url', 'events', 'is_active']));

        return response()->json([
            'success' => true,
            'webhook' => $webhook,
            'message' => 'Webhook updated successfully',
        ]);
    }

    /**
     * Delete a webhook
     */
    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->authorize('delete', $webhook);

        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Regenerate webhook secret
     */
    public function regenerateSecret(Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $webhook->update(['secret' => Str::random(40)]);

        return response()->json([
            'success' => true,
            'secret' => $webhook->secret,
            'message' => 'Secret regenerated successfully',
        ]);
    }

    /**
     * Get delivery logs for a webhook
     */
    public function deliveries(Webhook $webhook): JsonResponse
    {
        $this->authorize('view', $webhook);

        $deliveries = WebhookDelivery::where('webhook_id', $webhook->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($deliveries);
    }

    /**
     * Test a webhook (send a test event)
     */
    public function test(Webhook $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        // Dispatch test event
        $payload = [
            'event' => 'webhook.test',
            'timestamp' => now()->toISOString(),
            'data' => [
                'message' => 'This is a test event from DigitalMarketingSaaS',
            ],
        ];

        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => 'webhook.test',
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Dispatch job to send webhook
        \App\Jobs\SendWebhook::dispatch($webhook, $delivery);

        return response()->json([
            'success' => true,
            'delivery' => $delivery,
            'message' => 'Test event queued for delivery',
        ]);
    }

    /**
     * Get available webhook events
     */
    public function availableEvents(): JsonResponse
    {
        return response()->json([
            'events' => Webhook::$availableEvents,
        ]);
    }
}
