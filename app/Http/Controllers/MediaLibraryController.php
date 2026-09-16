<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Services\MediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaLibraryController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $mediaService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $query = MediaAsset::where('agency_id', $agencyId);

        if ($request->filled('type')) {
            $query->where('file_type', $request->type);
        }

        if ($request->filled('folder')) {
            $query->where('folder', $request->folder);
        }

        if ($request->filled('search')) {
            $query->whereLike('name', $request->search);
        }

        $assets = $query->orderBy('created_at', 'desc')->paginate(24);
        $folders = MediaAsset::where('agency_id', $agencyId)->distinct()->pluck('folder');
        $totalSize = MediaAsset::where('agency_id', $agencyId)->sum('file_size');

        return view('media.index', compact('assets', 'folders', 'totalSize'));
    }

    public function create(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $folders = MediaAsset::where('agency_id', $agencyId)->distinct()->pluck('folder');

        return view('media.create', compact('folders'));
    }

    public function store(Request $request, MediaUploadService $uploadService)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,svg,mp4,mov,avi,pdf,doc,docx,xls,xlsx,ppt,pptx,zip',
            'folder' => 'nullable|string|max:255',
        ], [
            'files.max' => 'You can upload a maximum of 20 files at once.',
            'files.*.max' => 'Each file must be less than 50MB.',
            'files.*.mimes' => 'Invalid file type. Allowed: images, videos, documents, archives.',
        ]);

        $agency = $request->user()->agency;
        $uploaded = [];
        $errors = [];

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'video/mp4', 'video/quicktime', 'video/x-msvideo',
            'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip', 'application/x-zip-compressed',
        ];

        foreach ($request->file('files') as $file) {
            try {
                $mime = $file->getMimeType();
                if (!in_array($mime, $allowedMimes)) {
                    $errors[] = "File {$file->getClientOriginalName()} has invalid MIME type.";
                    continue;
                }

                if (str_starts_with($mime, 'image/') && !str_contains($mime, 'svg')) {
                    $imageInfo = @getimagesize($file->getRealPath());
                    if (!$imageInfo) {
                        $errors[] = "File {$file->getClientOriginalName()} is not a valid image.";
                        continue;
                    }
                }

                $asset = $uploadService->upload($file, $agency, $request->user()->id, [
                    'folder' => $request->input('folder', 'uncategorized'),
                ]);
                $uploaded[] = $asset;
            } catch (\Exception $e) {
                $errors[] = "File {$file->getClientOriginalName()} upload failed.";
                Log::error('File upload failed', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = count($uploaded) . ' file(s) uploaded successfully.';
        if (count($errors) > 0) {
            $message .= ' ' . count($errors) . ' file(s) failed.';
        }

        return redirect()->route('media.index')
            ->with('success', $message)
            ->with('upload_errors', $errors);
    }

    public function show(Request $request, MediaAsset $asset)
    {
        if ($asset->agency_id !== $request->user()->agency_id) {
            abort(403);
        }

        return view('media.show', compact('asset'));
    }

    public function destroy(Request $request, MediaAsset $asset, MediaUploadService $uploadService)
    {
        if ($asset->agency_id !== $request->user()->agency_id) {
            abort(403);
        }

        try {
            $uploadService->delete($asset);
            return redirect()->route('media.index')->with('success', 'File deleted successfully.');
        } catch (\Exception $e) {
            Log::error('File delete failed', ['asset_id' => $asset->id, 'error' => $e->getMessage()]);
            return redirect()->route('media.index')->with('error', 'Failed to delete file.');
        }
    }

    public function download(Request $request, MediaAsset $asset)
    {
        $agency = $request->user()->agency;
        if ($asset->agency_id !== $agency->id) {
            abort(403);
        }
        $path = storage_path('app/public/'.$asset->file_path);

        return response()->download($path, $asset->name);
    }

    public function duplicate(Request $request, MediaAsset $asset, MediaUploadService $uploadService)
    {
        $agency = $request->user()->agency;
        if ($asset->agency_id !== $agency->id) {
            abort(403);
        }
        $uploadService->duplicate($asset);

        return back()->with('success', 'File duplicated.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $agency = $request->user()->agency;

        // Use chunk() for efficient bulk processing
        $deletedCount = 0;
        MediaAsset::where('agency_id', $agency->id)
            ->whereIn('id', $request->ids)
            ->chunk(100, function ($assets) use (&$deletedCount) {
                foreach ($assets as $asset) {
                    $this->mediaService->delete($asset);
                    $deletedCount++;
                }
            });

        return response()->json(['success' => true, 'count' => $deletedCount]);
    }
}
