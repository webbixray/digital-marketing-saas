<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Results\PlatformPublishResult;
use App\Services\Social\TwitterApiService;

/**
 * Twitter / X driver (OAuth 1.0a user context).
 */
class TwitterDriver extends AbstractPlatformDriver
{
    protected bool $supportsComments = true;

    public function __construct(private readonly TwitterApiService $api) {}

    public function name(): string
    {
        return 'twitter';
    }

    public function label(): string
    {
        return 'Twitter / X';
    }

    public function isConfigured(): bool
    {
        return $this->api->isConfigured();
    }

    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult
    {
        $result = $this->api->postTweet($this->content($post));

        $normalized = PlatformPublishResult::fromArray($result, $this->name());

        if ($normalized->success && $normalized->externalId) {
            return PlatformPublishResult::ok(
                externalId: $normalized->externalId,
                url: "https://twitter.com/i/web/status/{$normalized->externalId}",
                raw: $result,
                platform: $this->name(),
            );
        }

        return $normalized;
    }

    public function validateToken(SocialAccount $account): bool
    {
        return $this->api->isConfigured() && $this->api->hasBearerToken();
    }

    public function fetchComments(SocialAccount $account, string $postId, array $options = []): array
    {
        $result = $this->api->getTweetReplies($postId);

        return $result['data'] ?? [];
    }

    public function replyToComment(SocialAccount $account, string $commentId, string $message): PlatformPublishResult
    {
        $result = $this->api->replyToTweet($commentId, $message);

        return PlatformPublishResult::fromArray($result, $this->name());
    }

    public function fetchMetrics(SocialAccount $account, ?string $postId = null): array
    {
        if ($postId) {
            return $this->api->getTweetMetrics($postId)['data'] ?? [];
        }

        $username = (string) $account->platform_username;

        return $username !== '' ? ($this->api->getUserMetrics($username)['data'] ?? []) : [];
    }

    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        return '';
    }
}
