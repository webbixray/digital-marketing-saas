<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiInvoiceController extends ApiController
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $query = Invoice::where('agency_id', $agencyId);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

        return InvoiceResource::collection($invoices)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'total' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $agencyId = $request->user()->agency_id;
        $invoice = Invoice::create([
            'agency_id' => $agencyId,
            'invoice_number' => Invoice::generateNumber(),
            'status' => 'pending',
            'issue_date' => now(),
            'due_date' => $data['due_date'] ?? now()->addDays(30),
            ...$data,
        ]);

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeAgencyResource($invoice, $request->user()->agency_id);

        return (new InvoiceResource($invoice->load('items', 'client')))->response();
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeAgencyResource($invoice, $request->user()->agency_id);

        $data = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'total' => 'sometimes|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($data);

        return (new InvoiceResource($invoice))->response();
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeAgencyResource($invoice, $request->user()->agency_id);
        $invoice->delete();

        return response()->json(null, 204);
    }
}
