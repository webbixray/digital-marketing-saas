<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $agencyId = $request->user()->agency_id;
            $query = Invoice::where('agency_id', $agencyId);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $invoices = $query->orderBy('created_at', 'desc')
                ->paginate(min(max((int) $request->get('per_page', 20), 1), 100));

            return InvoiceResource::collection($invoices)->response();
        } catch (\Exception $e) {
            Log::error('Failed to list invoices', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices.',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create invoice', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice.',
            ], 500);
        }
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        try {
            $this->authorizeAccess($request, $invoice);

            return (new InvoiceResource($invoice->load('items', 'client')))->response();
        } catch (AuthorizationException|HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to show invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice.',
            ], 500);
        }
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        try {
            $this->authorizeAccess($request, $invoice);

            $data = $request->validate([
                'client_id' => 'nullable|exists:clients,id',
                'total' => 'sometimes|numeric|min:0',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'due_date' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            $invoice->update($data);

            return (new InvoiceResource($invoice))->response();
        } catch (AuthorizationException|HttpException $e) {
            throw $e;
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice.',
            ], 500);
        }
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        try {
            $this->authorizeAccess($request, $invoice);
            $invoice->delete();

            return response()->json(null, 204);
        } catch (AuthorizationException|HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to delete invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice.',
            ], 500);
        }
    }

    private function authorizeAccess(Request $request, Invoice $invoice): void
    {
        $agencyId = $request->user()->agency_id;
        if ($invoice->agency_id !== $agencyId) {
            abort(404);
        }
    }
}
