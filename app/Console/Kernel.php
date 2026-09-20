<?php

namespace App\Console;

use App\Models\ActivityLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process scheduled posts every minute
        $schedule->command('posts:process-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Retry failed posts every 5 minutes
        $schedule->command('posts:retry-failed')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old logs weekly
        $schedule->command('model:prune', [
            '--model' => [ActivityLog::class],
            '--days' => 90,
        ])->weekly();

        // Update scheduler heartbeat
        $schedule->command('scheduler:heartbeat')
            ->everyMinute()
            ->runInBackground();

        // Run self-improvement analysis daily with auto-tuning
        $schedule->command('agents:improve --auto-tune')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();

        // Run security/audit agents every 6 hours with auto-fix
        $schedule->command('agents:audit --auto-fix')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();

        // Run system backup daily at 2:00 AM
        $schedule->command('system:backup --compress')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Sync platform metrics hourly
        $schedule->command('metrics:sync --all')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old database logs (webhook_logs, activity_logs) weekly
        $schedule->command('database:cleanup --force')
            ->weekly()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
