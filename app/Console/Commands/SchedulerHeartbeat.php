<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SchedulerHeartbeat extends Command
{
    protected $signature = 'scheduler:heartbeat';

    protected $description = 'Update scheduler heartbeat file';

    public function handle(): int
    {
        $file = storage_path('logs/scheduler.lastrun');
        file_put_contents($file, now()->toDateTimeString());
        $this->info('Scheduler heartbeat updated at '.now()->toDateTimeString());

        return self::SUCCESS;
    }
}
