<?php

namespace App\Console\Commands;

use App\Jobs\FetchPlatformMetricsJob;
use App\Models\SocialAccount;
use Illuminate\Console\Command;

class SyncPlatformMetricsCommand extends Command
{
    protected $signature = 'metrics:sync {platform?} {--all : Sync all active accounts}';
    protected $description = 'Sync platform metrics for social accounts';

    public function handle(): int
    {
        $query = SocialAccount::query()->where('is_active', true);

        if ($this->argument('platform')) {
            $query->where('platform', $this->argument('platform'));
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('No active social accounts found.');
            return self::SUCCESS;
        }

        $this->info("Dispatching metrics sync for {$accounts->count()} accounts...");

        foreach ($accounts as $account) {
            FetchPlatformMetricsJob::dispatch($account);
            $this->line("  - Queued: {$account->platform} ({$account->platform_display_name})");
        }

        $this->info('Metrics sync jobs dispatched successfully.');
        return self::SUCCESS;
    }
}
