<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialPost;

/**
 * @deprecated Use App\Services\Social\SocialPlatformManager instead.
 *
 * Retained for backward compatibility only. Every method now delegates to the
 * central driver registry so the previously-divergent (and buggy) direct-HTTP
 * implementations can no longer drift from the real platform services.
 */
class SocialApiService
{
    public function __construct(private readonly SocialPlatformManager $platforms) {}

    /**
     * Publish to the appropriate platform.
     *
     * @return array<string, mixed>
     */
    public function publish(SocialAccount $account, SocialPost $post): array
    {
        if (! $this->platforms->has($account->platform)) {
            return ['success' => false, 'error' => "Unsupported platform: {$account->platform}"];
        }

        return $this->platforms->for($account->platform)->publish($account, $post)->toArray();
    }

    /**
     * Publish post to Facebook.
     *
     * @return array<string, mixed>
     */
    public function publishToFacebook(SocialAccount $account, SocialPost $post): array
    {
        return $this->platforms->for('facebook')->publish($account, $post)->toArray();
    }

    /**
     * Publish post to Instagram.
     *
     * @return array<string, mixed>
     */
    public function publishToInstagram(SocialAccount $account, SocialPost $post): array
    {
        return $this->platforms->for('instagram')->publish($account, $post)->toArray();
    }

    /**
     * Publish post to Twitter/X.
     *
     * @return array<string, mixed>
     */
    public function publishToTwitter(SocialAccount $account, SocialPost $post): array
    {
        return $this->platforms->for('twitter')->publish($account, $post)->toArray();
    }

    /**
     * Publish post to LinkedIn.
     *
     * @return array<string, mixed>
     */
    public function publishToLinkedIn(SocialAccount $account, SocialPost $post): array
    {
        return $this->platforms->for('linkedin')->publish($account, $post)->toArray();
    }

    /**
     * Publish post to TikTok.
     *
     * @return array<string, mixed>
     */
    public function publishToTikTok(SocialAccount $account, SocialPost $post): array
    {
        return $this->platforms->for('tiktok')->publish($account, $post)->toArray();
    }

    /**
     * Publish post to Pinterest.
     *
     * @return array<string, mixed>
     */
    public function publishToPinterest(SocialAccount $account, SocialPost $post): array
    {
        return $this->platforms->for('pinterest')->publish($account, $post)->toArray();
    }

    /**
     * Get account info from platform.
     *
     * @return array<string, mixed>
     */
    public function getAccountInfo(SocialAccount $account): array
    {
        if (! $this->platforms->has($account->platform)) {
            return ['success' => false, 'error' => 'Not implemented'];
        }

        return [
            'success' => true,
            'data' => $this->platforms->for($account->platform)->fetchMetrics($account),
        ];
    }
}
