<?php

namespace App\Services\Agent\Marketplace;

use App\Models\AgentMarketplaceItem;
use App\Models\AgentMarketplaceReview;
use Illuminate\Support\Collection;

class AgentRatingService
{
    public function submitReview(int $itemId, int $userId, int $rating, string $title, string $body): array
    {
        $item = AgentMarketplaceItem::findOrFail($itemId);

        if ($rating < 1 || $rating > 5) {
            return [
                'success' => false,
                'message' => 'Rating must be between 1 and 5.',
            ];
        }

        // Check for existing review by this user
        $existing = AgentMarketplaceReview::where('item_id', $itemId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return [
                'success' => false,
                'message' => 'You have already reviewed this agent.',
            ];
        }

        $review = AgentMarketplaceReview::create([
            'item_id' => $itemId,
            'user_id' => $userId,
            'agency_id' => auth()->user()->agency_id ?? null,
            'rating' => $rating,
            'title' => $title,
            'body' => $body,
            'is_verified_purchase' => true,
            'status' => 'approved',
        ]);

        $this->recalculateRating($item);

        return [
            'success' => true,
            'message' => 'Review submitted successfully.',
            'review' => $review,
        ];
    }

    public function getReviews(int $itemId, array $filters = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = AgentMarketplaceReview::with('user')->byItem($itemId)->approved();

        $sort = $filters['sort'] ?? 'recent';

        match ($sort) {
            'helpful' => $query->helpful(),
            default => $query->recent(),
        };

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }

    public function getAverageRating(int $itemId): float
    {
        $item = AgentMarketplaceItem::findOrFail($itemId);

        return (float) $item->rating_avg;
    }

    public function markHelpful(int $reviewId): array
    {
        $review = AgentMarketplaceReview::findOrFail($reviewId);
        $review->increment('helpful_count');

        return [
            'success' => true,
            'helpful_count' => $review->helpful_count,
        ];
    }

    public function getReviewStats(int $itemId): array
    {
        $reviews = AgentMarketplaceReview::byItem($itemId)->approved();

        $total = $reviews->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'average' => 0,
                'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                'verified_count' => 0,
            ];
        }

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = (clone $reviews)->where('rating', $i)->count();
        }

        $verifiedCount = (clone $reviews)->where('is_verified_purchase', true)->count();

        return [
            'total' => $total,
            'average' => round((float) AgentMarketplaceItem::findOrFail($itemId)->rating_avg, 2),
            'distribution' => $distribution,
            'verified_count' => $verifiedCount,
        ];
    }

    private function recalculateRating(AgentMarketplaceItem $item): void
    {
        $stats = AgentMarketplaceReview::byItem($item->id)
            ->approved()
            ->selectRaw('COUNT(*) as count, AVG(rating) as avg')
            ->first();

        $item->update([
            'rating_count' => $stats->count ?? 0,
            'rating_avg' => $stats->avg ? round((float) $stats->avg, 2) : 0,
        ]);
    }
}
