<?php

namespace App\Services\Agent\Marketplace;

use App\Models\AgentMarketplaceCategory;
use App\Models\AgentMarketplaceItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AgentMarketplaceService
{
    public function getItems(array $filters = []): LengthAwarePaginator
    {
        $query = AgentMarketplaceItem::with('category')->approved();

        if (! empty($filters['category_id'])) {
            $query->byCategory((int) $filters['category_id']);
        }

        if (! empty($filters['pricing_type'])) {
            $query->where('pricing_type', $filters['pricing_type']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['featured']) && $filters['featured']) {
            $query->featured();
        }

        $sortField = $filters['sort'] ?? 'popular';
        $sortDirection = $filters['direction'] ?? 'desc';

        match ($sortField) {
            'name' => $query->orderBy('name', $sortDirection),
            'rating' => $query->orderBy('rating_avg', $sortDirection),
            'installs' => $query->orderBy('install_count', $sortDirection),
            'newest' => $query->orderBy('published_at', $sortDirection),
            default => $query->popular(),
        };

        $perPage = $filters['per_page'] ?? 12;

        return $query->paginate($perPage);
    }

    public function getItem(string $slug): ?AgentMarketplaceItem
    {
        return AgentMarketplaceItem::with(['category', 'reviews' => function ($q) {
            $q->approved()->recent();
        }])->where('slug', $slug)->first();
    }

    public function installAgent(int $itemId, int $agencyId): array
    {
        $item = AgentMarketplaceItem::findOrFail($itemId);

        if (! $item->is_approved || $item->status !== 'approved') {
            return [
                'success' => false,
                'message' => 'This agent is not available for installation.',
            ];
        }

        // Record installation — actual install handled by AgentInstallerService
        $item->increment('install_count');

        return [
            'success' => true,
            'message' => "{$item->name} has been installed successfully.",
            'item' => $item,
        ];
    }

    public function uninstallAgent(int $itemId, int $agencyId): array
    {
        $item = AgentMarketplaceItem::findOrFail($itemId);

        // Record uninstall — actual cleanup handled by AgentInstallerService
        if ($item->install_count > 0) {
            $item->decrement('install_count');
        }

        return [
            'success' => true,
            'message' => "{$item->name} has been uninstalled.",
            'item' => $item,
        ];
    }

    public function getInstalledAgents(int $agencyId): Collection
    {
        // In a full implementation, this would query an installations pivot table
        // For v7.0, returns approved items associated with the agency
        return AgentMarketplaceItem::where('agency_id', $agencyId)
            ->approved()
            ->orderBy('name')
            ->get();
    }

    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        return $this->getItems(array_merge($filters, ['search' => $query]));
    }

    public function getFeatured(): Collection
    {
        return AgentMarketplaceItem::with('category')
            ->approved()
            ->featured()
            ->orderByDesc('rating_avg')
            ->limit(6)
            ->get();
    }

    public function getCategories(): Collection
    {
        return AgentMarketplaceCategory::active()->bySortOrder()->get();
    }

    public function getPopular(int $limit = 10): Collection
    {
        return AgentMarketplaceItem::approved()
            ->popular()
            ->limit($limit)
            ->get();
    }
}
