<?php

namespace App\Services\AI\Gateway;

use App\Models\Agency;
use App\Services\AI\Gateway\Contracts\AiProviderInterface;
use App\Services\AI\Gateway\Exceptions\NoProviderAvailableException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AiGateway
{
    /**
     * @var Collection<string, AiProviderInterface>
     */
    protected Collection $providers;

    protected string $defaultProvider;

    public function __construct()
    {
        $this->providers = collect();
        $this->defaultProvider = config('platform.ai.default_provider', 'openai');
    }

    public function registerProvider(string $name, AiProviderInterface $provider): self
    {
        $this->providers->put($name, $provider);

        return $this;
    }

    public function setDefaultProvider(string $name): self
    {
        $this->defaultProvider = $name;

        return $this;
    }

    /**
     * Send a request to the best available AI provider for the given agency.
     *
     * @throws NoProviderAvailableException
     */
    public function send(AiRequest $request, Agency $agency): AiResponse
    {
        // Try agency preference first, then routing-based selection
        $providerChain = $this->resolveProviderChain($request, $agency);

        if ($providerChain->isEmpty()) {
            throw new NoProviderAvailableException;
        }

        $lastException = null;

        foreach ($providerChain as $providerName) {
            $provider = $this->providers->get($providerName);

            if (! $provider || ! $provider->isAvailable()) {
                Log::debug("AI provider [{$providerName}] not available, skipping.");

                continue;
            }

            try {
                $response = $provider->send($request);

                $cost = $provider->calculateCost($response);

                return new AiResponse(
                    content: $response->content,
                    model: $response->model,
                    provider: $providerName,
                    promptTokens: $response->promptTokens,
                    completionTokens: $response->completionTokens,
                    totalTokens: $response->totalTokens,
                    costUsd: $cost,
                    finishReason: $response->finishReason,
                    metadata: array_merge($response->metadata, ['gateway_provider' => $providerName]),
                );
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("AI provider [{$providerName}] failed: {$e->getMessage()}");

                continue;
            }
        }

        throw new NoProviderAvailableException(
            message: 'All AI providers failed. Last error: '.($lastException ? $lastException->getMessage() : 'unknown'),
            previous: $lastException,
        );
    }

    /**
     * Get the preferred provider names in order of preference for a request.
     */
    protected function resolveProviderChain(AiRequest $request, Agency $agency): Collection
    {
        // If request specifies a provider, use it directly
        if ($request->provider) {
            return collect([$request->provider]);
        }

        $task = $request->task;

        // Task-based routing config
        $routing = config("platform.ai.routing.{$task}");

        if (is_array($routing) && ! empty($routing)) {
            return collect($routing)
                ->map(fn ($modelString) => explode(':', $modelString)[0])
                ->unique()
                ->values();
        }

        // Fallback to default provider
        return collect([$this->defaultProvider]);
    }

    /**
     * Check if any provider is available.
     */
    public function hasAvailableProvider(): bool
    {
        return $this->providers->contains(fn ($provider) => $provider->isAvailable());
    }

    /**
     * Get all registered providers.
     */
    public function getProviders(): Collection
    {
        return $this->providers;
    }

    /**
     * Get provider names.
     */
    public function getProviderNames(): array
    {
        return $this->providers->keys()->toArray();
    }
}
