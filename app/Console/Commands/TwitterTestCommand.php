<?php

namespace App\Console\Commands;

use App\Services\Social\TwitterApiService;
use Illuminate\Console\Command;

class TwitterTestCommand extends Command
{
    protected $signature = 'twitter:test {--action=authenticate : Action to test (authenticate, metrics, tweet)} {--username= : Twitter username for metrics} {--text= : Text for tweet}';

    protected $description = 'Test Twitter API connection and functionality';

    public function handle(): int
    {
        $action = $this->option('action');

        $this->info('═══════════════════════════════════════════════════');
        $this->info('  DigitalMarketingSaaS — Twitter API Test');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $twitter = new TwitterApiService;

        switch ($action) {
            case 'authenticate':
                return $this->testAuthentication($twitter);
            case 'metrics':
                return $this->testMetrics($twitter);
            case 'tweet':
                return $this->testTweet($twitter);
            default:
                $this->error("Unknown action: {$action}");
                $this->line('Available actions: authenticate, metrics, tweet');

                return self::FAILURE;
        }
    }

    private function testAuthentication(TwitterApiService $twitter): int
    {
        $this->info('Testing Twitter Authentication...');
        $this->newLine();

        if (! $twitter->hasBearerToken()) {
            $this->error('Twitter Bearer Token is not configured!');
            $this->line('Add TWITTER_BEARER_TOKEN to your .env file.');

            return self::FAILURE;
        }

        $result = $twitter->authenticate();

        if ($result['success']) {
            $this->info('✅ Authentication Successful!');
            $data = $result['data']['data'] ?? [];
            $this->line("  User: @{$data['username']} ({$data['name']})");
            $this->line("  ID: {$data['id']}");

            return self::SUCCESS;
        }

        $this->error('❌ Authentication Failed!');
        $this->line("  Error: {$result['error']}");

        return self::FAILURE;
    }

    private function testMetrics(TwitterApiService $twitter): int
    {
        $username = $this->option('username') ?? $this->ask('Enter Twitter username (without @):');

        $this->info("Fetching metrics for @{$username}...");
        $this->newLine();

        $result = $twitter->getUserMetrics($username);

        if ($result['success']) {
            $data = $result['data'];
            $this->info('✅ Metrics Retrieved!');
            $this->line("  Username: @{$data['username']}");
            $this->line("  Name: {$data['name']}");
            $this->line('  Followers: '.number_format($data['followers_count']));
            $this->line('  Following: '.number_format($data['following_count']));
            $this->line('  Tweets: '.number_format($data['tweet_count']));
            $this->line('  Listed: '.number_format($data['listed_count']));

            return self::SUCCESS;
        }

        $this->error('❌ Failed to fetch metrics!');
        $this->line("  Error: {$result['error']}");

        return self::FAILURE;
    }

    private function testTweet(TwitterApiService $twitter): int
    {
        $text = $this->option('text') ?? $this->ask('Enter tweet text (max 280 chars):');

        if (strlen($text) > 280) {
            $this->error('Tweet text exceeds 280 characters!');

            return self::FAILURE;
        }

        $this->info('Posting tweet...');
        $this->newLine();

        if (! $twitter->isConfigured()) {
            $this->error('Twitter OAuth 1.0a credentials are not fully configured!');
            $this->line('Required: TWITTER_API_KEY, TWITTER_API_SECRET, TWITTER_ACCESS_TOKEN, TWITTER_ACCESS_SECRET');

            return self::FAILURE;
        }

        $result = $twitter->postTweet($text);

        if ($result['success']) {
            $this->info('✅ Tweet Posted!');
            $this->line("  Tweet ID: {$result['tweet_id']}");

            return self::SUCCESS;
        }

        $this->error('❌ Failed to post tweet!');
        $this->line("  Error: {$result['error']}");

        return self::FAILURE;
    }
}
