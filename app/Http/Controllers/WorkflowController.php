<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowStatus;
use App\Models\Workflow;
use App\Models\WorkflowTemplate;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WorkflowController extends Controller
{
    public function __construct(
        private readonly WorkflowEngine $engine,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $query = Workflow::where('agency_id', $agencyId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $workflows = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('workflows.index', compact('workflows'));
    }

    public function builder(Request $request, ?Workflow $workflow = null)
    {
        $agencyId = $request->user()->agency_id;
        $templates = WorkflowTemplate::active()->public()->orderBy('category')->get();

        $existingWorkflow = null;
        if ($workflow) {
            if ((int) $workflow->agency_id !== (int) $agencyId) {
                abort(403);
            }
            $existingWorkflow = [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'description' => $workflow->description ?? '',
                'trigger_type' => $workflow->trigger_type,
                'trigger_config' => $workflow->trigger_config,
                'actions' => $workflow->actions,
                'conditions' => $workflow->conditions,
                'nodes' => $workflow->nodes ?? null,
                'connections' => $workflow->connections ?? null,
            ];
        }

        return view('workflows.builder', compact('templates', 'existingWorkflow'));
    }

    public function create(Request $request)
    {
        $triggerTypes = Workflow::TRIGGER_TYPES;
        $actionTypes = Workflow::ACTION_TYPES;

        return view('workflows.create', compact('triggerTypes', 'actionTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'trigger_type' => 'required|in:'.implode(',', array_keys(Workflow::TRIGGER_TYPES)),
            'trigger_config' => 'nullable|array',
            'actions' => 'required|array|min:1',
            'conditions' => 'nullable|array',
        ]);

        $workflow = Workflow::create([
            'agency_id' => $request->user()->agency_id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            'trigger_type' => $validated['trigger_type'],
            'trigger_config' => $validated['trigger_config'] ?? [],
            'actions' => $validated['actions'],
            'conditions' => $validated['conditions'] ?? [],
            'status' => WorkflowStatus::DRAFT->value,
        ]);

        $workflow->createVersion('Initial version', Auth::id());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Workflow created successfully.');
    }

    public function show(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $executions = $workflow->executions()->with('logs')->orderBy('started_at', 'desc')->paginate(10);
        $versions = $workflow->versions()->orderBy('version_number', 'desc')->paginate(10);
        $webhookLogs = $workflow->webhookLogs()->orderBy('created_at', 'desc')->paginate(10);

        return view('workflows.show', compact('workflow', 'executions', 'versions', 'webhookLogs'));
    }

    public function edit(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $triggerTypes = Workflow::TRIGGER_TYPES;
        $actionTypes = Workflow::ACTION_TYPES;

        return view('workflows.edit', compact('workflow', 'triggerTypes', 'actionTypes'));
    }

    public function update(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|in:'.implode(',', array_keys(Workflow::TRIGGER_TYPES)),
            'trigger_config' => 'nullable|array',
            'actions' => 'required|array|min:1',
            'conditions' => 'nullable|array',
            'change_notes' => 'nullable|string|max:500',
        ]);

        $workflow->update([
            'name' => $validated['name'],
            'trigger_type' => $validated['trigger_type'],
            'trigger_config' => $validated['trigger_config'] ?? [],
            'actions' => $validated['actions'],
            'conditions' => $validated['conditions'] ?? [],
        ]);

        $workflow->createVersion($validated['change_notes'] ?? 'Updated workflow', Auth::id());

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Workflow updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $workflow->delete();

        return redirect()->route('workflows.index')
            ->with('success', 'Workflow deleted.');
    }

    public function toggleStatus(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $newStatus = $workflow->status === WorkflowStatus::ACTIVE->value
            ? WorkflowStatus::PAUSED->value
            : WorkflowStatus::ACTIVE->value;

        $workflow->update(['status' => $newStatus]);

        return back()->with('success', 'Workflow status updated.');
    }

    public function storeFromBuilder(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'nodes' => 'required|array|min:1',
            'connections' => 'nullable|array',
        ]);

        $triggerNode = collect($validated['nodes'])->firstWhere('type', 'trigger');
        $actionNodes = collect($validated['nodes'])->where('type', 'action')->values()->all();

        $workflow = Workflow::create([
            'agency_id' => $request->user()->agency_id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            'trigger_type' => $triggerNode['subtype'] ?? 'manual',
            'trigger_config' => $triggerNode['config'] ?? [],
            'actions' => array_map(fn ($node) => [
                'type' => $node['subtype'],
                'config' => $node['config'] ?? [],
            ], $actionNodes),
            'conditions' => [],
            'status' => WorkflowStatus::DRAFT->value,
        ]);

        $workflow->createVersion('Created from visual builder', Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Workflow saved successfully.',
            'workflow' => $workflow,
        ]);
    }

    public function updateFromBuilder(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'nodes' => 'required|array|min:1',
            'connections' => 'nullable|array',
        ]);

        $triggerNode = collect($validated['nodes'])->firstWhere('type', 'trigger');
        $actionNodes = collect($validated['nodes'])->where('type', 'action')->values()->all();

        $workflow->update([
            'name' => $validated['name'],
            'trigger_type' => $triggerNode['subtype'] ?? 'manual',
            'trigger_config' => $triggerNode['config'] ?? [],
            'actions' => array_map(fn ($node) => [
                'type' => $node['subtype'],
                'config' => $node['config'] ?? [],
            ], $actionNodes),
            'conditions' => [],
        ]);

        $workflow->createVersion('Updated from visual builder', Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Workflow updated successfully.',
            'workflow' => $workflow,
        ]);
    }

    public function createFromTemplate(Request $request, string $templateSlug)
    {
        $template = WorkflowTemplate::where('slug', $templateSlug)->firstOrFail();

        $workflow = Workflow::create([
            'agency_id' => $request->user()->agency_id,
            'name' => $template->name,
            'slug' => Str::slug($template->name).'-'.uniqid(),
            'trigger_type' => $template->nodes[0]['subtype'] ?? 'manual',
            'trigger_config' => [],
            'actions' => collect($template->nodes)->where('type', 'action')->values()->map(fn ($node) => [
                'type' => $node['subtype'],
                'config' => $node['config'] ?? [],
            ])->all(),
            'conditions' => [],
            'status' => WorkflowStatus::DRAFT->value,
        ]);

        $workflow->createVersion('Created from template: '.$template->name, Auth::id());
        $template->incrementUsage();

        return redirect()->route('workflows.builder', $workflow)
            ->with('success', 'Workflow created from template: '.$template->name);
    }

    public function versions(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $versions = $workflow->versions()->orderBy('version_number', 'desc')->paginate(15);

        return view('workflows.versions', compact('workflow', 'versions'));
    }

    public function restoreVersion(Request $request, $id, int $versionId)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $version = $workflow->versions()->findOrFail($versionId);

        $workflow->createVersion('Before restore to v'.$version->version_number, Auth::id());
        $workflow->restoreFromVersion($version);

        return redirect()->route('workflows.show', $workflow)
            ->with('success', 'Workflow restored to version '.$version->version_number);
    }

    public function webhookInfo(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        if (! $workflow->webhook_secret) {
            $workflow->generateWebhookSecret();
        }

        $webhookLogs = $workflow->webhookLogs()->orderBy('created_at', 'desc')->paginate(20);

        return view('workflows.webhook', compact('workflow', 'webhookLogs'));
    }

    public function regenerateWebhook(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            abort(403);
        }

        $workflow->generateWebhookSecret();

        return back()->with('success', 'Webhook secret regenerated. Please update your external service.');
    }

    public function execute(Request $request, $id)
    {
        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::findOrFail($id);

        if ((int) $workflow->agency_id !== (int) $agencyId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'trigger_data' => 'nullable|array|max:50',
            ]);
            $engine = $this->engine;
            $execution = $engine->execute($workflow, $validated['trigger_data'] ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Workflow executed successfully.',
                'execution' => $execution,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Execution failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
