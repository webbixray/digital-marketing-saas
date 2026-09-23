<?php

namespace App\Services\AI;

use App\Models\Agency;
use App\Models\AiProviderKey;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AI Provider Manager
 *
 * Handles:
 * 1. BYOK (Bring Your Own Key) — agencies provide their own API keys
 * 2. Smart routing — picks best provider based on cost, availability, task type
 * 3. Fallback chain — if provider fails, tries next one
 * 4. Multi-vendor integration — OpenAI, Anthropic, Google, Mistral, Groq, NVIDIA, Nous, Ollama
 */
class AiProviderManager
{
    protected AiGateway $gateway;

    /**
     * Available providers registry.
     */
    protected array $providers = [];

    /**
     * Cost per 1M tokens by provider (input, output).
     */
    protected array $providerCost = [
        'openai' => ['input' => 2.50, 'output' => 10.00],          // gpt-4o
        'anthropic' => ['input' => 3.00, 'output' => 15.00],       // claude-3.5-sonnet
        'google' => ['input' => 1.25, 'output' => 5.00],           // gemini-1.5-pro
        'mistral' => ['input' => 2.00, 'output' => 6.00],           // mistral-large
        'groq' => ['input' => 0.10, 'output' => 0.10],              // llama-3.1
        'nvidia_nim' => ['input' => 0.40, 'output' => 1.00],       // llama-3.1
        'nous_portal' => ['input' => 0.20, 'output' => 0.60],       // hermes-3
        'ollama' => ['input' => 0.00, 'output' => 0.00],            // free/local
    ];

    /**
     * Average latency in ms per provider.
     */
    protected array $providerLatency = [
        'openai' => 1200,
        'anthropic' => 1500,
        'google' => 1100,
        'mistral' => 1300,
        'groq' => 400,
        'nvidia_nim' => 700,
        'nous_portal' => 800,
        'ollama' => 200,
    ];

