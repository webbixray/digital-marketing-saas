<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FormController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;
        $forms = Form::where('agency_id', $agency->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('forms.index', compact('agency', 'forms'));
    }

    public function create(Request $request)
    {
        $agency = $request->user()->agency;

        return view('forms.create', compact('agency'));
    }

    public function store(Request $request)
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fields' => 'required|array|min:1',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);

        $form = Form::create([
            'agency_id' => $agency->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            'fields' => $validated['fields'],
            'success_message' => $validated['success_message'] ?? 'Thank you!',
            'redirect_url' => $validated['redirect_url'] ?? null,
            'is_published' => false,
        ]);

        return redirect()->route('forms.show', $form)
            ->with('success', 'Form created successfully.');
    }

    public function show(Request $request, Form $form)
    {
        $agency = $request->user()->agency;

        if ($form->agency_id !== $agency->id) {
            abort(403);
        }

        $responses = $form->responses()->orderBy('submitted_at', 'desc')->paginate(25);

        return view('forms.show', compact('agency', 'form', 'responses'));
    }

    public function edit(Request $request, Form $form)
    {
        $agency = $request->user()->agency;

        if ($form->agency_id !== $agency->id) {
            abort(403);
        }

        return view('forms.edit', compact('agency', 'form'));
    }

    public function update(Request $request, Form $form)
    {
        $agency = $request->user()->agency;

        if ($form->agency_id !== $agency->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'fields' => 'required|array|min:1',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);

        $form->update($validated);

        return redirect()->route('forms.show', $form)
            ->with('success', 'Form updated successfully.');
    }

    public function destroy(Request $request, Form $form)
    {
        $agency = $request->user()->agency;

        if ($form->agency_id !== $agency->id) {
            abort(403);
        }

        $form->delete();
        $agency->decrement('forms_count');

        return redirect()->route('forms.index')
            ->with('success', 'Form deleted.');
    }

    public function togglePublish(Request $request, Form $form)
    {
        $agency = $request->user()->agency;

        if ($form->agency_id !== $agency->id) {
            abort(403);
        }

        $form->update([
            'is_published' => ! $form->is_published,
            'published_at' => ! $form->is_published ? now() : null,
        ]);

        return redirect()->route('forms.index')->with('success', 'Form status updated.');
    }

    public function render(Request $request, $slug)
    {
        $agency = $request->user()->agency;
        $form = Form::where('slug', $slug)
            ->where('is_published', true)
            ->where('agency_id', $agency->id)
            ->firstOrFail();

        return view('public.form', compact('form'));
    }

    public function submit(Request $request, $slug)
    {
        $agency = $request->user()->agency;
        $form = Form::where('slug', $slug)
            ->where('is_published', true)
            ->where('agency_id', $agency->id)
            ->firstOrFail();

        $rules = [];
        foreach ($form->fields as $field) {
            $rules[$field['name']] = ($field['required'] ?? false) ? 'required' : 'nullable';
        }

        $validated = $request->validate($rules);

        FormResponse::create([
            'form_id' => $form->id,
            'data' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $form->increment('submissions_count');

        if ($form->redirect_url) {
            return redirect($form->redirect_url);
        }

        return back()->with('success', $form->success_message);
    }
}
