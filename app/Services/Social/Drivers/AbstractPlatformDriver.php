<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Contracts\SocialPlatformContract;
use App\Services\Social\Results\PlatformPublishResult;

/**
 * Shared behaviour for all platform drivers.
 *
 * Concrete drivers only need to implement the methods that differ; the base
 * class provides safe defaults for optional capabilities (comments, metrics)
 * so a partially-integrated platform still slots in without fatal errors.
 */
abstract class AbstractPlatformDriver implements SocialPlatformContract
{
    /**
     * Whether this driver can fetch/reply to comments.
     */
    protected bool $supportsComments = false;

    /**
     * Whether this driver can fetch post metrics.
     */
    protected bool $supportsMetrics = true;

    /**
     * Extract a URL from a post's media array by key.
     */
    protected function media(SocialPost $post, string $key): ?string
    {
        $media = $post->media ?? [];

        return is_array($media) && ! empty($media[$key]) ? (string) $media[$key] : null;
    }

    /**
     * The first image URL attached to a post, if any.
     */
    protected function imageUrl(SocialPost $post): ?string
    {
        return $this->media($post, 'image_url')
            ?? $this->media($post, 'image')
            ?? $this->firstMediaUrl($post, 'images');
    }

    /**
     * The video URL attached to a post, if any.
     */
    protected function videoUrl(SocialPost $post): ?string
    {
        return $this->media($post, 'video_url')
            ?? $this->media($post, 'video');
    }

    /**
     * The post body text.
     */
    protected function content(SocialPost $post): string
    {
        return (string) ($post->content ?? '');
    }

    /**
     * The post title (falls back to a trimmed content snippet).
     */
    protected function title(SocialPost $post): string
    {
        $explicit = $this->media($post, 'title');

        return $explicit ?? mb_substr($this->content($post), 0, 100);
    }

    /**
     * The external post identifier as stored on the account model.
     */
    protected function accountId(SocialAccount $account): string
    {
        return (string) $account->platform_account_id;
    }

    /**
     * The account's access token.
     */
    protected function token(SocialAccount $account): string
    {
        return (string) $account->access_token;
    }

    /**
     * Pull the first URL from a media array key.
     */
    private function firstMediaUrl(SocialPost $post, string $key): ?string
    {
        $media = $post->media ?? [];
        if (! is_array($media) || empty($media[$key]) || ! is_array($media[$key])) {
            return null;
        }

        $first = $media[$key][0] ?? null;

        return is_string($first) ? $first : null;
    }

    // --- Optional capabilities with safe defaults ---------------------------

    public function validateToken(SocialAccount $account): bool
    {
        return ! empty($account->access_token) && ! $account->isExpired();
    }

    public function fetchComments(SocialAccount $account, string $postId, array $options = []): array
    {
        return [];
    }

    public function replyToComment(SocialAccount $account, string $commentId, string $message): PlatformPublishResult
    {
        return PlatformPublishResult::fail(
            "{$this->label()} does not support comment replies.",
            platform: $this->name(),
        );
    }

    public function fetchMetrics(SocialAccount $account, ?string $postId = null): array
    {
        return [];
    }

    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        return '';
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        return ['success' => false, 'error' => "{$this->label()} OAuth is not implemented in this driver."];
    }
}