    public function __construct(AiGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Send a request with smart routing.
     *
     * Priority:
     * 1. Agency BYOK keys (agency's own keys, prioritized)
     * 2. System default provider (platform config)
     * 3. Fallback chain based on cost/latency
     */
    public function send(AiRequest $request, Agency $agency): AiResponse
    {
        // 1. Get BYOK keys for this agency
        $byokKeys = $this->getActiveByokKeys($agency->id);

        // 2. Build candidate provider list
        $candidates = $this->buildCandidateList($byokKeys, $request, $agency);

        if ($candidates->isEmpty()) {
            throw new \RuntimeException('No AI providers available. Please configure an API key in Settings > AI Providers.');
        }

        // 3. Send with fallback chain
        $lastException = null;

        foreach ($candidates as $providerName => $providerConfig) {
            try {
                $response = $this->sendToProvider($providerName, $request, $agency, $providerConfig);

                // Log success
                Log::debug("AI request sent via {$providerName}", [
                    'agency_id' => $agency->id,
                    'model' => $request->model,
                    'task' => $request->task,
                    'cost' => $response->costUsd,
                    'tokens' => $response->totalTokens,
                ]);

                return $response;
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("AI provider {$providerName} failed: {$e->getMessage()}, trying fallback");
                continue;
            }
        }

        throw new \RuntimeException(
            'All AI providers failed. Last error: ' . ($lastException ? $lastException->getMessage() : 'unknown')
        );
    }

    /**
     * Get active BYOK keys for an agency.
     */
    public function getActiveByokKeys(int $agencyId): Collection
    {
        return Cache::remember("ai_byok:{$agencyId}", 360, function () use ($agencyId) {
            return AiProviderKey::byAgency($agencyId)
                ->active()
                ->priority()
                ->get();
        });
    }

    /**
     * Add or update a BYOK key for an agency.
     */
    public function setByokKey(
        int $agencyId,
        string $providerName,
        string $apiKey,
        ?string $baseUrl = null,
        int $priority = 5,
        ?string $notes = null
    ): AiProviderKey {
        $key = AiProviderKey::updateOrCreate(
            [
                'agency_id' => $agencyId,
                'provider_name' => $providerName,
            ],
            [
                'api_key' => $apiKey,
                'api_base_url' => $baseUrl,
                'is_active' => true,
                'priority' => $priority,
                'notes' => $notes,
            ]
        );

        // Invalidate cache
        Cache::forget("ai_byok:{$agencyId}");

        return $key;
    }

    /**
     * Remove a BYOK key.
     */
    public function removeByokKey(int $agencyId, string $providerName): void
    {
        AiProviderKey::where('agency_id', $agencyId)
            ->where('provider_name', $providerName)
            ->delete();

        Cache::forget("ai_byok:{$agencyId}");
    }

    /**
     * Get available providers with status.
     */
    public function getAvailableProviders(int $agencyId): array
    {
        $byokKeys = $this->getActiveByokKeys($agencyId);
        $providers = [];

        $allProviders = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'google' => 'Google Gemini',
            'mistral' => 'Mistral AI',
            'groq' => 'Groq',
            'nvidia_nim' => 'NVIDIA NIM',
            'nous_portal' => 'Nous Portal',
            'ollama' => 'Ollama (Local)',
        ];

        foreach ($allProviders as $name => $displayName) {
            $byokKey = $byokKeys->firstWhere('provider_name', $name);

            $providers[] = [
                'name' => $name,
                'display_name' => $displayName,
                'byok_configured' => ! is_null($byokKey),
                'byok_active' => $byokKey ? $byokKey->is_active : false,
                'priority' => $byokKey ? $byokKey->priority : null,
                'masked_key' => $byokKey ? $byokKey->masked_key : null,
                'cost_per_1m_input' => $this->providerCost[$name]['input'] ?? 0,
                'cost_per_1m_output' => $this->providerCost[$name]['output'] ?? 0,
                'avg_latency_ms' => $this->providerLatency[$name] ?? 1000,
                'models' => $this->getProviderModels($name),
            ];
        }

        return $providers;
    }

    /**
     * Get models supported by a provider.
     */
    public function getProviderModels(string $providerName): array
    {
        return match ($providerName) {
            'openai' => ['gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo', 'o1-preview', 'o1-mini'],
            'anthropic' => ['claude-3-5-sonnet-20241022', 'claude-3-haiku-20240307', 'claude-3-opus-20240229'],
            'google' => ['gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-1.0-pro'],
            'mistral' => ['mistral-large', 'mistral-small', 'mistral-tiny'],
            'groq' => ['llama-3.1-70b', 'llama-3.1-8b', 'qwen-3.8-27b'],
            'nvidia_nim' => ['meta/llama-3.1-70b', 'meta/llama-3.1-8b'],
            'nous_portal' => ['hermes-3-llama-3.1-70b', 'hermes-3-llama-3.1-8b'],
            'ollama' => array_keys($this->getOllamaModels()),
            default => [],
        };
    }

    /**
     * Get routing recommendation for a task type.
     */
    public function getRoutingRecommendation(string $taskType, ?int $agencyId = null): array
    {
        $config = config("platform.ai.routing.{$taskType}", []);
        $recommendations = [];

        foreach ($config as $routeString) {
            $parts = explode(':', $routeString);
            $provider = $parts[0];
            $model = $parts[1] ?? 'default';

            $recommendations[] = [
                'provider' => $provider,
                'model' => $model,
                'cost_per_1m_input' => $this->providerCost[$provider]['input'] ?? 0,
                'cost_per_1m_output' => $this->providerCost[$provider]['output'] ?? 0,
                'avg_latency_ms' => $this->providerLatency[$provider] ?? 1000,
                'byok_available' => $agencyId ? $this->hasByokKey($agencyId, $provider) : false,
            ];
        }

        return $recommendations;
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Build a candidate provider list ordered by priority and cost.
     */
    protected function buildCandidateList(Collection $byokKeys, AiRequest $request, Agency $agency): Collection
    {
        $candidates = collect();

        // 1. BYOK keys first (sorted by priority)
        foreach ($byokKeys as $byok) {
            $candidates->put("byok:{$byok->provider_name}", [
                'type' => 'byok',
                'provider_name' => $byok->provider_name,
                'api_key' => $byok->api_key,
                'api_base_url' => $byok->api_base_url,
            ]);
        }

        // 2. System default provider
        $defaultProvider = config('platform.ai.default_provider', 'openai');
        if (! $candidates->has("byok:{$defaultProvider}")) {
            $candidates->put("system:{$defaultProvider}", [
                'type' => 'system',
                'provider_name' => $defaultProvider,
            ]);
        }

        // 3. Fallback providers ordered by cost
        $fallbackProviders = ['groq', 'ollama', 'nvidia_nim', 'nous_portal', 'mistral', 'google', 'anthropic', 'openai'];
        foreach ($fallbackProviders as $provider) {
            $key = "fallback:{$provider}";
            if (! $candidates->has("byok:{$provider}") && ! $candidates->has($key)) {
                $candidates->put($key, [
                    'type' => 'fallback',
                    'provider_name' => $provider,
                ]);
            }
        }

        return $candidates;
    }

    /**
     * Send request to a specific provider.
     */
    protected function sendToProvider(
        string $providerKey,
        AiRequest $request,
        Agency $agency,
        array $config
    ): AiResponse {
        $providerName = $config['provider_name'];

        // Get the provider instance from gateway
        $provider = $this->gateway->getProviders()->get($providerName);

        if (! $provider) {
            throw new \RuntimeException("Provider {$providerName} is not registered");
        }

        // If using BYOK, inject the key
        if ($config['type'] === 'byok') {
            // The provider uses config-based API keys
            // For BYOK, we need to override with agency's key
            $this->overrideProviderConfig($provider, $config);
        }

        // Send
        return $provider->send($request);
    }

    /**
     * Override provider config with BYOK credentials.
     */
    protected function overrideProviderConfig(AiProviderInterface $provider, array $byokConfig): void
    {
        // Set config dynamically for this request
        config(["platform.ai.providers.{$byokConfig['provider_name']}.api_key" => $byokConfig['api_key']]);

        if ($byokConfig['api_base_url']) {
            config(["platform.ai.providers.{$byokConfig['provider_name']}.api_base_url" => $byokConfig['api_base_url']]);
        }
    }

    protected function hasByokKey(int $agencyId, string $providerName): bool
    {
        return AiProviderKey::where('agency_id', $agencyId)
            ->where('provider_name', $providerName)
            ->where('is_active', true)
            ->exists();
    }

    protected function getOllamaModels(): array
    {
        return [
            'llama3.1:8b' => 'Llama 3.1 8B',
            'llama3.1:70b' => 'Llama 3.1 70B',
            'llama3.2:3b' => 'Llama 3.2 3B',
            'mistral:7b' => 'Mistral 7B',
            'qwen2.5:7b' => 'Qwen 2.5 7B',
        ];
    }
}
