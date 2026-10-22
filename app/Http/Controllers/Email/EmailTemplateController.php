<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmailTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;
        $templates = EmailTemplate::where('agency_id', $agency->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('email.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('email.templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'html_content' => 'required|string',
            'plain_text_content' => 'nullable|string',
        ]);

        $agency = $request->user()->agency;
        $template = EmailTemplate::create([
            'agency_id' => $agency->id,
            'slug' => Str::slug($data['name']).'-'.uniqid(),
            ...$data,
        ]);

        return redirect()->route('email.templates.show', $template)
            ->with('success', 'Template created successfully.');
    }

    public function show(EmailTemplate $template)
    {
        $agency = request()->user()->agency;
        if ($template->agency_id !== $agency->id) {
            abort(403);
        }

        return view('email.templates.show', compact('template'));
    }

    public function edit(EmailTemplate $template)
    {
        $agency = request()->user()->agency;
        if ($template->agency_id !== $agency->id) {
            abort(403);
        }

        return view('email.templates.edit', compact('template'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $agency = $request->user()->agency;
        if ($template->agency_id !== $agency->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'html_content' => 'required|string',
            'plain_text_content' => 'nullable|string',
        ]);

        $template->update($data);

        return redirect()->route('email.templates.show', $template)
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(EmailTemplate $template)
    {
        $agency = request()->user()->agency;
        if ($template->agency_id !== $agency->id) {
            abort(403);
        }

        $template->delete();

        return redirect()->route('email.templates.index')
            ->with('success', 'Template deleted.');
    }

    public function preview(EmailTemplate $template)
    {
        $agency = request()->user()->agency;
        if ($template->agency_id !== $agency->id) {
            abort(403);
        }

        return response()->json([
            'html' => $template->html_content,
        ]);
    }

    public function duplicate(EmailTemplate $template)
    {
        $agency = request()->user()->agency;
        if ($template->agency_id !== $agency->id) {
            abort(403);
        }

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name.' (Copy)';
        $newTemplate->slug = Str::slug($newTemplate->name).'-'.uniqid();
        $newTemplate->save();

        return redirect()->route('email.templates.edit', $newTemplate)
            ->with('success', 'Template duplicated.');
    }
}
