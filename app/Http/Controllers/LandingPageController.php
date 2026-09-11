<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;
        $pages = LandingPage::where('agency_id', $agency->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('landing-pages.index', compact('agency', 'pages'));
    }

    public function create(Request $request)
    {
        $agency = $request->user()->agency;

        return view('landing-pages.create', compact('agency'));
    }

    public function store(Request $request)
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'headline' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url',
            'background_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'button_color' => 'nullable|string|max:7',
        ]);

        $page = LandingPage::create([
            'agency_id' => $agency->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            'title' => $validated['title'] ?? null,
            'headline' => $validated['headline'] ?? null,
            'content' => $validated['content'] ?? null,
            'cta_text' => $validated['cta_text'] ?? null,
            'cta_url' => $validated['cta_url'] ?? null,
            'background_color' => $validated['background_color'] ?? '#ffffff',
            'text_color' => $validated['text_color'] ?? '#333333',
            'button_color' => $validated['button_color'] ?? '#007bff',
            'is_published' => false,
        ]);

        return redirect()->route('landing-pages.show', $page)
            ->with('success', 'Landing page created successfully.');
    }

    public function show(Request $request, LandingPage $landingPage)
    {
        $agency = $request->user()->agency;

        if ($landingPage->agency_id !== $agency->id) {
            abort(403);
        }

        $page = $landingPage;

        return view('landing-pages.show', compact('agency', 'page'));
    }

    public function edit(Request $request, LandingPage $landingPage)
    {
        $agency = $request->user()->agency;

        if ($landingPage->agency_id !== $agency->id) {
            abort(403);
        }

        $types = ContentAsset::ASSET_TYPES;
        $page = $landingPage;

        return view('landing-pages.edit', compact('agency', 'page', 'types'));
    }

    public function update(Request $request, LandingPage $landingPage)
    {
        $agency = $request->user()->agency;

        if ($landingPage->agency_id !== $agency->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'headline' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url',
            'background_color' => 'nullable|string|max-7',
            'text_color' => 'nullable|string|max-7',
            'button_color' => 'nullable|string|max-7',
        ]);

        $landingPage->update($validated);

        return redirect()->route('landing-pages.show', $landingPage)
            ->with('success', 'Landing page updated successfully.');
    }

    public function destroy(Request $request, LandingPage $page)
    {
        $agency = $request->user()->agency;

        if ($page->agency_id !== $agency->id) {
            abort(403);
        }

        $page->delete();
        $agency->decrement('landing_pages_count');

        return redirect()->route('landing-pages.index')
            ->with('success', 'Landing page deleted.');
    }

    public function togglePublish(Request $request, LandingPage $page)
    {
        $agency = $request->user()->agency;

        if ($page->agency_id !== $agency->id) {
            abort(403);
        }

        $page->update([
            'is_published' => ! $page->is_published,
            'published_at' => ! $page->is_published ? now() : null,
        ]);

        return back()->with('success', 'Landing page status updated.');
    }

    /**
     * Public render endpoint for published landing pages.
     */
    public function render(Request $request, $slug)
    {
        $agency = $request->user()->agency;
        $page = LandingPage::where('slug', $slug)
            ->where('is_published', true)
            ->where('agency_id', $agency->id)
            ->firstOrFail();

        $page->incrementViews();

        return view('public.landing-page', compact('page'));
    }
}
