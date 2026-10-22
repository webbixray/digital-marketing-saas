<?php

namespace App\Console\Commands;

use App\Jobs\Social\RetryFailedPostsJob;
use Illuminate\Console\Command;

class RetryFailedPosts extends Command
{
    protected $signature = 'posts:retry-failed';

    protected $description = 'Retry failed posts (max 3 attempts)';

    public function handle(): int
    {
        $this->info('Dispatching failed post retry job...');
        RetryFailedPostsJob::dispatch();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
