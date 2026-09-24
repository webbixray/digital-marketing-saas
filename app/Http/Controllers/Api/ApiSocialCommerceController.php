<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialCommercePost;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiSocialCommerceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $posts = SocialCommercePost::byAgency($agencyId)
            ->with(['socialPost', 'product'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $posts,
            'meta' => ['total' => $posts->total()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'social_post_id' => 'required|exists:social_posts,id',
            'platform' => 'required|string|in:facebook,instagram,tiktok',
        ]);

        $post = SocialCommercePost::create([
            'agency_id' => $request->user()->agency_id,
            'product_id' => $validated['product_id'],
            'social_post_id' => $validated['social_post_id'],
            'platform' => $validated['platform'],
        ]);

        return response()->json(['data' => $post], 201);
    }

    public function show(int $id): JsonResponse
    {
        $post = SocialCommercePost::with(['socialPost', 'product'])->findOrFail($id);
        return response()->json(['data' => $post]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = SocialCommercePost::findOrFail($id);
        $validated = $request->validate([
            'platform' => 'sometimes|string|in:facebook,instagram,tiktok',
        ]);

        $post->update($validated);

        return response()->json(['data' => $post]);
    }

    public function destroy(int $id): JsonResponse
    {
        $post = SocialCommercePost::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'Social commerce post deleted.']);
    }

    public function analytics(int $id): JsonResponse
    {
        $post = SocialCommercePost::findOrFail($id);

        return response()->json([
            'clicks' => $post->clicks,
            'conversions' => $post->conversions,
            'revenue' => $post->revenue,
            'ctr' => $post->clicks > 0 ? round($post->conversions / $post->clicks * 100, 2) : 0,
        ]);
    }

    public function shopifyConnect(Request $request): JsonResponse
    {
        // Placeholder for Shopify OAuth connection
        return response()->json(['message' => 'Shopify connection initiated.']);
    }

    public function shopifyDisconnect(Request $request): JsonResponse
    {
        // Placeholder for Shopify disconnection
        return response()->json(['message' => 'Shopify disconnected.']);
    }

    public function wooConnect(Request $request): JsonResponse
    {
        // Placeholder for WooCommerce connection
        return response()->json(['message' => 'WooCommerce connection initiated.']);
    }

    public function wooDisconnect(Request $request): JsonResponse
    {
        // Placeholder for WooCommerce disconnection
        return response()->json(['message' => 'WooCommerce disconnected.']);
    }
}
