<?php

namespace App\Services\AI\Gateway\Contracts;

use App\Services\AI\Gateway\AiRequest;
use App\Services\AI\Gateway\AiResponse;

interface AiProviderInterface
{
    public function send(AiRequest $request): AiResponse;

    public function getName(): string;

    public function getDisplayName(): string;

    public function isAvailable(): bool;

    public function getSupportedModels(): array;

    public function getDefaultModel(): string;

    public function calculateCost(AiResponse $response): float;
}
