<?php

namespace App\Services\Social;

use App\Enums\PostStatus;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SocialPostService
{
    public function __construct(private SocialApiService $apiService) {}

    /**
     * Create a new social post (draft or scheduled).
     */
    public function createPost(int $agencyId, array $data): SocialPost
    {
        return DB::transaction(function () use ($agencyId, $data) {
            $post = SocialPost::create([
                'agency_id' => $agencyId,
                'social_account_id' => $data['social_account_id'],
                'platform' => $data['platform'],
                'content' => $data['content'] ?? null,
                'media' => $data['media'] ?? null,
                'links' => $data['links'] ?? null,
                'hashtags' => $data['hashtags'] ?? null,
                'mentions' => $data['mentions'] ?? null,
                'tags' => $data['tags'] ?? null,
                'status' => $data['status'] ?? PostStatus::DRAFT->value,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'published_at' => $data['published_at'] ?? null,
                'quality_score' => $data['quality_score'] ?? null,
            ]);

            return $post;
        });
    }

    /**
     * Schedule a post for future publishing.
     */
    public function schedulePost(int $agencyId, array $data): SocialPost
    {
        $data['status'] = PostStatus::SCHEDULED->value;

        return $this->createPost($agencyId, $data);
    }

    /**
     * Publish a post immediately.
     */
    public function publishPost(SocialPost $post): array
    {
        $post->update([
            'status' => PostStatus::PUBLISHING->value,
        ]);

        try {
            // Publish to platform
            $result = $this->publishToPlatform($post);

            $post->update([
                'status' => PostStatus::PUBLISHED->value,
                'published_at' => now(),
                'external_post_id' => $result['id'] ?? null,
                'platform_response' => $result,
            ]);

            return [
                'success' => true,
                'message' => 'Post published successfully.',
                'data' => $post,
            ];
        } catch (\Exception $e) {
            $post->update([
                'status' => PostStatus::FAILED->value,
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
                'retry_count' => $post->retry_count + 1,
            ]);

            Log::error("Failed to publish post #{$post->id}: {$e->getMessage()}");

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $post,
            ];
        }
    }

    /**
     * Publish to the social media platform.
     */
    protected function publishToPlatform(SocialPost $post): array
    {
        $account = $post->socialAccount;
        
        if (!$account) {
            throw new \RuntimeException("Social account not found for post #{$post->id}");
        }

        $result = $this->apiService->publish($account, $post);

        if (!$result['success']) {
            throw new \RuntimeException($result['error'] ?? 'Unknown error');
        }

        return $result;
    }

    /**
     * Get post statistics.
     */
    public function getPostStats(SocialPost $post): array
    {
        return [
            'views' => $post->views_count,
            'likes' => $post->likes_count,
            'comments' => $post->comments_count,
            'shares' => $post->shares_count,
            'clicks' => $post->clicks_count,
            'engagement_rate' => $post->engagement_rate,
        ];
    }

    /**
     * Retry a failed post.
     */
    public function retryPost(SocialPost $post): array
    {
        if ($post->retry_count >= 3) {
            return [
                'success' => false,
                'message' => 'Maximum retry attempts reached.',
            ];
        }

        return $this->publishPost($post);
    }
}
