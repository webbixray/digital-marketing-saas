<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\InstagramApiService;
use App\Services\Social\Results\PlatformPublishResult;

/**
 * Instagram Business driver (via the Instagram Graph API).
 */
class InstagramDriver extends AbstractPlatformDriver
{
    protected bool $supportsComments = true;

    public function __construct(private readonly InstagramApiService $api) {}

    public function name(): string
    {
        return 'instagram';
    }

    public function label(): string
    {
        return 'Instagram';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.instagram.client_id')
            && (bool) config('services.instagram.client_secret');
    }

    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult
    {
        $params = ['caption' => $this->content($post)];

        if ($video = $this->videoUrl($post)) {
            $params['video_url'] = $video;
            $params['is_reel'] = true;
        } elseif ($image = $this->imageUrl($post)) {
            $params['image_url'] = $image;
        } else {
            return PlatformPublishResult::fail(
                'Instagram requires an image or video to publish.',
                platform: $this->name(),
            );
        }

        $result = $this->api->post($this->accountId($account), $this->token($account), $params);

        $normalized = PlatformPublishResult::fromArray($result, $this->name());

        if ($normalized->success && $normalized->externalId) {
            return PlatformPublishResult::ok(
                externalId: $normalized->externalId,
                url: "https://instagram.com/p/{$normalized->externalId}",
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
        $result = $this->api->getMediaComments($postId, $this->token($account));

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
            ? ($this->api->getMediaInsights($postId, $token)['data'] ?? [])
            : ($this->api->getAccountInsights($this->accountId($account), $token)['data'] ?? []);
    }

    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        return $this->api->getAuthUrl($redirectUri, $state);
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        return $this->api->exchangeCodeForToken($code, $redirectUri);
    }
}
