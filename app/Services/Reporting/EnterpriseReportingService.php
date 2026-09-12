<?php

namespace App\Services\Reporting;

use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\ScheduledReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class EnterpriseReportingService
{
    private const ALLOWED_FREQUENCIES = ['daily', 'weekly', 'monthly'];

    private const ALLOWED_FORMATS = ['pdf', 'csv', 'xlsx'];

    private const ALLOWED_TYPES = ['social', 'email', 'campaign', 'analytics', 'custom', 'performance', 'engagement', 'conversion'];

    public function generateCustomReport(int $agencyId, array $config): Report
    {
        $this->validateConfig($config);

        $report = Report::create([
            'agency_id' => $agencyId,
            'user_id' => auth()->id() ?? $config['user_id'] ?? null,
            'name' => $config['name'] ?? 'Custom Report',
            'type' => $config['type'] ?? 'custom',
            'format' => $config['format'] ?? 'pdf',
            'schedule' => 'once',
            'status' => 'pending',
            'filters' => $config['filters'] ?? [],
            'columns' => $config['columns'] ?? [],
        ]);

        GenerateReportJob::dispatch($report);

        return $report;
    }

    public function scheduleReport(int $agencyId, array $config, string $frequency): ScheduledReport
    {
        if (! in_array($frequency, self::ALLOWED_FREQUENCIES, true)) {
            throw new InvalidArgumentException("Invalid frequency: {$frequency}. Allowed: ".implode(', ', self::ALLOWED_FREQUENCIES));
        }

        $this->validateConfig($config);

        $scheduledReport = ScheduledReport::create([
            'agency_id' => $agencyId,
            'user_id' => auth()->id() ?? $config['user_id'] ?? null,
            'name' => $config['name'] ?? 'Scheduled Report',
            'type' => $config['type'] ?? 'custom',
            'format' => $config['format'] ?? 'pdf',
            'frequency' => $frequency,
            'next_run_at' => $this->calculateNextRun($frequency),
            'is_active' => true,
            'filters' => $config['filters'] ?? [],
            'columns' => $config['columns'] ?? [],
        ]);

        Log::info('Scheduled report created', [
            'scheduled_report_id' => $scheduledReport->id,
            'agency_id' => $agencyId,
            'frequency' => $frequency,
        ]);

        return $scheduledReport;
    }

    public function exportData(int $agencyId, string $type, array $filters): string
    {
        if (! in_array($type, self::ALLOWED_FORMATS, true)) {
            throw new InvalidArgumentException("Invalid export type: {$type}. Allowed: ".implode(', ', self::ALLOWED_FORMATS));
        }

        $data = $this->collectExportData($agencyId, $filters);
        $filename = "exports/agency_{$agencyId}_{$type}_".time().".{$type}";

        switch ($type) {
            case 'csv':
                $content = $this->generateCsv($data);
                break;
            case 'xlsx':
                $content = $this->generateCsv($data);
                $filename = str_replace('.xlsx', '.csv', $filename);
                break;
            default:
                throw new RuntimeException("Export format {$type} not yet implemented");
        }

        Storage::put($filename, $content);

        return Storage::url($filename);
    }

    public function getReportTypes(): array
    {
        return [
            'social' => ['label' => 'Social Media', 'description' => 'Posts, engagement, and reach metrics'],
            'email' => ['label' => 'Email Campaigns', 'description' => 'Open rates, clicks, and conversions'],
            'campaign' => ['label' => 'Campaign Performance', 'description' => 'ROI, spend, and attribution'],
            'analytics' => ['label' => 'Web Analytics', 'description' => 'Traffic, sessions, and behavior'],
            'custom' => ['label' => 'Custom Report', 'description' => 'User-defined metrics and dimensions'],
            'performance' => ['label' => 'Performance Overview', 'description' => 'Cross-channel performance summary'],
            'engagement' => ['label' => 'Engagement Report', 'description' => 'Likes, comments, shares, and interactions'],
            'conversion' => ['label' => 'Conversion Report', 'description' => 'Lead generation and conversion metrics'],
        ];
    }

    public function getAvailableMetrics(): array
    {
        return [
            'social' => [
                'total_posts', 'published_posts', 'scheduled_posts', 'failed_posts',
                'total_reach', 'total_impressions', 'engagement_rate', 'follower_growth',
            ],
            'email' => [
                'emails_sent', 'open_rate', 'click_rate', 'bounce_rate', 'unsubscribe_rate',
                'revenue_per_email', 'list_growth',
            ],
            'campaign' => [
                'total_spend', 'impressions', 'clicks', 'conversions', 'cpa', 'roas', 'ctr',
            ],
            'analytics' => [
                'sessions', 'users', 'pageviews', 'bounce_rate', 'avg_session_duration',
                'pages_per_session', 'goal_completions',
            ],
            'performance' => [
                'total_revenue', 'total_cost', 'profit_margin', 'conversion_rate',
                'customer_acquisition_cost', 'lifetime_value',
            ],
        ];
    }

    public function getScheduledReports(int $agencyId): Collection
    {
        return ScheduledReport::forAgency($agencyId)
            ->with(['agency', 'user'])
            ->orderByDesc('created_at')
            ->get();
    }

    private function validateConfig(array $config): void
    {
        if (isset($config['type']) && ! in_array($config['type'], self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Invalid report type: {$config['type']}");
        }

        if (isset($config['format']) && ! in_array($config['format'], self::ALLOWED_FORMATS, true)) {
            throw new InvalidArgumentException("Invalid format: {$config['format']}");
        }
    }

    private function calculateNextRun(string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => now()->addDay()->startOfDay(),
            'weekly' => now()->addWeek()->startOfWeek(),
            'monthly' => now()->addMonth()->startOfMonth(),
            default => now()->addDay(),
        };
    }

    private function collectExportData(int $agencyId, array $filters): array
    {
        return Report::forAgency($agencyId)
            ->get()
            ->toArray();
    }

    private function generateCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0]));

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }
}
