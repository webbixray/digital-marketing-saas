<?php

namespace App\Console\Commands;

use App\Models\SocialAccount;
use App\Services\Social\SocialPlatformManager;
use Illuminate\Console\Command;

/**
 * Lists every registered social platform driver and its capabilities.
 *
 * A developer adding a platform can run this to confirm registration, and an
 * operator can run it to see which platforms are actually configured.
 */
class SocialPlatformsCommand extends Command
{
    protected $signature = 'social:platforms {--registered : Only show platforms with app credentials configured}';

    protected $description = 'List registered social platform drivers and their capabilities';

    public function handle(SocialPlatformManager $platforms): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — Social Platform Drivers');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $rows = [];

        foreach ($platforms->all() as $name => $driver) {
            if ($this->option('registered') && ! $driver->isConfigured()) {
                continue;
            }

            $inComposer = array_key_exists($name, SocialAccount::SUPPORTED_PLATFORMS);

            $rows[] = [
                $name,
                $driver->label(),
                $driver->isConfigured() ? '✅ yes' : '— no',
                $inComposer ? '✅ yes' : '❌ NO',
                get_class($driver),
            ];
        }

        if (empty($rows)) {
            $this->warn('No platforms match the filter.');

            return self::SUCCESS;
        }

        $this->table(
            ['Platform', 'Label', 'Credentials', 'In composer', 'Driver class'],
            $rows,
        );

        $this->newLine();
        $this->line('Add a platform: create a driver extending AbstractPlatformDriver,');
        $this->line('register it in config/platform.php (social_drivers), then add it to');
        $this->line('SocialAccount::SUPPORTED_PLATFORMS. No other file needs to change.');

        return self::SUCCESS;
    }
}
