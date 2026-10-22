<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Log;

trait StructuredLogger
{
    /**
     * Log authentication events with structured context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logAuth(string $event, array $context = []): void
    {
        Log::channel('auth')->info("auth:{$event}", array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'auth',
            'event_name' => $event,
        ]));
    }

    /**
     * Log authentication failure.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logAuthFailure(string $reason, array $context = []): void
    {
        Log::channel('auth')->warning("auth:failure:{$reason}", array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'auth',
            'event_name' => 'failure',
            'reason' => $reason,
        ]));
    }

    /**
     * Log billing/subscription events with structured context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logBilling(string $event, array $context = []): void
    {
        Log::channel('billing')->info("billing:{$event}", array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'billing',
            'event_name' => $event,
        ]));
    }

    /**
     * Log billing errors.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logBillingError(string $event, array $context = []): void
    {
        Log::channel('billing')->error("billing:error:{$event}", array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'billing',
            'event_name' => "error:{$event}",
        ]));
    }

    /**
     * Log agent execution with structured context.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logAgentExecution(string $event, array $context = []): void
    {
        Log::channel('agent')->info("agent:{$event}", array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'agent',
            'event_name' => $event,
        ]));
    }

    /**
     * Log agent errors.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logAgentError(string $event, array $context = []): void
    {
        Log::channel('agent')->error("agent:error:{$event}", array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'agent',
            'event_name' => "error:{$event}",
        ]));
    }

    /**
     * Log security-related events.
     *
     * @param  array<string, mixed>  $context
     */
    protected function logSecurity(string $event, array $context = []): void
    {
        Log::channel('security')->warning("security:{$event}", array_merge($context, [
            'timestamp' => now()->toIso8601String(),
            'event_type' => 'security',
            'event_name' => $event,
        ]));
    }
}
