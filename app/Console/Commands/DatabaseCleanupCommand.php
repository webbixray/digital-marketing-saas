<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\WebhookLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseCleanupCommand extends Command
{
    protected $signature = 'database:cleanup
                            {--webhook-days=90 : Number of days to keep webhook logs}
                            {--activity-days=90 : Number of days to keep activity logs}
                            {--chunk-size=1000 : Number of rows to delete per chunk}
                            {--dry-run : Preview what would be deleted without deleting}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Clean up old webhook_logs and activity_logs to maintain database performance';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — Database Cleanup');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $webhookDays = (int) $this->option('webhook-days');
        $activityDays = (int) $this->option('activity-days');
        $chunkSize = (int) $this->option('chunk-size');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE — No data will be deleted');
            $this->newLine();
        }

        // Confirmation
        if (! $dryRun && ! $force) {
            if (! $this->confirm("This will delete webhook_logs older than {$webhookDays} days and activity_logs older than {$activityDays} days. Continue?")) {
                $this->warn('Cleanup cancelled.');
                return self::SUCCESS;
            }
        }

        $results = [
            'webhook_logs' => ['count' => 0, 'table' => 'webhook_logs'],
            'activity_logs' => ['count' => 0, 'table' => 'activity_logs'],
        ];

        // -----------------------------------------------------------------------
        // Clean webhook_logs
        // -----------------------------------------------------------------------
        $this->info("📋 Cleaning webhook_logs older than {$webhookDays} days...");
        $webhookCutoff = Carbon::now()->subDays($webhookDays);
        $results['webhook_logs']['count'] = $this->cleanupInChunks(
            'webhook_logs',
            'created_at',
            $webhookCutoff,
            $chunkSize,
            $dryRun
        );
        $this->info("   Deleted: {$results['webhook_logs']['count']} webhook_logs");

        // -----------------------------------------------------------------------
        // Clean activity_logs
        // -----------------------------------------------------------------------
        $this->info("📋 Cleaning activity_logs older than {$activityDays} days...");
        $activityCutoff = Carbon::now()->subDays($activityDays);
        $results['activity_logs']['count'] = $this->cleanupInChunks(
            'activity_logs',
            'created_at',
            $activityCutoff,
            $chunkSize,
            $dryRun
        );
        $this->info("   Deleted: {$results['activity_logs']['count']} activity_logs");

        // -----------------------------------------------------------------------
        // Summary
        // -----------------------------------------------------------------------
        $totalDeleted = $results['webhook_logs']['count'] + $results['activity_logs']['count'];

        $this->newLine();
        $this->info('───────────────────────────────────────────────────');
        $this->info("Total deleted: {$totalDeleted} rows");
        $this->info('═══════════════════════════════════════════════════');

        if ($dryRun) {
            $this->warn('🔍 Dry run completed — no changes were made');
        } else {
            Log::info('Database cleanup completed', [
                'webhook_logs_deleted' => $results['webhook_logs']['count'],
                'activity_logs_deleted' => $results['activity_logs']['count'],
                'webhook_days' => $webhookDays,
                'activity_days' => $activityDays,
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * Delete rows in chunks to avoid long locks on large tables.
     */
    private function cleanupInChunks(
        string $table,
        string $dateColumn,
        Carbon $cutoff,
        int $chunkSize,
        bool $dryRun
    ): int {
        $totalDeleted = 0;

        if ($dryRun) {
            // For dry run, just count matching rows
            return DB::connection('mysql_write')
                ->table($table)
                ->where($dateColumn, '<', $cutoff)
                ->count();
        }

        // Delete in chunks using the write connection
        do {
            $deleted = DB::connection('mysql_write')
                ->table($table)
                ->where($dateColumn, '<', $cutoff)
                ->limit($chunkSize)
                ->delete();

            $totalDeleted += $deleted;

            if ($deleted > 0) {
                // Brief pause to reduce replication lag
                usleep(10000); // 10ms
            }
        } while ($deleted === $chunkSize);

        return $totalDeleted;
    }
}
