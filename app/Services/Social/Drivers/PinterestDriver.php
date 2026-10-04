<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\PinterestApiService;
use App\Services\Social\Results\PlatformPublishResult;

/**
 * Pinterest driver (pins on boards).
 */
class PinterestDriver extends AbstractPlatformDriver
{
    public function __construct(private readonly PinterestApiService $api) {}

    public function name(): string
    {
        return 'pinterest';
    }

    public function label(): string
    {
        return 'Pinterest';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.pinterest.app_id')
            && (bool) config('services.pinterest.app_secret');
    }

    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult
    {
        $image = $this->imageUrl($post);
        $boardId = $this->boardId($account, $post);

        if (! $image) {
            return PlatformPublishResult::fail(
                'Pinterest requires an image to create a pin.',
                platform: $this->name(),
            );
        }

        if (! $boardId) {
            return PlatformPublishResult::fail(
                'No Pinterest board selected. Set a board in the account metadata.',
                platform: $this->name(),
            );
        }

        $result = $this->api->createPin(
            $this->token($account),
            $boardId,
            $this->title($post),
            $this->content($post),
            $image,
            $this->media($post, 'link'),
        );

        $normalized = PlatformPublishResult::fromArray($result, $this->name());

        if ($normalized->success && $normalized->externalId) {
            return PlatformPublishResult::ok(
                externalId: $normalized->externalId,
                url: "https://pinterest.com/pin/{$normalized->externalId}",
                raw: $result,
                platform: $this->name(),
            );
        }

        return $normalized;
    }

    public function validateToken(SocialAccount $account): bool
    {
        return (bool) ($this->api->validateToken($this->token($account))['success'] ?? false);
    }

    public function fetchMetrics(SocialAccount $account, ?string $postId = null): array
    {
        if (! $postId) {
            return [];
        }

        return $this->api->getPinAnalytics($this->token($account), $postId)['data'] ?? [];
    }

    public function getAuthUrl(string $redirectUri, string $state, array $scopes = []): string
    {
        return $this->api->getAuthUrl($redirectUri, $state, $scopes);
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        return $this->api->exchangeCodeForToken($code, $redirectUri);
    }

    /**
     * Board ID from post metadata first, then account metadata.
     */
    private function boardId(SocialAccount $account, SocialPost $post): ?string
    {
        $postBoard = $this->media($post, 'board_id');
        if ($postBoard) {
            return $postBoard;
        }

        $meta = $account->metadata ?? [];

        return ! empty($meta['board_id']) ? (string) $meta['board_id'] : null;
    }
}
