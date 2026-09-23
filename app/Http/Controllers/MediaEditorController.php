<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use App\Services\Media\MediaQuotaService;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MediaEditorController extends Controller
{
    public function __construct(
        private readonly MediaQuotaService $quotaService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function edit(Request $request, MediaAsset $asset)
    {
        if ($asset->agency_id !== $request->user()->agency_id) {
            abort(403);
        }

        if ($asset->file_type !== 'image') {
            return redirect()->route('media.show', $asset)
                ->with('error', 'Only images can be edited.');
        }

        $agencyId = $request->user()->agency_id;
        $quota = $this->quotaService->getQuota($agencyId);
        $folders = MediaFolder::byAgency($agencyId)
            ->orderBy('name')
            ->get();

        return view('media.edit', compact('asset', 'quota', 'folders'));
    }

    public function update(Request $request, MediaAsset $asset, MediaUploadService $uploadService)
    {
        if ($asset->agency_id !== $request->user()->agency_id) {
            abort(403);
        }

        $request->validate([
            'image_data' => 'required|string',
        ]);

        try {
            $imageData = $request->input('image_data');

            if (! str_starts_with($imageData, 'data:image/')) {
                throw new InvalidArgumentException('Invalid image data format.');
            }

            $base64Data = substr($imageData, strpos($imageData, ',') + 1);
            $decodedData = base64_decode($base64Data);

            if ($decodedData === false || strlen($decodedData) < 10) {
                throw new InvalidArgumentException('Failed to decode image data.');
            }

            $fileSize = strlen($decodedData);
            $agencyId = $request->user()->agency_id;

            if (! $this->quotaService->canUpload($agencyId, $fileSize)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Storage quota exceeded. Cannot save edited image.',
                ], 413);
            }

            $agency = Agency::findOrFail($agencyId);
            $directory = "media/{$agency->id}/{$asset->folder}";
            $filename = pathinfo($asset->file_path, PATHINFO_FILENAME).'-edited-'.Str::random(6);
            $extension = match (true) {
                str_contains($imageData, 'data:image/png') => '.png',
                str_contains($imageData, 'data:image/gif') => '.gif',
                str_contains($imageData, 'data:image/webp') => '.webp',
                default => '.jpg',
            };
            $newPath = $directory.'/'.$filename.$extension;

            Storage::disk('public')->put($newPath, $decodedData);

            $uploadService->delete($asset);
            $asset->update([
                'file_path' => $newPath,
                'file_size' => $fileSize,
            ]);

            $this->quotaService->clearCache($agencyId);

            return response()->json([
                'success' => true,
                'message' => 'Image updated successfully.',
                'url' => asset('storage/'.$newPath),
            ]);
        } catch (InvalidArgumentException $e) {
            Log::warning('Media editor update failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Media editor update error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save edited image.',
            ], 500);
        }
    }

    public function getQuota(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $quota = $this->quotaService->getQuota($agencyId);

        return response()->json($quota);
    }

    public function getFolders(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $folders = MediaFolder::byAgency($agencyId)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'slug']);

        return response()->json($folders);
    }
}
