<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SocialCommercePost;
use App\Services\Social\CommerceIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiProductController extends Controller
{
    public function __construct(private CommerceIntegrationService $commerceService)
    {
        $this->middleware(['auth:sanctum', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $agencyId = $request->user()->agency_id;

            $products = $this->commerceService->getProducts($agencyId, [
                'status' => $request->query('status'),
                'search' => $request->query('search'),
                'sort' => $request->query('sort', 'created_at'),
                'direction' => $request->query('direction', 'desc'),
            ]);

            return response()->json([
                'data' => $products,
                'meta' => [
                    'total' => $products->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API product index failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'sku' => 'nullable|string|max:100',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'inventory_count' => 'required|integer|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'status' => 'required|in:active,draft,discontinued',
                'images' => 'nullable|array',
                'tags' => 'nullable|array',
            ]);

            $product = $this->commerceService->createProduct($request->user()->agency_id, $validated);

            return response()->json(['data' => $product], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('API product store failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create product'], 500);
        }
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $product->load('tags');

        return response()->json(['data' => $product]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'slug' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'sku' => 'nullable|string|max:100',
                'price' => 'sometimes|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'inventory_count' => 'sometimes|integer|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'status' => 'sometimes|in:active,draft,discontinued',
                'images' => 'nullable|array',
                'tags' => 'nullable|array',
            ]);

            $updated = $this->commerceService->updateProduct($product->id, $validated);

            return response()->json(['data' => $updated]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('API product update failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to update product'], 500);
        }
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if ((int) $product->agency_id !== (int) $request->user()->agency_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->commerceService->deleteProduct($product->id);

        return response()->json(null, 204);
    }
}
