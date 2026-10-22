<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\FacebookApiService;
use App\Services\Social\Results\PlatformPublishResult;

/**
 * Facebook Pages driver.
 */
class FacebookDriver extends AbstractPlatformDriver
{
    protected bool $supportsComments = true;

    public function __construct(private readonly FacebookApiService $api) {}

    public function name(): string
    {
        return 'facebook';
    }

    public function label(): string
    {
        return 'Facebook';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.facebook.client_id')
            && (bool) config('services.facebook.client_secret');
    }

    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult
    {
        $pageId = $this->accountId($account);
        $token = $this->token($account);

        if ($video = $this->videoUrl($post)) {
            $result = $this->api->postVideo($pageId, $token, $video, $this->content($post));
        } elseif ($image = $this->imageUrl($post)) {
            $result = $this->api->postPhoto($pageId, $token, $image, $this->content($post));
        } else {
            $result = $this->api->postText($pageId, $token, $this->content($post));
        }

        $normalized = PlatformPublishResult::fromArray($result, $this->name());

        if ($normalized->success && $normalized->externalId) {
            return PlatformPublishResult::ok(
                externalId: $normalized->externalId,
                url: "https://facebook.com/{$normalized->externalId}",
                raw: $result,
                platform: $this->name(),
            );
        }

        return $normalized;
    }

    public function validateToken(SocialAccount $account): bool
    {
        $result = $this->api->validateToken($this->token($account));

        return (bool) ($result['success'] ?? false);
    }

    public function fetchComments(SocialAccount $account, string $postId, array $options = []): array
    {
        $result = $this->api->getPostComments($postId, $this->token($account));

        return $result['data'] ?? [];
    }

    public function replyToComment(SocialAccount $account, string $commentId, string $message): PlatformPublishResult
    {
        $result = $this->api->replyToComment($commentId, $this->token($account), $message);

        return PlatformPublishResult::fromArray($result, $this->name());
    }

    public function fetchMetrics(SocialAccount $account, ?string $postId = null): array
    {
        $token = $this->token($account);

        return $postId
            ? ($this->api->getPostInsights($postId, $token)['data'] ?? [])
            : ($this->api->getPageInsights($this->accountId($account), $token)['data'] ?? []);
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
