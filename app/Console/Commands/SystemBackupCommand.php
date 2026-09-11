<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class SystemBackupCommand extends Command
{
    protected $signature = 'system:backup
                            {--storage=local : Storage disk to save the backup}
                            {--compress : Compress the backup file}
                            {--retention=7 : Number of days to keep backups}';

    protected $description = 'Create a database backup and store it on the configured disk';

    public function __construct(
        private readonly BackupService $backupService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — System Backup');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $result = $this->backupService->createBackup();

        if ($result['success']) {
            $this->info("Backup created: {$result['filename']}");
            $this->info("Size: {$result['size']}");
            return self::SUCCESS;
        }

        $this->error("Backup failed: {$result['error']}");
        return self::FAILURE;
    }
}
