<?php

namespace App\Http\Controllers;

use App\Http\Controllers\HandlesErrors;
use App\Jobs\TranslateContentJob;
use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialPost;
use App\Models\User;
use App\Notifications\TranslationCompleteNotification;
use App\Services\AI\ContentTranslationService;
use App\Services\QuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContentTranslationController extends Controller
{
    use HandlesErrors;

    public function __construct(
        private readonly ContentTranslationService $translationService,
        private readonly QuotaService $quotaService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show translation UI.
     */
    public function index(Request $request)
    {
        $agency = $request->user()->agency;

        return view('content.translate', [
            'agency' => $agency,
            'languages' => ContentTranslationService::getSupportedLanguages(),
        ]);
    }

    /**
     * Translate provided content.
     */
    public function translate(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'content' => 'required|string|max:10000',
            'source_lang' => 'required|string|size:2',
            'target_lang' => 'required|string|size:2|different:source_lang',
            'context' => 'nullable|string|in:general,social_post,email_subject,email_body',
        ]);

        if (! $this->translationService->isSupportedLanguage($validated['source_lang'])) {
            return response()->json([
                'success' => false,
                'message' => "Unsupported source language: {$validated['source_lang']}",
            ], 422);
        }

        if (! $this->translationService->isSupportedLanguage($validated['target_lang'])) {
            return response()->json([
                'success' => false,
                'message' => "Unsupported target language: {$validated['target_lang']}",
            ], 422);
        }

        try {
            $result = $this->translationService->translateContent(
                content: $validated['content'],
                sourceLang: $validated['source_lang'],
                targetLang: $validated['target_lang'],
                context: $validated['context'] ?? 'general',
                agency: $agency,
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error("Translation failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Translation failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Translate a social post.
     */
    public function translatePost(Request $request, int $postId): JsonResponse
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'target_lang' => 'required|string|size:2',
            'source_lang' => 'nullable|string|size:2',
        ]);

        /** @var SocialPost|null $post */
        $post = SocialPost::where('agency_id', $agency->id)
            ->where('id', $postId)
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        try {
            $postData = [
                'content' => $post->content,
                'hashtags' => $post->hashtags ?? [],
                'source_lang' => $validated['source_lang'] ?? ($post->platform === 'en' ? 'en' : 'auto'),
                'detected_lang' => $validated['source_lang'] ?? 'en',
            ];

            $result = $this->translationService->translateSocialPost(
                post: $postData,
                targetLang: $validated['target_lang'],
                agency: $agency,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'post_id' => $postId,
                    'original' => $post->content,
                    'translated' => $result['content'],
                    'hashtags' => $result['hashtags'],
                    'source_lang' => $result['source_lang'],
                    'target_lang' => $result['target_lang'],
                    'quality_score' => $result['quality_score'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Post translation failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Translation failed.',
            ], 500);
        }
    }

    /**
     * Translate campaign content.
     */
    public function translateCampaign(Request $request, int $campaignId): JsonResponse
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'target_lang' => 'required|string|size:2',
            'source_lang' => 'nullable|string|size:2',
        ]);

        /** @var Campaign|null $campaign */
        $campaign = Campaign::where('agency_id', $agency->id)
            ->where('id', $campaignId)
            ->first();

        if (! $campaign) {
            return response()->json([
                'success' => false,
                'message' => 'Campaign not found.',
            ], 404);
        }

        try {
            // Dispatch batch translation for campaign posts
            $posts = $campaign->posts()->where('agency_id', $agency->id)->get();
            $items = $posts->map(fn ($post) => [
                'content' => $post->content,
                'context' => 'social_post',
            ])->toArray();

            if (empty($items)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'campaign_id' => $campaignId,
                        'message' => 'No posts to translate.',
                        'results' => [],
                    ],
                ]);
            }

            // Dispatch batch job
            TranslateContentJob::dispatch(
                agency: $agency,
                items: $items,
                sourceLang: $validated['source_lang'] ?? 'en',
                targetLang: $validated['target_lang'],
                userId: $request->user()->id,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'campaign_id' => $campaignId,
                    'message' => 'Translation job dispatched. You will be notified when complete.',
                    'posts_count' => count($items),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Campaign translation failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Translation dispatch failed.',
            ], 500);
        }
    }

    /**
     * Get supported languages.
     */
    public function supportedLanguages(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => ContentTranslationService::getSupportedLanguages(),
        ]);
    }

    /**
     * Detect language of provided text.
     */
    public function detectLanguage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:2000',
        ]);

        $agency = $request->user()->agency;

        try {
            $result = $this->translationService->detectLanguage($validated['text'], $agency);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error("Language detection failed: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Language detection failed.',
            ], 500);
        }
    }
}
