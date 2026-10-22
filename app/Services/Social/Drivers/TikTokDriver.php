<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Results\PlatformPublishResult;
use App\Services\Social\TikTokApiService;

/**
 * TikTok driver (Content Posting API v2).
 */
class TikTokDriver extends AbstractPlatformDriver
{
    protected bool $supportsComments = true;

    public function __construct(private readonly TikTokApiService $api) {}

    public function name(): string
    {
        return 'tiktok';
    }

    public function label(): string
    {
        return 'TikTok';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.tiktok.client_key')
            && (bool) config('services.tiktok.client_secret');
    }

    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult
    {
        $video = $this->videoUrl($post);

        if (! $video) {
            return PlatformPublishResult::fail(
                'TikTok requires a video to publish.',
                platform: $this->name(),
            );
        }

        $result = $this->api->publishVideo(
            $this->token($account),
            $video,
            $this->title($post),
            $this->content($post),
        );

        return PlatformPublishResult::fromArray($result, $this->name());
    }

    public function validateToken(SocialAccount $account): bool
    {
        return (bool) ($this->api->validateToken($this->token($account))['success'] ?? false);
    }

    public function fetchComments(SocialAccount $account, string $postId, array $options = []): array
    {
        $result = $this->api->getComments($this->token($account), $postId);

        return $result['comments'] ?? [];
    }

    public function replyToComment(SocialAccount $account, string $commentId, string $message): PlatformPublishResult
    {
        $result = $this->api->replyToComment($this->token($account), $commentId, $message);

        return PlatformPublishResult::fromArray($result, $this->name());
    }

    public function fetchMetrics(SocialAccount $account, ?string $postId = null): array
    {
        if (! $postId) {
            return $this->api->getUserInfo($this->token($account))['data'] ?? [];
        }

        return $this->api->getVideoStats($this->token($account), $postId)['data'] ?? [];
    }

    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        return $this->api->getAuthUrl($redirectUri, $state, $scopes);
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        return $this->api->exchangeCodeForToken($code, $redirectUri);
    }
}
