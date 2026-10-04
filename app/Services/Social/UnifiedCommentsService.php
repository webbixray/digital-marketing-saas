<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use Illuminate\Support\Facades\Log;

/**
 * Unified comment/reply access across all platforms.
 *
 * Delegates to the central SocialPlatformManager, so it supports every
 * registered platform automatically — no per-platform match blocks.
 */
class UnifiedCommentsService
{
    public function __construct(private readonly SocialPlatformManager $platforms) {}

    /**
     * Fetch comments for a specific post from its platform.
     */
    public function fetchComments(SocialAccount $account, string $postId): array
    {
        try {
            if (! $this->platforms->has($account->platform)) {
                return [];
            }

            return $this->platforms
                ->for($account->platform)
                ->fetchComments($account, $postId);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch comments', [
                'platform' => $account->platform,
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Reply to a comment on a platform.
     */
    public function replyToComment(SocialAccount $account, string $commentId, string $message): bool
    {
        try {
            if (! $this->platforms->has($account->platform)) {
                return false;
            }

            return $this->platforms
                ->for($account->platform)
                ->replyToComment($account, $commentId, $message)
                ->success;
        } catch (\Throwable $e) {
            Log::error('Failed to reply to comment', [
                'platform' => $account->platform,
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
