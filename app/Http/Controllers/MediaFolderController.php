<?php

namespace App\Http\Controllers;

use App\Models\MediaFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaFolderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $folders = MediaFolder::byAgency($agencyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id', 'slug', 'sort_order']);

        return response()->json($folders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer',
        ]);

        $agencyId = $request->user()->agency_id;

        $folder = MediaFolder::create([
            'agency_id' => $agencyId,
            'parent_id' => $request->input('parent_id'),
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('name')).'-'.Str::random(4),
            'sort_order' => MediaFolder::byAgency($agencyId)->max('sort_order') + 1,
        ]);

        return response()->json(['success' => true, 'folder' => $folder], 201);
    }

    public function show(Request $request, MediaFolder $media_folder)
    {
        if ((int) $media_folder->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        return response()->json($media_folder);
    }

    public function update(Request $request, MediaFolder $media_folder)
    {
        if ((int) $media_folder->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $media_folder->update([
            'name' => $request->input('name'),
        ]);

        return response()->json(['success' => true, 'folder' => $media_folder]);
    }

    public function destroy(Request $request, MediaFolder $media_folder)
    {
        if ((int) $media_folder->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        try {
            $media_folder->delete();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Folder delete failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Failed to delete folder.'], 500);
        }
    }
}
