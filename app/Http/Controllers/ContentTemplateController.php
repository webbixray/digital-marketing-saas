<?php

namespace App\Http\Controllers;

use App\Models\ContentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function getAgencyId(Request $request): int
    {
        $userId = Auth::id() ?? $request->user()?->id ?? 0;

        return (int) (DB::table('users')->where('id', $userId)->value('agency_id') ?? 0);
    }

    public function index(Request $request)
    {
        $agencyId = $this->getAgencyId($request);
        $query = ContentTemplate::where('agency_id', $agencyId);

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $templates = $query->orderBy('created_at', 'desc')->paginate(20);
        $platforms = ContentTemplate::PLATFORMS ?? ['twitter', 'facebook', 'instagram', 'linkedin', 'tiktok', 'pinterest'];
        $types = ContentTemplate::TYPES ?? ['post', 'story', 'reel', 'pin', 'article'];

        return view('content-templates.index', compact('templates', 'platforms', 'types'));
    }

    public function create()
    {
        $platforms = ContentTemplate::PLATFORMS ?? ['twitter', 'facebook', 'instagram', 'linkedin', 'tiktok', 'pinterest'];
        $types = ContentTemplate::TYPES ?? ['post', 'story', 'reel', 'pin', 'article'];

        return view('content-templates.create', compact('platforms', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'template_content' => 'required|string',
            'variables' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        $template = ContentTemplate::create([
            'agency_id' => $this->getAgencyId($request),
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            ...$validated,
        ]);

        return redirect()->route('content-templates.show', $template)
            ->with('success', 'Template created.');
    }

    public function show(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbTemplate = DB::table('content_templates')->where('id', $id)->first();

        if (! $dbTemplate || (int) $dbTemplate->agency_id !== $agencyId) {
            abort(403);
        }

        $template = ContentTemplate::find($id);

        return view('content-templates.show', compact('template'));
    }

    public function edit(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbTemplate = DB::table('content_templates')->where('id', $id)->first();

        if (! $dbTemplate || (int) $dbTemplate->agency_id !== $agencyId) {
            abort(403);
        }

        $template = ContentTemplate::find($id);
        $platforms = ContentTemplate::PLATFORMS ?? ['twitter', 'facebook', 'instagram', 'linkedin', 'tiktok', 'pinterest'];
        $types = ContentTemplate::TYPES ?? ['post', 'story', 'reel', 'pin', 'article'];

        return view('content-templates.edit', compact('template', 'platforms', 'types'));
    }

    public function update(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbTemplate = DB::table('content_templates')->where('id', $id)->first();

        if (! $dbTemplate || (int) $dbTemplate->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'template_content' => 'required|string',
            'variables' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'status' => 'required|in:active,inactive',
        ]);

        ContentTemplate::where('id', $id)->update($validated);

        return redirect()->route('content-templates.show', $id)
            ->with('success', 'Template updated.');
    }

    public function destroy(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbTemplate = DB::table('content_templates')->where('id', $id)->first();

        if (! $dbTemplate || (int) $dbTemplate->agency_id !== $agencyId) {
            abort(403);
        }

        $template = ContentTemplate::findOrFail($id);
        $template->forceDelete();

        return redirect()->route('content-templates.index')
            ->with('success', 'Template deleted.');
    }
}
