<?php

namespace App\Services\AI\Audit;

use App\Models\AiAuditLog;
use App\Models\Agency;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiAuditService
{
    public function logRequest(
        int $agencyId,
        int $userId,
        string $action,
        string $model,
        string $input,
        string $output,
        array $metadata = [],
    ): AiAuditLog {
        $biasResult = $this->checkBias($output);
        $toxicityResult = $this->checkToxicity($output);
        $complianceResult = $this->checkCompliance($output, [
            'action' => $action,
            'bias_score' => $biasResult['score'],
            'toxicity_score' => $toxicityResult['score'],
        ]);

        return AiAuditLog::create([
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'action' => $action,
            'model_used' => $model,
            'input_hash' => hash('sha256', $input),
            'output_hash' => hash('sha256', $output),
            'bias_score' => $biasResult['score'],
            'toxicity_score' => $toxicityResult['score'],
            'compliance_status' => $complianceResult['status'],
            'flagged_reason' => $complianceResult['reason'],
            'metadata' => array_merge($metadata, [
                'bias_details' => $biasResult,
                'toxicity_details' => $toxicityResult,
            ]),
        ]);
    }

    public function checkBias(string $output): array
    {
        $score = 0.0;
        $flags = [];

        // Pattern-based bias detection
        $biasIndicators = [
            'gender_bias' => [
                'patterns' => ['/\b(mankind|manpower|chairman|fireman|policeman)\b/i'],
                'weight' => 0.15,
                'type' => 'gendered_language',
            ],
            'racial_bias' => [
                'patterns' => ['/\b(blacklist|whitelist|master\s*slave)\b/i'],
                'weight' => 0.2,
                'type' => 'exclusionary_language',
            ],
            'age_bias' => [
                'patterns' => ['/\b(young\s*and\s*digital|digital\s*native|millennial\s*mentality)\b/i'],
                'weight' => 0.1,
                'type' => 'age_stereotype',
            ],
            'cultural_bias' => [
                'patterns' => ['/\b(third\s*world|developing\s*nation|western\s*standard)\b/i'],
                'weight' => 0.25,
                'type' => 'cultural_superiority',
            ],
        ];

        foreach ($biasIndicators as $biasType => $indicator) {
            foreach ($indicator['patterns'] as $pattern) {
                if (preg_match($pattern, $output)) {
                    $score += $indicator['weight'];
                    $flags[] = $indicator['type'];
                }
            }
        }

        $score = min($score, 1.0);

        return [
            'score' => round($score, 2),
            'flags' => array_unique($flags),
            'passed' => $score < 0.3,
        ];
    }

    public function checkToxicity(string $output): array
    {
        $score = 0.0;
        $flags = [];

        $toxicityIndicators = [
            'hostility' => [
                'patterns' => ['/\b(hate|destroy|kill|attack|abuse)\b/i'],
                'weight' => 0.3,
            ],
            'profanity' => [
                'patterns' => ['/\b(damn|hell|crap)\b/i'],
                'weight' => 0.1,
            ],
            'harassment' => [
                'patterns' => ['/\b(stupid|idiot|loser|worthless)\b/i'],
                'weight' => 0.25,
            ],
            'discrimination' => [
                'patterns' => ['/\b(inferior|superior\s*race|genetic\s*superiority)\b/i'],
                'weight' => 0.5,
            ],
        ];

        foreach ($toxicityIndicators as $type => $indicator) {
            foreach ($indicator['patterns'] as $pattern) {
                if (preg_match($pattern, $output)) {
                    $score += $indicator['weight'];
                    $flags[] = $type;
                }
            }
        }

        $score = min($score, 1.0);

        return [
            'score' => round($score, 2),
            'flags' => array_unique($flags),
            'passed' => $score < 0.2,
        ];
    }

    public function checkCompliance(string $output, array $context = []): array
    {
        $biasScore = $context['bias_score'] ?? 0;
        $toxicityScore = $context['toxicity_score'] ?? 0;

        $status = 'pass';
        $reason = null;

        if ($toxicityScore >= 0.5) {
            $status = 'fail';
            $reason = 'High toxicity detected';
        } elseif ($biasScore >= 0.6) {
            $status = 'fail';
            $reason = 'Severe bias detected';
        } elseif ($toxicityScore >= 0.2) {
            $status = 'warn';
            $reason = 'Moderate toxicity detected';
        } elseif ($biasScore >= 0.3) {
            $status = 'warn';
            $reason = 'Potential bias detected';
        }

        // Check for PII (simplified)
        if (preg_match('/\b\d{3}-\d{2}-\d{4}\b/', $output)) {
            $status = 'fail';
            $reason = 'Potential SSN pattern detected in output';
        } elseif (preg_match('/\b\d{16}\b/', $output)) {
            $status = 'fail';
            $reason = 'Potential credit card number in output';
        }

        return [
            'status' => $status,
            'reason' => $reason,
            'checks' => [
                'bias_passed' => $biasScore < 0.3,
                'toxicity_passed' => $toxicityScore < 0.2,
                'pii_clean' => $reason === null || ! str_contains($reason, 'SSN') && ! str_contains($reason, 'credit card'),
            ],
        ];
    }

    public function getAuditLogs(int $agencyId, array $filters = []): LengthAwarePaginator
    {
        $query = AiAuditLog::byAgency($agencyId)->with(['user', 'agency']);

        if (! empty($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (! empty($filters['compliance_status'])) {
            $query->byComplianceStatus($filters['compliance_status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['flagged'])) {
            $query->flagged();
        }

        $perPage = $filters['per_page'] ?? 25;

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getFlaggedContent(int $agencyId, int $limit = 50): Collection
    {
        return AiAuditLog::byAgency($agencyId)
            ->flagged()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getComplianceReport(int $agencyId, ?string $period = '30 days'): array
    {
        $query = AiAuditLog::byAgency($agencyId);

        if ($period) {
            $query->where('created_at', '>=', now()->sub($period));
        }

        $totalLogs = $query->count();

        if ($totalLogs === 0) {
            return [
                'agency_id' => $agencyId,
                'period' => $period,
                'total_logs' => 0,
                'pass_count' => 0,
                'fail_count' => 0,
                'warn_count' => 0,
                'pass_rate' => 100,
                'avg_bias_score' => 0,
                'avg_toxicity_score' => 0,
                'flagged_by_action' => [],
                'trend' => [],
            ];
        }

        $passCount = (clone $query)->byComplianceStatus('pass')->count();
        $failCount = (clone $query)->byComplianceStatus('fail')->count();
        $warnCount = (clone $query)->byComplianceStatus('warn')->count();

        $avgBias = (clone $query)->avg('bias_score') ?? 0;
        $avgToxicity = (clone $query)->avg('toxicity_score') ?? 0;

        $flaggedByAction = (clone $query)
            ->flagged()
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        $trend = (clone $query)
            ->selectRaw('DATE(created_at) as date, compliance_status, COUNT(*) as count')
            ->groupBy('date', 'compliance_status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(fn ($items) => [
                'pass' => $items->where('compliance_status', 'pass')->sum('count'),
                'fail' => $items->where('compliance_status', 'fail')->sum('count'),
                'warn' => $items->where('compliance_status', 'warn')->sum('count'),
            ])
            ->toArray();

        return [
            'agency_id' => $agencyId,
            'period' => $period,
            'total_logs' => $totalLogs,
            'pass_count' => $passCount,
            'fail_count' => $failCount,
            'warn_count' => $warnCount,
            'pass_rate' => round(($passCount / $totalLogs) * 100, 1),
            'avg_bias_score' => round($avgBias, 3),
            'avg_toxicity_score' => round($avgToxicity, 3),
            'flagged_by_action' => $flaggedByAction,
            'trend' => $trend,
        ];
    }

    public function exportAuditLog(int $agencyId, string $format = 'csv'): string
    {
        $logs = AiAuditLog::byAgency($agencyId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = "ai-audit-exports/{$agencyId}/audit-" . time() . ".{$format}";

        if ($format === 'json') {
            $content = $logs->map(fn ($log) => [
                'id' => $log->id,
                'user' => $log->user?->name,
                'action' => $log->action,
                'model' => $log->model_used,
                'bias_score' => $log->bias_score,
                'toxicity_score' => $log->toxicity_score,
                'compliance_status' => $log->compliance_status,
                'flagged_reason' => $log->flagged_reason,
                'created_at' => $log->created_at->toISOString(),
            ])->toJson(JSON_PRETTY_PRINT);
        } else {
            // CSV format
            $csv = "ID,User,Action,Model,Bias Score,Toxicity Score,Compliance Status,Flagged Reason,Created At\n";
            foreach ($logs as $log) {
                $csv .= sprintf(
                    "%d,%s,%s,%s,%s,%s,%s,%s,%s\n",
                    $log->id,
                    '"' . ($log->user?->name ?? 'unknown') . '"',
                    $log->action,
                    '"' . ($log->model_used ?? '') . '"',
                    $log->bias_score,
                    $log->toxicity_score,
                    $log->compliance_status,
                    '"' . str_replace('"', '""', $log->flagged_reason ?? '') . '"',
                    $log->created_at->toISOString()
                );
            }
            $content = $csv;
        }

        Storage::disk('local')->put($filename, $content);

        Log::info('AI Audit log exported', [
            'agency_id' => $agencyId,
            'format' => $format,
            'record_count' => $logs->count(),
            'file' => $filename,
        ]);

        return $filename;
    }

    public function getStatsForNav(int $agencyId): array
    {
        $flaggedCount = AiAuditLog::byAgency($agencyId)
            ->flagged()
            ->forToday()
            ->count();

        $totalToday = AiAuditLog::byAgency($agencyId)
            ->forToday()
            ->count();

        return [
            'flagged_today' => $flaggedCount,
            'total_today' => $totalToday,
            'has_flagged' => $flaggedCount > 0,
        ];
    }
}
