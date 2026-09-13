<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Services\MediaUploadService;
use Illuminate\Http\Request;

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
            $query->where('name', 'like', '%'.$request->search.'%');
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
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,mp4,pdf,doc,docx',
            'folder' => 'nullable|string|max:255',
        ]);

        $agency = $request->user()->agency;
        $uploaded = [];

        foreach ($request->file('files') as $file) {
            $asset = $uploadService->upload($file, $agency, $request->user()->id, [
                'folder' => $request->input('folder', 'uncategorized'),
            ]);
            $uploaded[] = $asset;
        }

        return redirect()->route('media.index')
            ->with('success', count($uploaded).' file(s) uploaded successfully.');
    }

    public function show(Request $request, MediaAsset $asset)
    {
        $agency = $request->user()->agency;
        if ($asset->agency_id !== $agency->id) {
            abort(403);
        }

        return view('media.show', compact('agency', 'asset'));
    }

    public function destroy(Request $request, MediaAsset $asset, MediaUploadService $uploadService)
    {
        $agency = $request->user()->agency;
        if ($asset->agency_id !== $agency->id) {
            abort(403);
        }
        $uploadService->delete($asset);

        return redirect()->route('media.index')->with('success', 'File deleted.');
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
