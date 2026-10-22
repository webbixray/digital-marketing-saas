<?php

namespace App\Console\Commands;

use App\Services\AI\Gateway\AiGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SystemStatusCommand extends Command
{
    protected $signature = 'system:status';

    protected $description = 'Show comprehensive system status';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — System Status');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        // Environment
        $this->info('📋 Environment');
        $this->line('  Environment: '.config('app.env'));
        $this->line('  Debug: '.(config('app.debug') ? 'true' : 'false'));
        $this->line('  URL: '.config('app.url'));
        $this->line('  Version: '.config('app.version', '1.0.0'));
        $this->newLine();

        // Database
        $this->info('🗄️ Database');
        try {
            DB::connection()->getPdo();
            $this->line('  Status: ✅ Connected');
            $this->line('  Driver: '.DB::connection()->getDriverName());
            $this->line('  Database: '.DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            $this->line('  Status: ❌ Failed');
            $this->line('  Error: '.$e->getMessage());
        }
        $this->newLine();

        // Cache
        $this->info('⚡ Cache');
        try {
            $driver = config('cache.default');
            $this->line("  Driver: {$driver}");
            cache()->put('system_status_test', true, 10);
            $test = cache()->get('system_status_test');
            $this->line('  Status: '.($test ? '✅ Working' : '❌ Failed'));
        } catch (\Exception $e) {
            $this->line('  Status: ❌ Failed');
            $this->line('  Error: '.$e->getMessage());
        }
        $this->newLine();

        // Queue
        $this->info('📬 Queue');
        $this->line('  Driver: '.config('queue.default'));
        $this->newLine();

        // AI Gateway
        $this->info('🤖 AI Gateway');
        try {
            $gateway = app(AiGateway::class);
            $providers = $gateway->getProviderNames();
            $this->line('  Registered providers: '.(empty($providers) ? 'none' : implode(', ', $providers)));
            $this->line('  Has available provider: '.($gateway->hasAvailableProvider() ? 'Yes' : 'No'));
        } catch (\Exception $e) {
            $this->line('  Status: ❌ Failed');
            $this->line('  Error: '.$e->getMessage());
        }
        $this->newLine();

        // Stats
        $this->info('📊 Statistics');
        try {
            $this->line('  Agencies: '.DB::table('agencies')->count());
            $this->line('  Users: '.DB::table('users')->count());
            $this->line('  Social Posts: '.DB::table('social_posts')->count());
            $this->line('  Campaigns: '.DB::table('campaigns')->count());
            $this->line('  Clients: '.DB::table('clients')->count());
        } catch (\Exception $e) {
            $this->line('  Could not fetch statistics');
        }
        $this->newLine();

        // Health checks
        $this->info('🏥 Health Checks');
        try {
            $response = Http::get(url('/health'));
            $this->line('  /health: '.($response->successful() ? '✅ '.$response->json('status') : '❌ Failed'));
        } catch (\Exception $e) {
            $this->line('  /health: ❌ Failed');
        }

        try {
            $response = Http::get(url('/ready'));
            $this->line('  /ready: '.($response->successful() ? '✅ '.$response->json('status') : '❌ '.$response->json('status')));
        } catch (\Exception $e) {
            $this->line('  /ready: ❌ Failed');
        }
        $this->newLine();

        $this->info('═══════════════════════════════════════════════════');

        return self::SUCCESS;
    }
}
