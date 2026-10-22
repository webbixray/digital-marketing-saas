<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\Results\PlatformPublishResult;
use App\Services\Social\YouTubeApiService;

/**
 * YouTube driver (video upload).
 */
class YouTubeDriver extends AbstractPlatformDriver
{
    public function __construct(private readonly YouTubeApiService $api) {}

    public function name(): string
    {
        return 'youtube';
    }

    public function label(): string
    {
        return 'YouTube';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.youtube.client_id')
            && (bool) config('services.youtube.client_secret');
    }

    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult
    {
        $video = $this->videoUrl($post);

        if (! $video) {
            return PlatformPublishResult::fail(
                'YouTube requires a video to publish.',
                platform: $this->name(),
            );
        }

        $result = $this->api->uploadVideo(
            $this->token($account),
            $video,
            $this->title($post),
            $this->content($post),
            [
                'privacy' => $this->media($post, 'privacy') ?? 'private',
                'tags' => is_array($post->hashtags) ? $post->hashtags : [],
            ],
        );

        $normalized = PlatformPublishResult::fromArray($result, $this->name());

        if ($normalized->success) {
            $videoId = $result['video_id'] ?? $normalized->externalId;

            return PlatformPublishResult::ok(
                externalId: $videoId !== null ? (string) $videoId : null,
                url: $videoId ? "https://www.youtube.com/watch?v={$videoId}" : null,
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
            return $this->api->getMyChannel($this->token($account))['data'] ?? [];
        }

        return $this->api->getVideoAnalytics($this->token($account), $postId)['data'] ?? [];
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
