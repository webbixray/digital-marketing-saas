<?php

namespace App\Services\AI\Agent;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class AgentHealthMonitor
{
    /**
     * Cache key for storing agent health snapshots.
     */
    private const HEALTH_CACHE_KEY = 'agent_health_snapshots';

    /**
     * Cache key for storing alert counts.
     */
    private const ALERT_CACHE_KEY = 'agent_alert_counts';

    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Check health of a specific agent.
     */
    public function checkAgentHealth(AgentInterface $agent): array
    {
        $name = $agent->getName();

        try {
            $snapshot = $this->getHealthSnapshot($name);

            $health = [
                'agent_name' => $name,
                'status' => $this->determineAgentStatus($agent, $snapshot),
                'last_execution' => $snapshot['last_execution'] ?? null,
                'last_error' => $snapshot['last_error'] ?? null,
                'error_rate' => $snapshot['error_rate'] ?? 0.0,
                'success_rate' => $agent->getSuccessRate(),
                'speed_score' => $agent->getSpeedScore(),
                'cost_score' => $agent->getCostScore(),
                'total_executed' => $agent->getTotalExecuted(),
                'total_cost' => $agent->getTotalCost(),
                'supported_types' => $agent->getSupportedTaskTypes(),
                'uptime_percentage' => $this->getAgentUptime($name),
                'checked_at' => now()->toIso8601String(),
            ];

            // Check for alert conditions
            $this->evaluateAlertConditions($name, $health);

            return $health;
        } catch (Throwable $e) {
            Log::error("AgentHealthMonitor: failed to check health for [{$name}]: {$e->getMessage()}");

            return [
                'agent_name' => $name,
                'status' => 'error',
                'last_execution' => null,
                'last_error' => $e->getMessage(),
                'error_rate' => 1.0,
                'success_rate' => $agent->getSuccessRate(),
                'speed_score' => $agent->getSpeedScore(),
                'cost_score' => $agent->getCostScore(),
                'total_executed' => $agent->getTotalExecuted(),
                'total_cost' => $agent->getTotalCost(),
                'supported_types' => $agent->getSupportedTaskTypes(),
                'uptime_percentage' => 0.0,
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get overall system health score.
     */
    public function getSystemHealth(): array
    {
        $agents = $this->getAllAgentNames();
        $agentHealthScores = [];
        $totalUptime = 0.0;
        $healthyCount = 0;

        foreach ($agents as $agentName) {
            $uptime = $this->getAgentUptime($agentName);
            $totalUptime += $uptime;

            if ($uptime >= 95.0) {
                $healthyCount++;
            }

            $agentHealthScores[$agentName] = [
                'uptime_percentage' => $uptime,
                'status' => $uptime >= 95.0 ? 'healthy' : ($uptime >= 80.0 ? 'degraded' : 'critical'),
            ];
        }

        $agentCount = count($agents);
        $systemScore = $agentCount > 0 ? ($healthyCount / $agentCount) * 100 : 100.0;
        $averageUptime = $agentCount > 0 ? $totalUptime / $agentCount : 100.0;

        return [
            'system_score' => round($systemScore, 2),
            'average_uptime_percentage' => round($averageUptime, 2),
            'total_agents' => $agentCount,
            'healthy_agents' => $healthyCount,
            'degraded_agents' => $agentCount - $healthyCount,
            'overall_status' => $systemScore >= 95.0 ? 'healthy' : ($systemScore >= 80.0 ? 'degraded' : 'critical'),
            'agents' => $agentHealthScores,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get uptime percentage for a specific agent.
     */
    public function getAgentUptime(string $agentName): float
    {
        $snapshot = $this->getHealthSnapshot($agentName);

        $totalChecks = $snapshot['total_health_checks'] ?? 0;
        $healthyChecks = $snapshot['healthy_checks'] ?? 0;

        if ($totalChecks === 0) {
            // No data yet, assume healthy
            return 100.0;
        }

        return round(($healthyChecks / $totalChecks) * 100, 2);
    }

    /**
     * Get configurable alert thresholds.
     */
    public function getAlertThresholds(): array
    {
        return [
            'error_rate_warning' => 0.1,    // 10% error rate triggers warning
            'error_rate_critical' => 0.3,   // 30% error rate triggers critical
            'uptime_warning' => 95.0,       // Below 95% uptime triggers warning
            'uptime_critical' => 80.0,      // Below 80% uptime triggers critical
            'slow_execution_ms' => 30000,   // Tasks taking >30s are slow
            'high_cost_per_task' => 1.0,    // $1.00 per task is high
            'consecutive_failures' => 3,    // 3 consecutive failures triggers alert
        ];
    }

    /**
     * Send/log an alert for an agent issue.
     */
    public function sendAlert(string $agentName, string $issue): void
    {
        $thresholds = $this->getAlertThresholds();

        // Increment alert count
        $alertCounts = Cache::get(self::ALERT_CACHE_KEY, []);
        $alertCounts[$agentName] = ($alertCounts[$agentName] ?? 0) + 1;
        Cache::put(self::ALERT_CACHE_KEY, $alertCounts, now()->addHours(24));

        // Determine severity based on issue
        $severity = $this->classifySeverity($issue, $thresholds);

        // Log the alert
        Log::channel('agent_alerts')->warning("Agent Alert [{$severity}]: [{$agentName}] {$issue}", [
            'agent_name' => $agentName,
            'issue' => $issue,
            'severity' => $severity,
            'alert_count' => $alertCounts[$agentName],
            'timestamp' => now()->toIso8601String(),
        ]);

        // In production, this would also notify:
        // - Slack/Teams webhook
        // - Email to admin
        // - Push notification to dashboard
        // - PagerDuty for critical alerts
    }

    /**
     * Record a health check result for an agent.
     */
    public function recordHealthCheck(string $agentName, bool $wasHealthy, ?string $error = null): void
    {
        $snapshot = $this->getHealthSnapshot($agentName);

        $snapshot['total_health_checks'] = ($snapshot['total_health_checks'] ?? 0) + 1;
        $snapshot['healthy_checks'] = ($snapshot['healthy_checks'] ?? 0) + ($wasHealthy ? 1 : 0);
        $snapshot['last_execution'] = now()->toIso8601String();
        $snapshot['last_error'] = $error;

        // Calculate rolling error rate from recent history
        $recentErrors = $snapshot['recent_errors'] ?? [];
        $recentErrors[] = $wasHealthy ? 0 : 1;
        $recentErrors = array_slice($recentErrors, -100); // Keep last 100
        $snapshot['recent_errors'] = $recentErrors;
        $snapshot['error_rate'] = count($recentErrors) > 0
            ? round(array_sum($recentErrors) / count($recentErrors), 4)
            : 0.0;

        $this->storeHealthSnapshot($agentName, $snapshot);
    }

    /**
     * Record task execution result for health monitoring.
     */
    public function recordTaskExecution(string $agentName, AgentResult $result): void
    {
        $this->recordHealthCheck(
            $agentName,
            $result->success,
            $result->error
        );
    }

    /**
     * Get health snapshot from cache.
     */
    private function getHealthSnapshot(string $agentName): array
    {
        $snapshots = Cache::get(self::HEALTH_CACHE_KEY, []);

        return $snapshots[$agentName] ?? [];
    }

    /**
     * Store health snapshot to cache.
     */
    private function storeHealthSnapshot(string $agentName, array $snapshot): void
    {
        $snapshots = Cache::get(self::HEALTH_CACHE_KEY, []);
        $snapshots[$agentName] = $snapshot;
        Cache::put(self::HEALTH_CACHE_KEY, $snapshots, now()->addMinutes(self::CACHE_TTL));
    }

    /**
     * Determine agent status based on its metrics.
     */
    private function determineAgentStatus(AgentInterface $agent, array $snapshot): string
    {
        $errorRate = $snapshot['error_rate'] ?? 0.0;
        $thresholds = $this->getAlertThresholds();
        $uptime = $this->getAgentUptime($agent->getName());

        if ($errorRate >= $thresholds['error_rate_critical'] || $uptime < $thresholds['uptime_critical']) {
            return 'critical';
        }

        if ($errorRate >= $thresholds['error_rate_warning'] || $uptime < $thresholds['uptime_warning']) {
            return 'degraded';
        }

        return 'healthy';
    }

    /**
     * Evaluate alert conditions and send alerts if needed.
     */
    private function evaluateAlertConditions(string $agentName, array $health): void
    {
        $thresholds = $this->getAlertThresholds();

        // Check error rate
        if ($health['error_rate'] >= $thresholds['error_rate_critical']) {
            $this->sendAlert($agentName, "Critical error rate: {$health['error_rate']}%");
        } elseif ($health['error_rate'] >= $thresholds['error_rate_warning']) {
            $this->sendAlert($agentName, "High error rate: {$health['error_rate']}%");
        }

        // Check uptime
        if ($health['uptime_percentage'] < $thresholds['uptime_critical']) {
            $this->sendAlert($agentName, "Critical uptime: {$health['uptime_percentage']}%");
        } elseif ($health['uptime_percentage'] < $thresholds['uptime_warning']) {
            $this->sendAlert($agentName, "Low uptime: {$health['uptime_percentage']}%");
        }
    }

    /**
     * Classify alert severity.
     */
    private function classifySeverity(string $issue, array $thresholds): string
    {
        $issueLower = strtolower($issue);

        if (str_contains($issueLower, 'critical')) {
            return 'critical';
        }

        if (str_contains($issueLower, 'error rate:') && $this->extractPercentage($issue) > $thresholds['error_rate_critical'] * 100) {
            return 'critical';
        }

        if (str_contains($issueLower, 'uptime:') && $this->extractPercentage($issue) < $thresholds['uptime_critical']) {
            return 'critical';
        }

        return 'warning';
    }

    /**
     * Extract percentage value from issue string.
     */
    private function extractPercentage(string $text): float
    {
        if (preg_match('/([\d.]+)%/', $text, $matches)) {
            return (float) $matches[1];
        }

        return 0.0;
    }

    /**
     * Get all registered agent names from the orchestrator.
     */
    private function getAllAgentNames(): array
    {
        // Try to get from orchestrator if available
        try {
            $orchestrator = app(AgentOrchestrator::class);
            $agents = $orchestrator->getRegisteredAgents();
            if (! empty($agents)) {
                return $agents;
            }
        } catch (Throwable) {
            // Orchestrator not available
        }

        // Fallback: check cache for known agents
        $snapshots = Cache::get(self::HEALTH_CACHE_KEY, []);

        return array_keys($snapshots);
    }
}
