<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Balance;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\AuthenticationException;
use Stripe\Stripe;

class StripeTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Stripe API connection and display account balance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $secretKey = config('stripe.secret');

        if (empty($secretKey) || str_contains($secretKey, 'xxx')) {
            $this->error('Stripe secret key is not configured or uses placeholder values.');
            $this->line('Please set STRIPE_SECRET in your .env file with a valid test key (sk_test_...).');

            return Command::FAILURE;
        }

        if (! str_starts_with($secretKey, 'sk_test_')) {
            $this->warn('Warning: The Stripe secret key does not start with sk_test_. You are NOT in test mode!');
        } else {
            $this->info('✓ Running in Stripe test mode.');
        }

        Stripe::setApiKey($secretKey);

        try {
            $balance = Balance::retrieve();

            $this->info('✓ Stripe API connection successful!');
            $this->line('');
            $this->line('Available balances:');

            foreach ($balance->available as $fund) {
                $this->line(sprintf(
                    '  %s %s',
                    strtoupper($fund->currency),
                    number_format($fund->amount / 100, 2)
                ));
            }

            return Command::SUCCESS;
        } catch (AuthenticationException $e) {
            $this->error('✗ Stripe authentication failed: '.$e->getMessage());

            return Command::FAILURE;
        } catch (ApiConnectionException $e) {
            $this->error('✗ Could not connect to Stripe: '.$e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('✗ Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
