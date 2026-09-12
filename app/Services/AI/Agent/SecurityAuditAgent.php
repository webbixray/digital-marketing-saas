<?php

namespace App\Services\AI\Agent;

use App\Models\AiContentLog;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Security audit agent that checks for common security issues.
 */
class SecurityAuditAgent implements AgentInterface
{
    public function getName(): string
    {
        return 'security_auditor';
    }

    public function getDescription(): string
    {
        return 'Scans for security vulnerabilities, weak credentials, and misconfigurations.';
    }

    public function getParameters(): array
    {
        return [
            'severity_threshold' => 'medium',
            'scan_scope' => ['config', 'database', 'permissions'],
            'auto_fix' => false,
        ];
    }

    public function setParameters(array $parameters): void
    {
        // In a real implementation, this would persist to config/database
        Log::info('SecurityAuditAgent parameters updated', $parameters);
    }

    public function getCategory(): string
    {
        return 'security';
    }

    public function execute(array $context = []): array
    {
        $findings = [];
        $agency = $context['agency'] ?? null;

        // Check for debug mode in production
        if (config('app.debug') === true && app()->environment('production')) {
            $findings[] = [
                'severity' => 'critical',
                'category' => 'configuration',
                'title' => 'Debug mode enabled in production',
                'description' => 'APP_DEBUG is set to true in a production environment. This can expose sensitive information.',
                'fix' => 'Set APP_DEBUG=false in your .env file.',
                'auto_fixable' => true,
            ];
        }

        // Check for weak app key
        if (empty(config('app.key')) || config('app.key') === 'base:64:your-key-here') {
            $findings[] = [
                'severity' => 'critical',
                'category' => 'configuration',
                'title' => 'Weak or default application key',
                'description' => 'The application key is empty or uses a default value.',
                'fix' => 'Run php artisan key:generate to create a secure key.',
                'auto_fixable' => true,
            ];
        }

        // Check for default database credentials
        $dbPassword = config('database.connections.'.config('database.default').'.password');
        if (in_array($dbPassword, ['', 'password', 'root', '123456'])) {
            $findings[] = [
                'severity' => 'high',
                'category' => 'database',
                'title' => 'Weak database password',
                'description' => 'Database connection uses a weak or default password.',
                'fix' => 'Change the database password in your .env file.',
                'auto_fixable' => false,
            ];
        }

        // Check for overprivileged users
        $adminCount = User::where('role', 'owner')->count();
        if ($adminCount > 5) {
            $findings[] = [
                'severity' => 'medium',
                'category' => 'permissions',
                'title' => 'Excessive number of admin users',
                'description' => "There are {$adminCount} users with owner role. Consider reviewing and reducing admin access.",
                'fix' => 'Review admin users and downgrade unnecessary privileges.',
                'auto_fixable' => false,
            ];
        }

        // Check for failed AI requests spike
        $recentFailures = AiContentLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($recentFailures > 50) {
            $findings[] = [
                'severity' => 'medium',
                'category' => 'reliability',
                'title' => 'High AI request failure rate',
                'description' => "{$recentFailures} AI requests failed in the last 24 hours.",
                'fix' => 'Check AI provider API keys and rate limits.',
                'auto_fixable' => false,
            ];
        }

        // Check for unencrypted sensitive data
        if (config('app.env') === 'production') {
            $findings[] = [
                'severity' => 'low',
                'category' => 'configuration',
                'title' => 'Verify HTTPS enforcement',
                'description' => 'Ensure HTTPS is enforced in production.',
                'fix' => 'Set APP_URL to use https:// and configure trusted proxies.',
                'auto_fixable' => false,
            ];
        }

        // Check for stale scheduled posts
        $stalePosts = SocialPost::where('status', 'scheduled')
            ->where('scheduled_at', '<', now()->subDays(7))
            ->count();
        if ($stalePosts > 0) {
            $findings[] = [
                'severity' => 'low',
                'category' => 'data_integrity',
                'title' => 'Stale scheduled posts',
                'description' => "{$stalePosts} posts have been in 'scheduled' state for over 7 days.",
                'fix' => 'Review and either publish or cancel these stale scheduled posts.',
                'auto_fixable' => true,
            ];
        }

        return [
            'success' => true,
            'findings' => $findings,
            'total_findings' => count($findings),
            'critical_count' => count(array_filter($findings, fn ($f) => $f['severity'] === 'critical')),
            'high_count' => count(array_filter($findings, fn ($f) => $f['severity'] === 'high')),
            'medium_count' => count(array_filter($findings, fn ($f) => $f['severity'] === 'medium')),
            'low_count' => count(array_filter($findings, fn ($f) => $f['severity'] === 'low')),
            'auto_fixable_count' => count(array_filter($findings, fn ($f) => $f['auto_fixable'])),
        ];
    }
}
