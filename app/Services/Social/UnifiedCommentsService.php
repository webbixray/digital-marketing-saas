<?php

namespace App\Services\Social;

use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Services\Social\FacebookApiService;
use App\Services\Social\InstagramApiService;
use App\Services\Social\TwitterApiService;
use Illuminate\Support\Facades\Log;

class UnifiedCommentsService
{
    public function __construct(
        private readonly FacebookApiService $facebook,
        private readonly InstagramApiService $instagram,
        private readonly TwitterApiService $twitter,
    ) {}

    /**
     * Fetch comments for a specific post from its platform.
     */
    public function fetchComments(SocialAccount $account, string $postId): array
    {
        try {
            return match ($account->platform) {
                'facebook' => $this->fetchFacebookComments($account, $postId),
                'instagram' => $this->fetchInstagramComments($account, $postId),
                'twitter' => $this->fetchTwitterComments($account, $postId),
                default => [],
            };
        } catch (\Exception $e) {
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
            return match ($account->platform) {
                'facebook' => $this->facebook->replyToComment(
                    $account->access_token,
                    $commentId,
                    $message
                ),
                'instagram' => $this->instagram->replyToComment(
                    $account->access_token,
                    $commentId,
                    $message
                ),
                'twitter' => $this->twitter->replyToTweet(
                    $account->access_token,
                    $commentId,
                    $message
                ),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('Failed to reply to comment', [
                'platform' => $account->platform,
                'comment_id' => $commentId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Fetch comments from Facebook.
     */
    private function fetchFacebookComments(SocialAccount $account, string $postId): array
    {
        $response = $this->facebook->getPostComments(
            $account->access_token,
            $postId
        );
        return $response['data'] ?? [];
    }

    /**
     * Fetch comments from Instagram.
     */
    private function fetchInstagramComments(SocialAccount $account, string $postId): array
    {
        $response = $this->instagram->getMediaComments(
            $account->access_token,
            $postId
        );
        return $response['data'] ?? [];
    }

    /**
     * Fetch comments from Twitter.
     */
    private function fetchTwitterComments(SocialAccount $account, string $postId): array
    {
        $response = $this->twitter->getTweetReplies(
            $account->access_token,
            $postId
        );
        return $response['data'] ?? [];
    }
}
