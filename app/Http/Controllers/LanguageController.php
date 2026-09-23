<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Services\Localization\LocaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    /**
     * List all supported (active) languages.
     */
    public function index(): JsonResponse
    {
        $languages = Language::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Language $lang) => [
                'code' => $lang->code,
                'name' => $lang->name,
                'native_name' => $lang->native_name,
                'flag_emoji' => $lang->flag_emoji,
                'is_rtl' => $lang->is_rtl,
            ]);

        return response()->json([
            'success' => true,
            'data' => $languages,
        ]);
    }

    /**
     * Switch the current user's locale.
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => ['required', 'string', 'max:10'],
        ]);

        $locale = $request->input('locale');

        /** @var LocaleService $localeService */
        $localeService = app(LocaleService::class);

        if (! $localeService->isValidLocale($locale)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported locale: ' . $locale,
            ], 422);
        }

        $localeService->setLocale($locale);

        // Update user's locale if authenticated
        $user = Auth::user();
        if ($user) {
            $user->locale = $locale;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Language switched successfully',
            'locale' => $locale,
            'direction' => $localeService->getDirection(),
        ]);
    }

    /**
     * Get the current locale info.
     */
    public function current(): JsonResponse
    {
        /** @var LocaleService $localeService */
        $localeService = app(LocaleService::class);
        $locale = $localeService->getLocale();
        $language = $localeService->getLanguageByCode($locale);

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $locale,
                'direction' => $localeService->getDirection(),
                'language' => $language ? [
                    'code' => $language->code,
                    'name' => $language->name,
                    'native_name' => $language->native_name,
                    'flag_emoji' => $language->flag_emoji,
                    'is_rtl' => $language->is_rtl,
                ] : null,
            ],
        ]);
    }
}
