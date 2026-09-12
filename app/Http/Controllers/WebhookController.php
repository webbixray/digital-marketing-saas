<?php

namespace App\Http\Controllers;

use App\Concerns\StructuredLogger;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebhookController extends Controller
{
    use StructuredLogger;

    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        try {
            $agencyId = $request->user()->agency_id;
            $webhooks = Webhook::where('agency_id', $agencyId)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return view('webhooks.index', compact('agencyId', 'webhooks'));
        } catch (\Exception $e) {
            Log::error('Failed to load webhooks', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load webhooks. Please try again.');
        }
    }

    public function create(Request $request)
    {
        try {
            $agencyId = $request->user()->agency_id;
            $events = Webhook::$availableEvents;

            return view('webhooks.create', compact('agencyId', 'events'));
        } catch (\Exception $e) {
            Log::error('Failed to load webhook create form', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    public function store(Request $request)
    {
        try {
            $agencyId = $request->user()->agency_id;

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'required|url',
                'events' => 'required|array|min:1',
                'events.*' => 'in:'.implode(',', array_keys(Webhook::$availableEvents)),
                'is_active' => 'boolean',
            ]);

            $webhook = Webhook::create([
                'agency_id' => $agencyId,
                'name' => $validated['name'],
                'url' => $validated['url'],
                'events' => $validated['events'],
                'secret' => Str::random(40),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::info('Webhook created', [
                'webhook_id' => $webhook->id,
                'agency_id' => $agencyId,
            ]);

            return redirect()->route('webhooks.show', $webhook)
                ->with('success', 'Webhook created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create webhook', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to create webhook. Please try again.');
        }
    }

    public function show(Request $request, $webhookId)
    {
        $agencyId = $request->user()->agency_id;

        $webhook = Webhook::findOrFail($webhookId);

        if ($webhook->agency_id !== $agencyId) {
            $this->logSecurity('unauthorized_webhook_access', [
                'agency_id' => $agencyId,
                'webhook_id' => $webhookId,
                'webhook_agency_id' => $webhook->agency_id,
            ]);

            abort(403);
        }

        try {
            $logs = $webhook->logs()->orderBy('created_at', 'desc')->paginate(25);

            return view('webhooks.show', compact('agencyId', 'webhook', 'logs'));
        } catch (\Exception $e) {
            Log::error('Failed to load webhook details', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load webhook details.');
        }
    }

    public function edit(Request $request, $webhookId)
    {
        $agencyId = $request->user()->agency_id;

        $webhook = Webhook::findOrFail($webhookId);

        if ($webhook->agency_id !== $agencyId) {
            $this->logSecurity('unauthorized_webhook_edit', [
                'agency_id' => $agencyId,
                'webhook_id' => $webhookId,
            ]);

            abort(403);
        }

        try {
            $events = Webhook::$availableEvents;

            return view('webhooks.edit', compact('agencyId', 'webhook', 'events'));
        } catch (\Exception $e) {
            Log::error('Failed to load webhook edit form', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load webhook details.');
        }
    }

    public function update(Request $request, $webhookId)
    {
        $agencyId = $request->user()->agency_id;

        $webhook = Webhook::findOrFail($webhookId);

        if ($webhook->agency_id !== $agencyId) {
            $this->logSecurity('unauthorized_webhook_update', [
                'agency_id' => $agencyId,
                'webhook_id' => $webhookId,
            ]);

            abort(403);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'url' => 'required|url',
                'events' => 'required|array|min:1',
                'is_active' => 'boolean',
            ]);

            $webhook->update($validated);

            return redirect()->route('webhooks.show', $webhook)
                ->with('success', 'Webhook updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update webhook.');
        }
    }

    public function destroy(Request $request, $webhookId)
    {
        $agencyId = $request->user()->agency_id;

        $webhook = Webhook::findOrFail($webhookId);

        if ($webhook->agency_id !== $agencyId) {
            $this->logSecurity('unauthorized_webhook_delete', [
                'agency_id' => $agencyId,
                'webhook_id' => $webhookId,
            ]);

            abort(403);
        }

        try {
            $webhook->delete();

            return redirect()->route('webhooks.index')
                ->with('success', 'Webhook deleted.');
        } catch (\Exception $e) {
            Log::error('Failed to delete webhook', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete webhook.');
        }
    }

    /**
     * Trigger a webhook for a specific event.
     */
    public function trigger(string $event, array $payload = []): void
    {
        try {
            $webhooks = Webhook::whereJsonContains('events', $event)
                ->where('is_active', true)
                ->get();

            foreach ($webhooks as $webhook) {
                $this->dispatchWebhook($webhook, $event, $payload);
            }
        } catch (\Exception $e) {
            Log::error('Failed to trigger webhooks', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch webhook to endpoint.
     */
    protected function dispatchWebhook(Webhook $webhook, string $event, array $payload): void
    {
        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => $webhook->content_type,
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Signature' => $this->signPayload($payload, $webhook->secret),
                ])
                ->post($webhook->url, [
                    'event' => $event,
                    'data' => $payload,
                    'timestamp' => now()->toISOString(),
                ]);

            WebhookLog::create([
                'webhook_id' => $webhook->webhook_id,
                'event' => $event,
                'status_code' => $response->status(),
                'payload' => $payload,
                'response' => $response->body(),
                'response_time_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'is_success' => $response->successful(),
            ]);

            DB::table('webhooks')->where('id', $webhook->id)->increment('total_calls');

            if (! $response->successful()) {
                DB::table('webhooks')->where('id', $webhook->id)->increment('failed_calls');

                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'status_code' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            WebhookLog::create([
                'webhook_id' => $webhook->webhook_id,
                'event' => $event,
                'payload' => $payload,
                'error_message' => $e->getMessage(),
                'response_time_ms' => (int) round((microtime(true) - $startTime) * 1000),
                'is_success' => false,
            ]);

            DB::table('webhooks')->where('id', $webhook->id)->increment('total_calls');
            DB::table('webhooks')->where('id', $webhook->id)->increment('failed_calls');

            Log::error("Webhook #{$webhook->id} failed", [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        DB::table('webhooks')->where('id', $webhook->id)->update(['last_triggered_at' => now()]);
    }

    /**
     * Sign payload with HMAC-SHA256.
     */
    protected function signPayload(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
