<?php

namespace App\Services\Social\Results;

/**
 * Normalized result of a platform publish/action.
 *
 * Every platform driver returns this instead of an ad-hoc array, so callers
 * never have to guess whether the external identifier is `post_id`,
 * `video_id`, `media_id`, or `pin_id`.
 */
final class PlatformPublishResult
{
    /**
     * @param  array<string, mixed>  $raw  Unmodified platform response for debugging/audit.
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId = null,
        public readonly ?string $url = null,
        public readonly ?string $error = null,
        public readonly array $raw = [],
        public readonly ?string $platform = null,
    ) {}

    /**
     * Create a successful result.
     *
     * @param  array<string, mixed>  $raw
     */
    public static function ok(
        ?string $externalId = null,
        ?string $url = null,
        array $raw = [],
        ?string $platform = null,
    ): self {
        return new self(
            success: true,
            externalId: $externalId,
            url: $url,
            error: null,
            raw: $raw,
            platform: $platform,
        );
    }

    /**
     * Create a failed result.
     *
     * @param  array<string, mixed>  $raw
     */
    public static function fail(
        string $error,
        array $raw = [],
        ?string $platform = null,
    ): self {
        return new self(
            success: false,
            externalId: null,
            url: null,
            error: $error,
            raw: $raw,
            platform: $platform,
        );
    }

    /**
     * Build a result from a legacy array shape returned by the *ApiService classes.
     *
     * Handles the various key names platforms use for the external identifier.
     *
     * @param  array<string, mixed>  $array
     */
    public static function fromArray(array $array, ?string $platform = null): self
    {
        $success = (bool) ($array['success'] ?? false);

        if (! $success) {
            return self::fail(
                (string) ($array['error'] ?? 'Unknown platform error'),
                $array,
                $platform,
            );
        }

        $externalId = $array['external_id']
            ?? $array['post_id']
            ?? $array['video_id']
            ?? $array['media_id']
            ?? $array['pin_id']
            ?? $array['tweet_id']
            ?? $array['publish_id']
            ?? $array['id']
            ?? null;

        $url = $array['url'] ?? null;

        return self::ok(
            externalId: $externalId !== null ? (string) $externalId : null,
            url: $url !== null ? (string) $url : null,
            raw: $array,
            platform: $platform,
        );
    }

    /**
     * Legacy array shape, for backward compatibility with existing callers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'external_id' => $this->externalId,
            'post_id' => $this->externalId,
            'url' => $this->url,
            'error' => $this->error,
            'platform' => $this->platform,
        ];
    }
}
