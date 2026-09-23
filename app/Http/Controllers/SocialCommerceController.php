<?php

namespace App\Http\Controllers;

use App\Models\SocialCommercePost;
use App\Models\SocialPost;
use App\Models\Product;
use App\Services\Social\CommerceIntegrationService;
use App\Services\Social\ShopifyIntegrationService;
use App\Services\Social\WooCommerceIntegrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class SocialCommerceController extends Controller
{
    public function __construct(
        private CommerceIntegrationService $commerceService,
        private ShopifyIntegrationService $shopifyService,
        private WooCommerceIntegrationService $wooService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display a listing of social commerce posts.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $query = SocialCommercePost::byAgency($agencyId)
            ->with(['socialPost', 'product'])
            ->orderBy('created_at', 'desc');

        if ($request->query('product_id')) {
            $query->byProduct((int) $request->query('product_id'));
        }

        if ($request->query('post_id')) {
            $query->byPost((int) $request->query('post_id'));
        }

        if ($request->query('this_month')) {
            $query->thisMonth();
        }

        $posts = $query->paginate(20);

        $totals = [
            'clicks' => SocialCommercePost::byAgency($agencyId)->sum('clicks'),
            'conversions' => SocialCommercePost::byAgency($agencyId)->sum('conversions'),
            'revenue' => SocialCommercePost::byAgency($agencyId)->sum('revenue'),
        ];

        return view('social-commerce.index', compact('posts', 'totals'));
    }

    /**
     * Show the form for creating a commerce post.
     */
    public function create(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $posts = SocialPost::byAgency($agencyId)
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(50)
            ->get();

        $products = Product::byAgency($agencyId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('social-commerce.create', compact('posts', 'products'));
    }

    /**
     * Store a new commerce post.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'social_post_id' => 'required|exists:social_posts,id',
            'product_id' => 'required|exists:products,id',
            'shop_url' => 'nullable|url|max:500',
            'discount_code' => 'nullable|string|max:100',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ]);

        $agencyId = $request->user()->agency_id;

        $utmParams = array_filter([
            'utm_source' => $validated['utm_source'] ?? 'social',
            'utm_medium' => $validated['utm_medium'] ?? 'social_commerce',
            'utm_campaign' => $validated['utm_campaign'] ?? null,
        ]);

        SocialCommercePost::create([
            'agency_id' => $agencyId,
            'social_post_id' => $validated['social_post_id'],
            'product_id' => $validated['product_id'],
            'shop_url' => $validated['shop_url'] ?? null,
            'discount_code' => $validated['discount_code'] ?? null,
            'utm_params' => $utmParams,
            'clicks' => 0,
            'conversions' => 0,
            'revenue' => 0,
            'created_at' => now(),
        ]);

        return redirect()->route('social-commerce.index')
            ->with('success', 'Commerce post linked successfully!');
    }

    /**
     * Display a specific commerce post.
     */
    public function show(Request $request, SocialCommercePost $post): View|RedirectResponse
    {
        if ((int) $post->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $post->load(['socialPost', 'product']);

        return view('social-commerce.show', compact('post'));
    }

    /**
     * Track a click.
     */
    public function track(Request $request, SocialCommercePost $post): JsonResponse
    {
        $this->commerceService->trackClick($post->id);

        return response()->json(['success' => true]);
    }

    /**
     * Show commerce analytics.
     */
    public function analytics(Request $request): View|JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $analytics = [
            'top_products' => $this->commerceService->getTopProducts($agencyId, 10),
            'this_month' => [
                'clicks' => SocialCommercePost::byAgency($agencyId)->thisMonth()->sum('clicks'),
                'conversions' => SocialCommercePost::byAgency($agencyId)->thisMonth()->sum('conversions'),
                'revenue' => SocialCommercePost::byAgency($agencyId)->thisMonth()->sum('revenue'),
            ],
        ];

        if ($request->wantsJson()) {
            return response()->json($analytics);
        }

        return view('social-commerce.analytics', compact('analytics'));
    }

    /**
     * Connect Shopify.
     */
    public function shopifyConnect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'shop_domain' => 'required|string|max:255',
            'access_token' => 'required|string|max:255',
        ]);

        $result = $this->shopifyService->connect(
            $request->user()->agency_id,
            $validated
        );

        $method = $result['success'] ? 'success' : 'error';

        return redirect()->route('social-commerce.index')->with($method, $result['message']);
    }

    /**
     * Disconnect Shopify.
     */
    public function shopifyDisconnect(Request $request): RedirectResponse
    {
        $result = $this->shopifyService->disconnect($request->user()->agency_id);

        $method = $result['success'] ? 'success' : 'error';

        return redirect()->route('social-commerce.index')->with($method, $result['message']);
    }

    /**
     * Connect WooCommerce.
     */
    public function wooConnect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_url' => 'required|url|max:500',
            'consumer_key' => 'required|string|max:255',
            'consumer_secret' => 'required|string|max:255',
        ]);

        $result = $this->wooService->connect(
            $request->user()->agency_id,
            $validated
        );

        $method = $result['success'] ? 'success' : 'error';

        return redirect()->route('social-commerce.index')->with($method, $result['message']);
    }

    /**
     * Disconnect WooCommerce.
     */
    public function wooDisconnect(Request $request): RedirectResponse
    {
        $result = $this->wooService->disconnect($request->user()->agency_id);

        $method = $result['success'] ? 'success' : 'error';

        return redirect()->route('social-commerce.index')->with($method, $result['message']);
    }
}
