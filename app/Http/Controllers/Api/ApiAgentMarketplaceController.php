<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentMarketplaceItem;
use App\Services\Agent\Marketplace\AgentInstallerService;
use App\Services\Agent\Marketplace\AgentMarketplaceService;
use App\Services\Agent\Marketplace\AgentRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiAgentMarketplaceController extends Controller
{
    public function __construct(
        private readonly AgentMarketplaceService $marketplaceService,
        private readonly AgentInstallerService $installerService,
        private readonly AgentRatingService $ratingService,
    ) {
        $this->middleware(['auth:sanctum', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'pricing_type', 'search', 'sort', 'featured', 'per_page']);
        $items = $this->marketplaceService->getItems($filters);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $item = $this->marketplaceService->getItem($slug);

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found.',
            ], 404);
        }

        $reviewStats = $this->ratingService->getReviewStats($item->id);

        return response()->json([
            'success' => true,
            'data' => [
                'item' => $item,
                'review_stats' => $reviewStats,
            ],
        ]);
    }

    public function install(Request $request, int $itemId): JsonResponse
    {
        $agencyId = auth()->user()->agency_id;
        $item = AgentMarketplaceItem::findOrFail($itemId);

        $result = $this->installerService->install($item, $agencyId, $request->input('config', []));

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        $this->marketplaceService->installAgent($item->id, $agencyId);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public function configure(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'config' => 'required|array',
        ]);

        $item = AgentMarketplaceItem::findOrFail($itemId);
        $result = $this->installerService->configure($item, $validated['config']);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'config' => $result['config'],
        ]);
    }

    public function myAgents(Request $request): JsonResponse
    {
        $agencyId = auth()->user()->agency_id;
        $installed = $this->marketplaceService->getInstalledAgents($agencyId);

        return response()->json([
            'success' => true,
            'data' => $installed,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $filters = $request->only(['category_id', 'pricing_type', 'sort', 'per_page']);
        $results = $this->marketplaceService->search($validated['q'], $filters);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
