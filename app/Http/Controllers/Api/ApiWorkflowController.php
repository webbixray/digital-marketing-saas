<?php

namespace App\Http\Controllers\Api;

use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiWorkflowController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $workflows = Workflow::where('agency_id', $agencyId)
            ->orderBy('created_at', 'desc')
            ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

        return response()->json($workflows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_type' => 'required|string',
            'actions' => 'present|array',
        ]);

        $agencyId = $request->user()->agency_id;
        $workflow = Workflow::create([
            'agency_id' => $agencyId,
            'slug' => Str::slug($data['name']).'-'.uniqid(),
            ...$data,
        ]);

        return response()->json($workflow, 201);
    }

    public function show(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorizeAgencyResource($workflow, $request->user()->agency_id);

        return response()->json($workflow->load('executions', 'versions'));
    }

    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorizeAgencyResource($workflow, $request->user()->agency_id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'trigger_type' => 'sometimes|string',
            'actions' => 'sometimes|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $workflow->update($data);

        return response()->json($workflow);
    }

    public function destroy(Request $request, Workflow $workflow): JsonResponse
    {
        $this->authorizeAgencyResource($workflow, $request->user()->agency_id);
        $workflow->delete();

        return response()->json(null, 204);
    }
}
