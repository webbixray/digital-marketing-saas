<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Social\CommerceIntegrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(private CommerceIntegrationService $commerceService)
    {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $products = $this->commerceService->getProducts($agencyId, [
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'low_stock' => $request->query('low_stock'),
            'tag_id' => $request->query('tag'),
            'sort' => $request->query('sort', 'created_at'),
            'direction' => $request->query('direction', 'desc'),
        ]);

        $stats = [
            'total' => Product::byAgency($agencyId)->count(),
            'active' => Product::byAgency($agencyId)->active()->count(),
            'low_stock' => Product::byAgency($agencyId)->lowStock()->count(),
        ];

        return view('products.index', compact('products', 'stats'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        return view('products.create');
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'sku' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'inventory_count' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'status' => 'required|in:active,draft,discontinued',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
        ]);

        $this->commerceService->createProduct($request->user()->agency_id, $validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully!');
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, Product $product): View|RedirectResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $product->load('tags');

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the product.
     */
    public function edit(Request $request, Product $product): View|RedirectResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $product->load('tags');

        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'sku' => 'nullable|string|max:100',
            'price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'inventory_count' => 'sometimes|required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'status' => 'sometimes|required|in:active,draft,discontinued',
            'images' => 'nullable|array',
            'images.*' => 'string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
        ]);

        $this->commerceService->updateProduct($product->id, $validated);

        return redirect()->route('products.show', $product)
            ->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Request $request, Product $product): RedirectResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $this->commerceService->deleteProduct($product->id);

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * Manage tags for a product.
     */
    public function tags(Request $request, Product $product): JsonResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:attach,detach',
            'tags' => 'required|array',
            'tags.*' => 'string|max:255',
        ]);

        if ($validated['action'] === 'attach') {
            $this->commerceService->tagProduct($product->id, $validated['tags']);
        } else {
            $tagIds = collect($validated['tags'])
                ->filter(fn ($t) => is_numeric($t))
                ->map(fn ($t) => (int) $t)
                ->toArray();

            $this->commerceService->untagProduct($product->id, $tagIds);
        }

        return response()->json([
            'success' => true,
            'tags' => $product->tags,
        ]);
    }

    /**
     * Show product analytics.
     */
    public function analytics(Request $request, Product $product): View|JsonResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $analytics = $this->commerceService->getProductAnalytics($product->id);

        if ($request->wantsJson()) {
            return response()->json($analytics);
        }

        return view('products.analytics', compact('product', 'analytics'));
    }

    /**
     * Show import form.
     */
    public function import(): View
    {
        return view('products.import');
    }

    /**
     * Show export view.
     */
    public function export(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $products = Product::byAgency($agencyId)
            ->with('tags')
            ->orderBy('name')
            ->get();

        return view('products.export', compact('products'));
    }
}
