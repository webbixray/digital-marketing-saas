<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AppStatus extends Command
{
    protected $signature = 'app:status';

    protected $description = 'Show comprehensive application status';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════');
        $this->info('  DIGITAL MARKETING SAAS — SYSTEM STATUS');
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        // Database
        $this->info('📊 DATABASE');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Agencies', Agency::count()],
                ['Total Users', User::count()],
                ['Total Social Posts', SocialPost::count()],
                ['Published Posts', SocialPost::where('status', 'published')->count()],
                ['Failed Posts', SocialPost::where('status', 'failed')->count()],
                ['Total Campaigns', Campaign::count()],
                ['Total Clients', Client::count()],
                ['Total Invoices', Invoice::count()],
                ['Pending Invoices', Invoice::where('status', 'pending')->count()],
                ['Paid Invoices', Invoice::where('status', 'paid')->count()],
            ]
        );
        $this->newLine();

        // Plans
        $this->info('💰 PLAN DISTRIBUTION');
        $plans = Agency::select('subscription_plan', DB::raw('count(*) as count'))
            ->groupBy('subscription_plan')
            ->pluck('count', 'subscription_plan')
            ->toArray();

        $this->table(
            ['Plan', 'Count'],
            collect($plans)->map(fn ($count, $plan) => [ucfirst($plan), $count])->toArray()
        );
        $this->newLine();

        // Queue
        $this->info('🔄 QUEUE');
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Pending Jobs', $pendingJobs],
                ['Failed Jobs', $failedJobs],
            ]
        );
        $this->newLine();

        // Cache
        $this->info('⚡ CACHE');
        $this->line('  Driver: '.config('cache.default'));
        $this->newLine();

        // Scheduler
        $this->info('⏰ SCHEDULER');
        $lastRunFile = storage_path('logs/scheduler.lastrun');
        if (file_exists($lastRunFile)) {
            $lastRun = file_get_contents($lastRunFile);
            $this->line("  Last run: {$lastRun}");
        } else {
            $this->line('  Last run: never');
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════');

        return self::SUCCESS;
    }
}
