<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class QueueHealthService
{
    public function isConfigured(): bool
    {
        $driver = config('queue.default');

        return in_array($driver, ['redis', 'beanstalkd', 'sqs']);
    }

    public function getStatus(): array
    {
        return [
            'driver' => config('queue.default'),
            'configured' => $this->isConfigured(),
        ];
    }

    public function getHealthCheck(): array
    {
        $status = $this->getStatus();
        $issues = [];

        if ($status['driver'] === 'sync') {
            $issues[] = 'Queue driver is sync. Jobs will block the request.';
        }

        if ($status['driver'] === 'database') {
            $issues[] = 'Queue driver is database. Use Redis for production.';
        }

        return [
            'healthy' => empty($issues),
            'status' => $status,
            'issues' => $issues,
        ];
    }

    public function getFailedJobsCount(): int
    {
        try {
            return Cache::remember('queue_failed_count', 60, function () {
                return DB::table('failed_jobs')->count();
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function retryFailedJob(string $jobId): bool
    {
        try {
            $job = DB::table('failed_jobs')->where('id', $jobId)->first();
            if (! $job) {
                return false;
            }

            Queue::pushRaw($job->payload, $job->queue);
            DB::table('failed_jobs')->where('id', $jobId)->delete();

            Log::info('Failed job retried', ['job_id' => $jobId]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to retry job', ['job_id' => $jobId, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
