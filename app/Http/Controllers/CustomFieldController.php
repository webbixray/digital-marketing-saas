<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomFieldController extends Controller
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
        $fields = CustomField::where('agency_id', $agencyId)
            ->orderBy('sort_order')
            ->paginate(20);

        return view('custom-fields.index', compact('fields'));
    }

    public function create()
    {
        $types = CustomField::FIELD_TYPES;

        return view('custom-fields.create', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,date,select,textarea,boolean',
            'model_type' => 'required|string|max:255',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $field = CustomField::create([
            'agency_id' => $this->getAgencyId($request),
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            ...$validated,
        ]);

        return redirect()->route('custom-fields.show', $field)
            ->with('success', 'Custom field created.');
    }

    public function show(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbField = DB::table('custom_fields')->where('id', $id)->first();

        if (! $dbField || (int) $dbField->agency_id !== $agencyId) {
            abort(403);
        }

        $field = CustomField::find($id);

        return view('custom-fields.show', compact('field'));
    }

    public function edit(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbField = DB::table('custom_fields')->where('id', $id)->first();

        if (! $dbField || (int) $dbField->agency_id !== $agencyId) {
            abort(403);
        }

        $field = CustomField::find($id);
        $types = CustomField::FIELD_TYPES;

        return view('custom-fields.edit', compact('field', 'types'));
    }

    public function update(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbField = DB::table('custom_fields')->where('id', $id)->first();

        if (! $dbField || (int) $dbField->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,number,date,select,textarea,boolean',
            'model_type' => 'required|string|max:255',
            'options' => 'nullable|array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        CustomField::where('id', $id)->update($validated);

        return redirect()->route('custom-fields.show', $id)
            ->with('success', 'Custom field updated.');
    }

    public function destroy(Request $request, $id)
    {
        $agencyId = $this->getAgencyId($request);
        $dbField = DB::table('custom_fields')->where('id', $id)->first();

        if (! $dbField || (int) $dbField->agency_id !== $agencyId) {
            abort(403);
        }

        $field = CustomField::findOrFail($id);
        $field->forceDelete();

        return redirect()->route('custom-fields.index')
            ->with('success', 'Custom field deleted.');
    }
}
