<?php

namespace App\Services\Social\Drivers;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\LinkedInApiService;
use App\Services\Social\Results\PlatformPublishResult;

/**
 * LinkedIn driver (member + organization sharing).
 */
class LinkedInDriver extends AbstractPlatformDriver
{
    public function __construct(private readonly LinkedInApiService $api) {}

    public function name(): string
    {
        return 'linkedin';
    }

    public function label(): string
    {
        return 'LinkedIn';
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.linkedin.client_id')
            && (bool) config('services.linkedin.client_secret');
    }

    public function publish(SocialAccount $account, SocialPost $post): PlatformPublishResult
    {
        $authorUrn = $this->authorUrn($account);
        $options = [];

        if ($link = $this->media($post, 'link')) {
            $options['media_url'] = $link;
            $options['media_type'] = 'article';
            $options['title'] = $this->title($post);
        }

        $result = $this->api->share($this->token($account), $authorUrn, $this->content($post), $options);

        return PlatformPublishResult::fromArray($result, $this->name());
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

        return $this->api->getShareStats($this->token($account), $postId)['data'] ?? [];
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
     * LinkedIn URNs: organization accounts use `urn:li:organization:*`, members `urn:li:person:*`.
     */
    private function authorUrn(SocialAccount $account): string
    {
        $type = $account->platform_account_type === 'organization' ? 'organization' : 'person';

        return "urn:li:{$type}:{$this->accountId($account)}";
    }
}
