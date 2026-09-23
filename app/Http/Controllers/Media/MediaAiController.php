<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\Media\AiImageGenerationService;
use App\Services\Media\MediaAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MediaAiController extends Controller
{
    public function __construct(
        private readonly AiImageGenerationService $aiService,
        private readonly MediaAnalyticsService $analyticsService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Generate image from prompt.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|min:5|max:500',
            'style' => 'nullable|string|in:photorealistic,illustration,digital-art,minimalist,corporate,social-media,product,abstract',
            'size' => 'nullable|string|in:1024x1024,1024x1792,1792x1024,512x512,768x768',
        ]);

        $prompt = $request->input('prompt');
        $style = $request->input('style', 'photorealistic');
        $size = $request->input('size', '1024x1024');

        $result = $this->aiService->generateImage($prompt, $style, $size);

        return response()->json($result);
    }

    /**
     * AI edit existing image.
     */
    public function edit(Request $request, MediaAsset $asset): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|min:5|max:500',
        ]);

        if ($asset->agency_id !== $request->user()->agency_id) {
            abort(403, 'Unauthorized access to asset.');
        }

        $result = $this->aiService->editImage($asset->file_path, $request->input('prompt'));

        return response()->json($result);
    }

    /**
     * Generate variations of an existing image.
     */
    public function variations(Request $request, MediaAsset $asset): JsonResponse
    {
        $request->validate([
            'count' => 'nullable|integer|min:1|max:8',
        ]);

        if ($asset->agency_id !== $request->user()->agency_id) {
            abort(403, 'Unauthorized access to asset.');
        }

        $count = $request->input('count', 4);
        $result = $this->aiService->generateVariations($asset->file_path, $count);

        return response()->json($result);
    }

    /**
     * Get available AI image generation styles.
     */
    public function styles(): JsonResponse
    {
        $styles = $this->aiService->getAvailableStyles();
        $sizes = $this->aiService->getAvailableSizes();

        return response()->json([
            'styles' => $styles,
            'sizes' => $sizes,
        ]);
    }

    /**
     * Show AI image generation UI.
     */
    public function index(Request $request): View
    {
        $styles = $this->aiService->getAvailableStyles();
        $sizes = $this->aiService->getAvailableSizes();

        return view('media.ai-generate', compact('styles', 'sizes'));
    }

    /**
     * Show media analytics dashboard (web) or return JSON (API).
     */
    public function analytics(Request $request): View|JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $mostUsedAssets = $this->analyticsService->getMostUsedAssets($agencyId);
        $storageTrends = $this->analyticsService->getStorageTrends($agencyId);
        $fileTypeBreakdown = $this->analyticsService->getFileTypeBreakdown($agencyId);
        $uploadActivity = $this->analyticsService->getUploadActivity($agencyId);
        $summary = $this->analyticsService->getSummaryAnalytics($agencyId);

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'most_used_assets' => $mostUsedAssets,
                'storage_trends' => $storageTrends,
                'file_type_breakdown' => $fileTypeBreakdown,
                'upload_activity' => $uploadActivity,
                'summary' => $summary,
            ]);
        }

        return view('media.analytics', compact(
            'mostUsedAssets',
            'storageTrends',
            'fileTypeBreakdown',
            'uploadActivity',
            'summary',
        ));
    }
}
