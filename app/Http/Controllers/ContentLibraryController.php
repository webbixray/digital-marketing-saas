<?php

namespace App\Http\Controllers;

use App\Models\ContentAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentLibraryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();

        $query = ContentAsset::where('agency_id', $agencyId);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $query->whereLike('name', $request->search);
        }

        $assets = $query->orderBy('created_at', 'desc')->paginate(15);

        $types = ContentAsset::ASSET_TYPES;

        return view('content.index', compact('agency', 'assets', 'types'));
    }

    public function create(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();
        $types = ContentAsset::ASSET_TYPES;

        return view('content.create', compact('agency', 'types'));
    }

    public function store(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,image,video,audio,document,link',
            'content' => 'required|string',
            'media_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $asset = ContentAsset::create([
            'agency_id' => $agencyId,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            'type' => $validated['type'],
            'content' => $validated['content'],
            'media_url' => $validated['media_url'] ?? null,
            'tags' => $validated['tags'] ?? [],
            'is_public' => $validated['is_public'] ?? false,
            'status' => 'active',
        ]);

        return redirect()->route('content.show', $asset)
            ->with('success', 'Content asset created successfully.');
    }

    public function show(Request $request, $assetId)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();

        $asset = ContentAsset::findOrFail($assetId);

        if ($asset->agency_id !== $agencyId) {
            abort(403);
        }

        return view('content.show', compact('agency', 'asset'));
    }

    public function edit(Request $request, $assetId)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();

        $asset = ContentAsset::findOrFail($assetId);

        if ($asset->agency_id !== $agencyId) {
            abort(403);
        }

        $types = ContentAsset::ASSET_TYPES;

        return view('content.edit', compact('agency', 'asset', 'types'));
    }

    public function update(Request $request, $assetId)
    {
        $agencyId = $request->user()->agency_id;

        $asset = ContentAsset::findOrFail($assetId);

        if ($asset->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'media_url' => 'nullable|url',
            'tags' => 'nullable|array',
            'is_public' => 'boolean',
        ]);

        $asset->update($validated);

        return redirect()->route('content.show', $asset)
            ->with('success', 'Content asset updated successfully.');
    }

    public function destroy(Request $request, $assetId)
    {
        $agencyId = $request->user()->agency_id;

        $asset = ContentAsset::findOrFail($assetId);

        if ($asset->agency_id !== $agencyId) {
            abort(403);
        }

        $asset->delete();

        return redirect()->route('content.index')
            ->with('success', 'Content asset deleted.');
    }
}
