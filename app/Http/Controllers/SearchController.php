<?php

namespace App\Http\Controllers;

use App\Models\SearchHistory;
use App\Services\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = trim($request->get('q', ''));
        $type = $this->resolveType($request->get('type', 'all'));
        $results = [];

        if (strlen($query) >= 2) {
            $service = app(GlobalSearchService::class);
            $results = $service->search($query, $user->agency_id, $type, 20);

            $totalCount = collect($results)->flatten(1)->count();
            $service->saveSearch($query, $type, $totalCount);
        }

        $recent = SearchHistory::byUser($user->id)
            ->recent()
            ->limit(10)
            ->get();

        return view('search.index', compact('results', 'query', 'type', 'recent'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'type' => 'nullable|string|in:all,posts,campaigns,clients,content,analytics',
        ]);

        $user = $request->user();
        $query = trim($request->input('query'));
        $type = $request->input('type', 'all');

        $service = app(GlobalSearchService::class);
        $results = $service->search($query, $user->agency_id, $type, 20);

        $totalCount = collect($results)->flatten(1)->count();
        $service->saveSearch($query, $type, $totalCount);

        return response()->json([
            'success' => true,
            'results' => $results,
            'total_count' => $totalCount,
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $searches = app(GlobalSearchService::class)->getRecentSearches(
            $request->user()->id,
            10
        );

        return response()->json([
            'success' => true,
            'recent' => $searches,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $entry = SearchHistory::byUser(auth()->id())->findOrFail($id);
        $entry->delete();

        return response()->json(['success' => true]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'total_searches' => SearchHistory::byUser($user->id)->count(),
            'total_clicks' => SearchHistory::byUser($user->id)
                ->whereNotNull('clicked_result')
                ->count(),
            'top_queries' => SearchHistory::byUser($user->id)
                ->selectRaw('query, COUNT(*) as count')
                ->groupBy('query')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'query')
                ->toArray(),
            'type_distribution' => SearchHistory::byUser($user->id)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    private function resolveType(?string $type): string
    {
        return in_array($type, ['all', 'posts', 'campaigns', 'clients', 'content', 'analytics'])
            ? $type
            : 'all';
    }
}
