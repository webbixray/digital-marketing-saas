<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $features = Feature::orderBy('name')->paginate(20);

        return view('features.index', compact('features'));
    }

    public function create()
    {
        return view('features.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:features',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Feature::create($validated);

        return redirect()->route('features.flags.index')->with('success', 'Feature created.');
    }

    public function show(Feature $feature)
    {
        return view('features.show', compact('feature'));
    }

    public function edit(Feature $feature)
    {
        return view('features.edit', compact('feature'));
    }

    public function update(Request $request, Feature $feature)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $feature->update($validated);

        return redirect()->route('features.flags.index')->with('success', 'Feature updated.');
    }

    public function destroy(Feature $feature)
    {
        $feature->delete();

        return redirect()->route('features.flags.index')->with('success', 'Feature deleted.');
    }
}
