<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SystemCleanupCommand extends Command
{
    protected $signature = 'system:cleanup
                            {--logs : Clean old log files}
                            {--temp : Clean temporary files}
                            {--cache : Clean application cache}
                            {--sessions : Clean expired sessions}
                            {--all : Run all cleanup tasks}
                            {--days=30 : Number of days to keep (for logs)}
                            {--dry-run : Preview what would be deleted without deleting}';

    protected $description = 'Clean up logs, temporary files, and stale data';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — System Cleanup');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $all = $this->option('all');
        $logs = $all || $this->option('logs');
        $temp = $all || $this->option('temp');
        $cache = $all || $this->option('cache');
        $sessions = $all || $this->option('sessions');
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE — No files will be deleted');
            $this->newLine();
        }

        $results = [
            'logs' => ['count' => 0, 'size' => 0],
            'temp' => ['count' => 0, 'size' => 0],
            'cache' => ['count' => 0, 'size' => 0],
            'sessions' => ['count' => 0, 'size' => 0],
        ];

        // Clean logs
        if ($logs) {
            $this->info('📋 Cleaning log files...');
            $results['logs'] = $this->cleanupLogs($days, $dryRun);
            $this->info("   Files: {$results['logs']['count']} | Size: ".$this->formatBytes($results['logs']['size']));
        }

        // Clean temporary files
        if ($temp) {
            $this->info('📋 Cleaning temporary files...');
            $results['temp'] = $this->cleanupTemp($dryRun);
            $this->info("   Files: {$results['temp']['count']} | Size: ".$this->formatBytes($results['temp']['size']));
        }

        // Clean cache
        if ($cache) {
            $this->info('📋 Cleaning application cache...');
            $results['cache'] = $this->cleanupCache($dryRun);
            $this->info("   Items: {$results['cache']['count']}");
        }

        // Clean sessions
        if ($sessions) {
            $this->info('📋 Cleaning expired sessions...');
            $results['sessions'] = $this->cleanupSessions($days, $dryRun);
            $this->info("   Sessions: {$results['sessions']['count']}");
        }

        // Summary
        $totalFiles = $results['logs']['count'] + $results['temp']['count'] + $results['cache']['count'] + $results['sessions']['count'];
        $totalSize = $results['logs']['size'] + $results['temp']['size'];

        $this->newLine();
        $this->info('───────────────────────────────────────────────────');
        $this->info("Total cleaned: {$totalFiles} items, ".$this->formatBytes($totalSize));
        $this->info('═══════════════════════════════════════════════════');

        if ($dryRun) {
            $this->warn('🔍 Dry run completed — no changes were made');
        }

        return self::SUCCESS;
    }

    private function cleanupLogs(int $days, bool $dryRun): array
    {
        $logPath = storage_path('logs');
        $count = 0;
        $size = 0;
        $cutoff = Carbon::now()->subDays($days);

        if (! is_dir($logPath)) {
            return ['count' => 0, 'size' => 0];
        }

        $files = File::files($logPath);

        foreach ($files as $file) {
            // Skip .gitignore and current log file
            if (in_array($file->getFilename(), ['.gitignore', 'laravel.log'])) {
                continue;
            }

            $modified = Carbon::createFromTimestamp($file->getMTime());
            if ($modified < $cutoff) {
                $count++;
                $size += $file->getSize();
                if (! $dryRun) {
                    File::delete($file->getPathname());
                }
            }
        }

        return ['count' => $count, 'size' => $size];
    }

    private function cleanupTemp(bool $dryRun): array
    {
        $count = 0;
        $size = 0;

        // Clean temp directory
        $tempPaths = [
            storage_path('app/temp'),
            storage_path('app/livewire-tmp'),
            storage_path('framework/data'),
        ];

        foreach ($tempPaths as $tempPath) {
            if (! is_dir($tempPath)) {
                continue;
            }

            $files = File::allFiles($tempPath);
            foreach ($files as $file) {
                // Only delete files older than 24 hours
                $modified = Carbon::createFromTimestamp($file->getMTime());
                if ($modified < Carbon::now()->subDay()) {
                    $count++;
                    $size += $file->getSize();
                    if (! $dryRun) {
                        File::delete($file->getPathname());
                    }
                }
            }
        }

        // Clean compiled views older than 7 days
        $viewPath = storage_path('framework/views');
        if (is_dir($viewPath)) {
            $files = File::files($viewPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'php') {
                    $modified = Carbon::createFromTimestamp($file->getMTime());
                    if ($modified < Carbon::now()->subDays(7)) {
                        $count++;
                        $size += $file->getSize();
                        if (! $dryRun) {
                            File::delete($file->getPathname());
                        }
                    }
                }
            }
        }

        return ['count' => $count, 'size' => $size];
    }

    private function cleanupCache(bool $dryRun): array
    {
        $count = 0;

        // Clean old cache files
        $cachePath = storage_path('framework/cache/data');
        if (is_dir($cachePath)) {
            $directories = File::directories($cachePath);
            foreach ($directories as $dir) {
                $files = File::files($dir);
                foreach ($files as $file) {
                    $modified = Carbon::createFromTimestamp($file->getMTime());
                    if ($modified < Carbon::now()->subDays(7)) {
                        $count++;
                        if (! $dryRun) {
                            File::delete($file->getPathname());
                        }
                    }
                }
            }
        }

        return ['count' => $count, 'size' => 0];
    }

    private function cleanupSessions(int $days, bool $dryRun): array
    {
        $count = 0;
        $sessionDriver = config('session.driver');

        if ($sessionDriver === 'file') {
            $sessionPath = storage_path('framework/sessions');
            if (is_dir($sessionPath)) {
                $files = File::files($sessionPath);
                $cutoff = Carbon::now()->subDays($days);

                foreach ($files as $file) {
                    if ($file->getFilename() === '.gitignore') {
                        continue;
                    }

                    $modified = Carbon::createFromTimestamp($file->getMTime());
                    if ($modified < $cutoff) {
                        $count++;
                        if (! $dryRun) {
                            File::delete($file->getPathname());
                        }
                    }
                }
            }
        } elseif ($sessionDriver === 'database') {
            // Clean sessions from database
            $cutoff = Carbon::now()->subDays($days)->timestamp;
            $count = \DB::table('sessions')
                ->where('last_activity', '<', $cutoff)
                ->when(! $dryRun, function ($query) {
                    $query->delete();
                })
                ->count();
        }

        return ['count' => $count, 'size' => 0];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2).' '.$units[$unitIndex];
    }
}
