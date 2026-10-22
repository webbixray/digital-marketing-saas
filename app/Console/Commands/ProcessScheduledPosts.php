<?php

namespace App\Console\Commands;

use App\Jobs\Social\ProcessScheduledPostsJob;
use Illuminate\Console\Command;

class ProcessScheduledPosts extends Command
{
    protected $signature = 'posts:process-scheduled';

    protected $description = 'Process all scheduled posts that are due';

    public function handle(): int
    {
        $this->info('Dispatching scheduled post processor...');
        ProcessScheduledPostsJob::dispatch();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
