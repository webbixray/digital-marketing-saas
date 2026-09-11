<?php

namespace App\Console\Commands;

use App\Mail\WinBackEmail;
use App\Models\User;
use App\Services\ChurnPreventionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendWinBackEmailsCommand extends Command
{
    protected $signature = 'campaigns:win-back
                            {--days=30 : Days since last activity}
                            {--discount=25 : Discount percentage}
                            {--dry-run : Preview without sending}';

    protected $description = 'Send win-back emails to dormant users';

    public function __construct(
        private readonly ChurnPreventionService $churnService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $discount = (int) $this->option('discount');
        $dryRun = $this->option('dry-run');

        $this->info("Finding users inactive for {$days}+ days...");

        $dormantUsers = $this->churnService->getDormantUsers($days);

        if (empty($dormantUsers)) {
            $this->info('No dormant users found.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($dormantUsers) . ' dormant users.');

        if ($dryRun) {
            $this->info('DRY RUN - No emails will be sent.');
            foreach ($dormantUsers as $user) {
                $this->line("  - {$user->email} (last active: {$user->last_active_at})");
            }
            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($dormantUsers as $user) {
            try {
                $discountCode = 'COMEBACK-' . strtoupper(Str::random(6));

                Mail::to($user->email)->send(new WinBackEmail(
                    user: $user,
                    discountCode: $discountCode,
                    discountPercent: $discount,
                ));

                $sent++;
                $this->line("  ✓ Sent to {$user->email}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$user->email} - {$e->getMessage()}");
                Log::error('Win-back email failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Sent {$sent} win-back emails.");
        return self::SUCCESS;
    }
}
