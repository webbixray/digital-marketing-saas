<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentMarketplaceItemRequest;
use App\Models\AgentMarketplaceItem;
use App\Services\Agent\Marketplace\AgentInstallerService;
use App\Services\Agent\Marketplace\AgentMarketplaceService;
use App\Services\Agent\Marketplace\AgentRatingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentMarketplaceController extends Controller
{
    public function __construct(
        private readonly AgentMarketplaceService $marketplaceService,
        private readonly AgentInstallerService $installerService,
        private readonly AgentRatingService $ratingService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['category_id', 'pricing_type', 'search', 'sort', 'featured']);
        $items = $this->marketplaceService->getItems($filters);
        $categories = $this->marketplaceService->getCategories();
        $featured = $this->marketplaceService->getFeatured();

        return view('agent-marketplace.index', compact('items', 'categories', 'featured', 'filters'));
    }

    public function show(string $slug): View
    {
        $item = $this->marketplaceService->getItem($slug);

        if (! $item) {
            abort(404, 'Agent not found.');
        }

        $reviews = $this->ratingService->getReviews($item->id);
        $reviewStats = $this->ratingService->getReviewStats($item->id);
        $installStatus = $this->installerService->getInstallationStatus($item->id, auth()->user()->agency_id);

        return view('agent-marketplace.show', compact('item', 'reviews', 'reviewStats', 'installStatus'));
    }

    public function install(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:agent_marketplace_items,id',
        ]);

        $agencyId = auth()->user()->agency_id;
        $item = AgentMarketplaceItem::findOrFail($validated['item_id']);

        $result = $this->installerService->install($item, $agencyId, $request->input('config', []));

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $this->marketplaceService->installAgent($item->id, $agencyId);

        return back()->with('success', $result['message']);
    }

    public function configure(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:agent_marketplace_items,id',
            'config' => 'required|array',
        ]);

        $item = AgentMarketplaceItem::findOrFail($validated['item_id']);
        $result = $this->installerService->configure($item, $validated['config']);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        return back()->with('success', $result['message']);
    }

    public function myAgents(Request $request): View
    {
        $agencyId = auth()->user()->agency_id;
        $installed = $this->marketplaceService->getInstalledAgents($agencyId);

        return view('agent-marketplace.my-agents', compact('installed'));
    }

    public function create(): View
    {
        $categories = $this->marketplaceService->getCategories();

        return view('agent-marketplace.create', compact('categories'));
    }

    public function store(StoreAgentMarketplaceItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['agency_id'] = auth()->user()->agency_id;
        $validated['status'] = 'pending';
        $validated['is_approved'] = false;

        // Parse textarea arrays
        if (! empty($validated['features'])) {
            $validated['features'] = array_filter(array_map('trim', explode("\n", $validated['features'])));
        }
        if (! empty($validated['requirements'])) {
            $validated['requirements'] = array_filter(array_map('trim', explode("\n", $validated['requirements'])));
        }
        if (! empty($validated['tags'])) {
            $validated['tags'] = array_filter(array_map('trim', explode(',', $validated['tags'])));
        }

        $item = AgentMarketplaceItem::create($validated);

        return redirect()->route('agent-marketplace.show', $item->slug)
            ->with('success', 'Agent submitted for review successfully.');
    }

    public function uninstall(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_id' => 'required|integer|exists:agent_marketplace_items,id',
        ]);

        $agencyId = auth()->user()->agency_id;
        $item = AgentMarketplaceItem::findOrFail($validated['item_id']);

        $result = $this->installerService->uninstall($item, $agencyId);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        $this->marketplaceService->uninstallAgent($item->id, $agencyId);

        return back()->with('success', $result['message']);
    }
}
