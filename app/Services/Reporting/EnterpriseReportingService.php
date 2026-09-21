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
use App\Models\SocialPost;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;

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
        $filename = "exports/agency_{$agencyId}_{$type}_".time();

        switch ($type) {
            case 'csv':
                $content = $this->generateCsv($data);
                $filename .= '.csv';
                break;
            case 'xlsx':
                $content = $this->generateCsv($data);
                $filename .= '.xlsx';
                break;
            case 'pdf':
                $content = $this->generatePdf($data);
                $filename .= '.pdf';
                break;
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
        $type = $filters['report_type'] ?? 'summary';

        $socialData = SocialPost::where('agency_id', $agencyId)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(1000)
            ->get()
            ->toArray();

        $campaignData = Campaign::where('agency_id', $agencyId)
            ->withCount('socialPosts')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->toArray();

        $clientData = Client::where('agency_id', $agencyId)
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->toArray();

        $accountData = SocialAccount::where('agency_id', $agencyId)
            ->orderBy('platform')
            ->get()
            ->toArray();

        return match($type) {
            'social' => $socialData,
            'campaign' => $campaignData,
            'client' => $clientData,
            'account' => $accountData,
            default => $this->getSummaryReport($agencyId),
        };
    }

    private function getSummaryReport(int $agencyId): array
    {
        $postsQuery = SocialPost::where('agency_id', $agencyId)->where('status', 'published');
        $campaignsQuery = Campaign::where('agency_id', $agencyId);
        $clientsQuery = Client::where('agency_id', $agencyId);

        return [
            ['metric' => 'Total Posts', 'value' => $postsQuery->count()],
            ['metric' => 'Total Campaigns', 'value' => $campaignsQuery->count()],
            ['metric' => 'Total Clients', 'value' => $clientsQuery->count()],
            ['metric' => 'Avg Engagement Rate', 'value' => round($postsQuery->avg('engagement_rate') ?? 0, 2)],
            ['metric' => 'Total Reach', 'value' => $postsQuery->sum('reach')],
        ];
    }

    private function generatePdf(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $headers = array_keys($data[0]);
        
        $html = '<html><head><meta charset="utf-8"><style>
            body { font-family: DejaVu Sans, sans-serif; margin: 20px; color: #1a1a1a; }
            h1 { color: #4f46e5; font-size: 24px; margin-bottom: 20px; }
            h2 { color: #374151; font-size: 16px; margin-bottom: 10px; }
            table { border-collapse: collapse; width: 100%; margin-top: 10px; font-size: 10px; }
            th { background: #4f46e5; color: white; padding: 8px; text-align: left; }
            td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
            tr:nth-child(even) { background: #f9fafb; }
            .header { margin-bottom: 30px; padding-bottom: 15px; border-bottom: 2px solid #4f46e5; }
            .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #e5e7eb; font-size: 10px; color: #6b7280; }
            .generated { color: #6b7280; font-size: 11px; }
        </style></head><body>';
        $html .= '<div class="header">';
        $html .= '<h1>📊 DigitalMarketingSaaS Report</h1>';
        $html .= '<div class="generated">Generated: ' . now()->format('F j, Y \a\t g:i A') . '</div>';
        $html .= '</div>';
        $html .= '<table><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
        }
        $html .= '</tr></head><tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<div class="footer">';
        $html .= '<p>Report generated by DigitalMarketingSaaS</p>';
        $html .= '</div>';
        $html .= '</body></html>';

        return $html;
    }

    private function getMetricValue(int $agencyId, string $metric): float|int
    {
        $posts = SocialPost::where('agency_id', $agencyId)->where('status', 'published');
        $postsAll = SocialPost::where('agency_id', $agencyId);
        $campaigns = Campaign::where('agency_id', $agencyId);
        $clients = Client::where('agency_id', $agencyId);
        $accounts = SocialAccount::where('agency_id', $agencyId);

        return match($metric) {
            'total_posts', 'published_posts' => (int) $posts->count(),
            'total_reach' => (int) $postsAll->sum('reach'),
            'engagement_rate' => round((float) ($posts->avg('engagement_rate') ?? 0), 2),
            'total_campaigns' => (int) $campaigns->count(),
            'total_clients' => (int) $clients->count(),
            'follower_growth' => round((float) ($accounts->avg('follower_growth_rate') ?? 0), 2),
            default => 0,
        };
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
