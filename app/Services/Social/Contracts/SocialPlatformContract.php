<?php

namespace App\Services\Social\Contracts;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Results\PlatformPublishResult;

/**
 * The single contract every social platform driver implements.
 *
 * Adding a platform means implementing this interface, registering the driver
 * in config/platform.php, and adding it to SocialAccount::SUPPORTED_PLATFORMS.
 * No other file in the codebase needs to change.
 */
interface SocialPlatformContract
{
    /**
     * Machine name, e.g. "facebook", "tiktok".
     */
    public function name(): string;

    /**
     * Human label, e.g. "Facebook", "TikTok".
     */
    public function label(): string;

    /**
     * Whether the platform's app credentials are configured.
     */
    public function isConfigured(): bool;

    /**
     * Publish a post to the platform.
     */
    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult;

    /**
     * Validate that a connected account's token is still usable.
     */
    public function validateToken(SocialAccount $account): bool;

    /**
     * Fetch comments/replies for a published post.
     *
     * @param  array<string, mixed>  $options
     * @return array<int, array<string, mixed>>
     */
    public function fetchComments(SocialAccount $account, string $postId, array $options = []): array;

    /**
     * Reply to a comment/reply on the platform.
     */
    public function replyToComment(SocialAccount $account, string $commentId, string $message): PlatformPublishResult;

    /**
     * Fetch account- and/or post-level metrics.
     *
     * @return array<string, mixed>
     */
    public function fetchMetrics(SocialAccount $account, ?string $postId = null): array;

    /**
     * Build the OAuth authorization URL.
     *
     * @param  array<int, string>  $scopes
     */
    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string;

    /**
     * Exchange an authorization code for tokens.
     *
     * @return array<string, mixed>
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): array;
}
