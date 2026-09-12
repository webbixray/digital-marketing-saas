<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        try {
            $agency = $request->user()->agency;

            $query = Invoice::where('agency_id', $agency->id);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

            $stats = [
                'total' => Invoice::where('agency_id', $agency->id)->sum('total'),
                'pending' => Invoice::where('agency_id', $agency->id)->pending()->sum('total'),
                'overdue' => Invoice::where('agency_id', $agency->id)->overdue()->sum('total'),
            ];

            return view('invoices.index', compact('agency', 'invoices', 'stats'));
        } catch (\Exception $e) {
            Log::error('Failed to load invoices', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load invoices. Please try again.');
        }
    }

    public function create(Request $request)
    {
        try {
            $agency = $request->user()->agency;

            return view('invoices.create', compact('agency'));
        } catch (\Exception $e) {
            Log::error('Failed to load invoice create form', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'An error occurred. Please try again.');
        }
    }

    public function store(Request $request)
    {
        try {
            $agency = $request->user()->agency;

            $validated = $request->validate([
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $tax = $subtotal * 0.0; // Tax rate can be configured
            $total = $subtotal + $tax;

            $invoice = Invoice::create([
                'agency_id' => $agency->id,
                'invoice_number' => Invoice::generateNumber(),
                'status' => InvoiceStatus::PENDING->value,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create invoice', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to create invoice. Please try again.');
        }
    }

    public function show(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        try {
            $invoice->load('items', 'client', 'agency');

            return view('invoices.show', compact('agency', 'invoice'));
        } catch (\Exception $e) {
            Log::error('Failed to load invoice details', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load invoice details.');
        }
    }

    public function edit(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        try {
            return view('invoices.edit', compact('agency', 'invoice'));
        } catch (\Exception $e) {
            Log::error('Failed to load invoice edit form', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to load invoice details.');
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        try {
            $validated = $request->validate([
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
            ]);

            $invoice->update($validated);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice updated successfully.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update invoice.');
        }
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        try {
            $invoice->delete();

            return redirect()->route('invoices.index')
                ->with('success', 'Invoice deleted.');
        } catch (\Exception $e) {
            Log::error('Failed to delete invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete invoice.');
        }
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $agencyId = $request->user()->agency_id;

        if ((int) $invoice->agency_id !== (int) $agencyId) {
            abort(403);
        }

        try {
            $invoice->markPaid(
                $request->input('payment_method', 'manual'),
                $request->input('transaction_id', '')
            );

            return back()->with('success', 'Invoice marked as paid.');
        } catch (\Exception $e) {
            Log::error('Failed to mark invoice as paid', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to mark invoice as paid.');
        }
    }
}
