<?php

namespace App\Services\Search;

use App\Models\AnalyticsEvent;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\ContentAsset;
use App\Models\ContentTemplate;
use App\Models\Invoice;
use App\Models\SearchHistory;
use App\Models\SocialPost;

class GlobalSearchService
{
    public function search(string $query, int $agencyId, string $type = 'all', int $limit = 20): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        $results = [];

        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = $this->searchPosts($query, $agencyId, $type === 'posts' ? $limit : 10);
        }

        if ($type === 'all' || $type === 'campaigns') {
            $results['campaigns'] = $this->searchCampaigns($query, $agencyId, $type === 'campaigns' ? $limit : 10);
        }

        if ($type === 'all' || $type === 'clients') {
            $results['clients'] = $this->searchClients($query, $agencyId, $type === 'clients' ? $limit : 10);
        }

        if ($type === 'all' || $type === 'content') {
            $results['content'] = $this->searchContent($query, $agencyId, $type === 'content' ? $limit : 10);
        }

        if ($type === 'all' || $type === 'analytics') {
            $results['analytics'] = $this->searchAnalytics($query, $agencyId, $type === 'analytics' ? $limit : 10);
        }

        return $results;
    }

    public function searchPosts(string $query, int $agencyId, int $limit = 10): array
    {
        return SocialPost::where('agency_id', $agencyId)
            ->where(function ($q) use ($query) {
                $search = '%' . $query . '%';
                $q->where('content', 'LIKE', $search)
                    ->orWhere('platform', 'LIKE', $search);
            })
            ->limit($limit)
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'title' => \Illuminate\Support\Str::limit($post->content, 60),
                'type' => 'post',
                'url' => route('social.posts.index'),
                'icon' => 'fas fa-pen-nib',
                'subtitle' => ucfirst($post->platform ?? 'social'),
            ])
            ->toArray();
    }

    public function searchCampaigns(string $query, int $agencyId, int $limit = 10): array
    {
        return Campaign::where('agency_id', $agencyId)
            ->where(function ($q) use ($query) {
                $search = '%' . $query . '%';
                $q->where('name', 'LIKE', $search)
                    ->orWhere('description', 'LIKE', $search);
            })
            ->limit($limit)
            ->get()
            ->map(fn ($campaign) => [
                'id' => $campaign->id,
                'title' => $campaign->name,
                'type' => 'campaign',
                'url' => route('campaigns.show', $campaign),
                'icon' => 'fas fa-bullhorn',
                'subtitle' => ucfirst($campaign->status ?? 'draft'),
            ])
            ->toArray();
    }

    public function searchClients(string $query, int $agencyId, int $limit = 10): array
    {
        return Client::where('agency_id', $agencyId)
            ->where(function ($q) use ($query) {
                $search = '%' . $query . '%';
                $q->where('name', 'LIKE', $search)
                    ->orWhere('email', 'LIKE', $search)
                    ->orWhere('company', 'LIKE', $search);
            })
            ->limit($limit)
            ->get()
            ->map(fn ($client) => [
                'id' => $client->id,
                'title' => $client->name,
                'type' => 'client',
                'url' => route('clients.show', $client),
                'icon' => 'fas fa-users',
                'subtitle' => $client->company ?? $client->email ?? '',
            ])
            ->toArray();
    }

    public function searchContent(string $query, int $agencyId, int $limit = 10): array
    {
        $assets = ContentAsset::where('agency_id', $agencyId)
            ->where(function ($q) use ($query) {
                $search = '%' . $query . '%';
                $q->where('name', 'LIKE', $search)
                    ->orWhere('content', 'LIKE', $search);
            })
            ->limit($limit)
            ->get()
            ->map(fn ($asset) => [
                'id' => $asset->id,
                'title' => $asset->name,
                'type' => 'content',
                'url' => route('content.index'),
                'icon' => 'fas fa-folder-open',
                'subtitle' => ucfirst($asset->type ?? 'asset'),
            ])
            ->toArray();

        $templates = ContentTemplate::where('agency_id', $agencyId)
            ->where(function ($q) use ($query) {
                $search = '%' . $query . '%';
                $q->where('name', 'LIKE', $search)
                    ->orWhere('template_content', 'LIKE', $search);
            })
            ->limit($limit)
            ->get()
            ->map(fn ($tpl) => [
                'id' => $tpl->id,
                'title' => $tpl->name,
                'type' => 'content_template',
                'url' => route('content-templates.index'),
                'icon' => 'fas fa-copy',
                'subtitle' => 'Template',
            ])
            ->toArray();

        return array_merge($assets, $templates);
    }

    public function searchAnalytics(string $query, int $agencyId, int $limit = 10): array
    {
        return AnalyticsEvent::where('agency_id', $agencyId)
            ->where(function ($q) use ($query) {
                $search = '%' . $query . '%';
                $q->where('event_type', 'LIKE', $search)
                    ->orWhere('platform', 'LIKE', $search);
            })
            ->limit($limit)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->event_type,
                'type' => 'analytics',
                'url' => route('analytics.index'),
                'icon' => 'fas fa-chart-line',
                'subtitle' => ucfirst($event->platform ?? 'event'),
            ])
            ->toArray();
    }

    public function getRecentSearches(int $userId, int $limit = 10): array
    {
        return SearchHistory::byUser($userId)
            ->recent()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function saveSearch(string $query, string $type, int $count): void
    {
        $user = auth()->user();

        if (! $user || strlen(trim($query)) < 2) {
            return;
        }

        SearchHistory::create([
            'user_id' => $user->id,
            'agency_id' => $user->agency_id,
            'query' => trim($query),
            'type' => $type,
            'results_count' => $count,
            'created_at' => now(),
        ]);
    }

    public function getTypeLabel(string $type): string
    {
        return match ($type) {
            'all' => 'All Results',
            'posts' => 'Posts',
            'campaigns' => 'Campaigns',
            'clients' => 'Clients',
            'content' => 'Content',
            'analytics' => 'Analytics',
            default => ucfirst($type),
        };
    }

    public function getResultIcon(string $type): string
    {
        return match ($type) {
            'post' => 'fas fa-pen-nib',
            'campaign' => 'fas fa-bullhorn',
            'client' => 'fas fa-users',
            'content' => 'fas fa-folder-open',
            'content_template' => 'fas fa-copy',
            'analytics' => 'fas fa-chart-line',
            'invoice' => 'fas fa-file-invoice',
            default => 'fas fa-search',
        };
    }

    public function getResultUrl(array $result): string
    {
        if (! empty($result['url'])) {
            return $result['url'];
        }

        return match ($result['type'] ?? '') {
            'post' => route('social.posts.index'),
            'campaign' => route('campaigns.show', $result['id'] ?? 0),
            'client' => route('clients.show', $result['id'] ?? 0),
            'content' => route('content.index'),
            'content_template' => route('content-templates.index'),
            'analytics' => route('analytics.index'),
            default => route('dashboard'),
        };
    }
}
