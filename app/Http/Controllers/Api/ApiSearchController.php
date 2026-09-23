<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'agency']);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'type' => 'nullable|string|in:all,posts,campaigns,clients,content,analytics',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $user = $request->user();
        $query = trim($request->input('q'));
        $type = $request->input('type', 'all');
        $limit = $request->input('limit', 20);

        $service = app(GlobalSearchService::class);
        $results = $service->search($query, $user->agency_id, $type, $limit);

        $service->saveSearch($query, $type, collect($results)->flatten(1)->count());

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'query' => $query,
                'type' => $type,
                'total_count' => collect($results)->flatten(1)->count(),
            ],
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
            'data' => $searches,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = app(GlobalSearchService::class);

        return response()->json([
            'success' => true,
            'data' => [
                'recent_searches' => $stats->getRecentSearches($user->id, 5),
            ],
        ]);
    }
}
