<?php

namespace App\Http\Controllers;

use App\Models\FeatureFlag;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeatureFlagController extends Controller
{
    private FeatureFlagService $featureFlagService;

    public function __construct(FeatureFlagService $featureFlagService)
    {
        $this->middleware(['auth', 'agency']);
        $this->featureFlagService = $featureFlagService;
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;
        $flags = FeatureFlag::where('agency_id', $agency->id)
            ->orderBy('feature_name')
            ->paginate(20);

        return view('feature-flags.index', compact('flags'));
    }

    public function create()
    {
        return view('feature-flags.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'feature_key' => 'required|string|max:255',
            'feature_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'enabled' => 'boolean',
            'required_plan' => 'nullable|string',
            'minimum_version' => 'nullable|string',
        ]);

        $agency = $request->user()->agency;
        $existing = FeatureFlag::where('agency_id', $agency->id)
            ->where('feature_key', $data['feature_key'])
            ->first();

        if ($existing) {
            return back()->withErrors(['feature_key' => 'Feature flag already exists.']);
        }

        $agency->featureFlags()->create($data + ['enabled' => $data['enabled'] ?? true]);

        if ($data['enabled'] ?? true) {
            $this->featureFlagService->enable($agency, $data['feature_key']);
        } else {
            $this->featureFlagService->disable($agency, $data['feature_key']);
        }

        Log::info('Feature flag created', ['key' => $request->input('feature_key'), 'agency_id' => $request->user()->agency_id]);

        return redirect()->route('feature-flags.index')->with('success', 'Feature flag created.');
    }

    public function show(Request $request, FeatureFlag $flag)
    {
        $agency = $request->user()->agency;
        if ($flag->agency_id !== $agency->id) {
            abort(403);
        }

        return view('feature-flags.show', compact('flag'));
    }

    public function edit(Request $request, FeatureFlag $flag)
    {
        $agency = $request->user()->agency;
        if ($flag->agency_id !== $agency->id) {
            abort(403);
        }

        return view('feature-flags.edit', compact('flag'));
    }

    public function update(Request $request, FeatureFlag $flag)
    {
        $agency = $request->user()->agency;
        if ($flag->agency_id !== $agency->id) {
            abort(403);
        }

        $data = $request->validate([
            'feature_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'enabled' => 'boolean',
            'required_plan' => 'nullable|string',
            'minimum_version' => 'nullable|string',
        ]);

        $flag->update($data + ['enabled' => $data['enabled'] ?? true]);

        if ($data['enabled'] ?? true) {
            $this->featureFlagService->enable($agency, $flag->feature_key);
        } else {
            $this->featureFlagService->disable($agency, $flag->feature_key);
        }

        Log::info('Feature flag updated', ['flag_id' => $flag->id, 'agency_id' => $request->user()->agency_id]);

        return redirect()->route('feature-flags.index')->with('success', 'Feature flag updated.');
    }

    public function destroy(Request $request, FeatureFlag $flag)
    {
        $agency = $request->user()->agency;
        if ($flag->agency_id !== $agency->id) {
            abort(403);
        }

        $flag->delete();

        $this->featureFlagService->clearCache($agency->id);

        Log::warning('Feature flag deleted', ['flag_id' => $flag->id, 'agency_id' => $request->user()->agency_id]);

        return redirect()->route('feature-flags.index')->with('success', 'Feature flag deleted.');
    }

    public function check(Request $request, string $featureCode)
    {
        $agency = $request->user()->agency;
        $enabled = $this->featureFlagService->isEnabled($agency, $featureCode);

        return response()->json([
            'feature' => $featureCode,
            'enabled' => $enabled,
        ]);
    }

    public function all(Request $request)
    {
        $agency = $request->user()->agency;
        $flags = $this->featureFlagService->getAll($agency);

        return response()->json([
            'features' => $flags,
        ]);
    }
}
