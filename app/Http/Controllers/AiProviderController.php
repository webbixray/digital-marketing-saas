<?php

namespace App\Http\Controllers;

use App\Models\AiProviderKey;
use App\Services\AI\AiCacheService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\CostOptimizationEngine;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\SmartRoutingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiProviderController extends Controller
{
    protected AiProviderManager $manager;
    protected AiCacheService $cache;
    protected SmartRoutingEngine $routing;
    protected CostOptimizationEngine $costEngine;

    public function __construct(
        AiProviderManager $manager,
        AiCacheService $cache,
        SmartRoutingEngine $routing,
        CostOptimizationEngine $costEngine
    ) {
        $this->middleware(['auth', 'agency']);
        $this->manager = $manager;
        $this->cache = $cache;
        $this->routing = $routing;
        $this->costEngine = $costEngine;
    }

    /**
     * Display AI Provider Management page.
     */
    public function index(Request $request)
    {
        // If AJAX/JSON request, return data for Alpine.js
        if ($request->wantsJson() || $request->ajax()) {
            return $this->getJsonData();
        }

        return view('ai-providers.index');
    }

    /**
     * Return JSON data for Alpine.js frontend.
     */
    protected function getJsonData(): JsonResponse
    {
        $agencyId = Auth::user()->agency_id;

        $providers = $this->manager->getAvailableProviders($agencyId);
        $cacheMetrics = $this->cache->getMetrics($agencyId);
        $optimizationMetrics = $this->costEngine->getOptimizationMetrics($agencyId);

        // Build routing recommendations
        $taskTypes = ['reasoning', 'creative', 'fast', 'analysis', 'code', 'embedding'];
        $routing = [];
        foreach ($taskTypes as $task) {
            $routing[$task] = $this->manager->getRoutingRecommendation($task, $agencyId);
        }

        // Map providers with masked keys
        $providerData = array_map(function ($p) {
            return array_merge($p, [
                'masked_key' => $p['masked_key'] ?? null,
            ]);
        }, $providers);

        return response()->json([
            'providers' => $providerData,
            'cache_stats' => $cacheMetrics,
            'routing' => $routing,
            'recommendations' => $optimizationMetrics['recommendations'] ?? [],
            'monthly_cost' => $optimizationMetrics['monthly_cost_usd'] ?? 0,
            'budget_limit' => $optimizationMetrics['budget_limit_usd'] ?? 200,
            'byok_savings' => $optimizationMetrics['estimated_savings_usd'] ?? 0,
        ]);
    }

    /**
     * Store a new BYOK key.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_name' => 'required|string|in:openai,anthropic,google,mistral,groq,nvidia_nim,nous_portal,ollama',
            'api_key' => 'required|string|min:10|max:500',
            'api_base_url' => 'nullable|url|max:255',
            'priority' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string|max:500',
        ]);

        $agencyId = Auth::user()->agency_id;

        try {
            $key = $this->manager->setByokKey(
                $agencyId,
                $validated['provider_name'],
                $validated['api_key'],
                $validated['api_base_url'] ?? null,
                $validated['priority'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => "API key for {$validated['provider_name']} saved successfully.",
                'provider' => [
                    'name' => $key->provider_name,
                    'masked_key' => $key->masked_key,
                    'priority' => $key->priority,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to save key: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update an existing BYOK key.
     */
    public function update(Request $request, string $providerName): JsonResponse
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string|min:10|max:500',
            'api_base_url' => 'nullable|url|max:255',
            'priority' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string|max:500',
        ]);

        $agencyId = Auth::user()->agency_id;

        // Get existing key
        $existingKey = AiProviderKey::where('agency_id', $agencyId)
            ->where('provider_name', $providerName)
            ->first();

        if (!$existingKey) {
            return response()->json(['message' => 'Provider key not found.'], 404);
        }

        // Use existing key if new one not provided
        $apiKey = $validated['api_key'] ?? $existingKey->api_key;

        try {
            $this->manager->setByokKey(
                $agencyId,
                $providerName,
                $apiKey,
                $validated['api_base_url'] ?? $existingKey->api_base_url,
                $validated['priority'],
                $validated['notes'] ?? $existingKey->notes
            );

            return response()->json([
                'message' => "API key for {$providerName} updated successfully.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update key: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a BYOK key.
     */
    public function destroy(string $providerName): JsonResponse
    {
        $agencyId = Auth::user()->agency_id;

        $existingKey = AiProviderKey::where('agency_id', $agencyId)
            ->where('provider_name', $providerName)
            ->first();

        if (!$existingKey) {
            return response()->json(['message' => 'Provider key not found.'], 404);
        }

        try {
            $this->manager->removeByokKey($agencyId, $providerName);

            return response()->json([
                'message' => "API key for {$providerName} deleted.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete key: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggle(string $providerName): JsonResponse
    {
        $agencyId = Auth::user()->agency_id;

        $key = AiProviderKey::where('agency_id', $agencyId)
            ->where('provider_name', $providerName)
            ->first();

        if (!$key) {
            return response()->json(['message' => 'Provider key not found.'], 404);
        }

        $key->is_active = !$key->is_active;
        $key->save();

        // Invalidate cache
        \Illuminate\Support\Facades\Cache::forget("ai_byok:{$agencyId}");

        return response()->json([
            'is_active' => $key->is_active,
            'message' => $key->is_active ? 'Provider activated.' : 'Provider deactivated.',
        ]);
    }

    /**
     * Test a provider connection.
     */
    public function test(string $providerName): JsonResponse
    {
        $agencyId = Auth::user()->agency_id;

        $key = AiProviderKey::where('agency_id', $agencyId)
            ->where('provider_name', $providerName)
            ->first();

        if (!$key) {
            return response()->json(['success' => false, 'message' => 'Provider key not found.'], 404);
        }

        // Simple test: check if provider is reachable
        $start = microtime(true);
        try {
            $gateway = app(AiGateway::class);
            $providers = $gateway->getProviders();
            $provider = $providers->get($providerName);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not registered in gateway.',
                ]);
            }

            $available = $provider->isAvailable();
            $latency = round((microtime(true) - $start) * 1000);

            return response()->json([
                'success' => $available,
                'latency_ms' => (int) $latency,
                'message' => $available ? 'Connection successful.' : 'Provider reports as unavailable.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ]);
        }
    }
}
