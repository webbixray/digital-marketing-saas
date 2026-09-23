<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    use HandlesErrors;

    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;

        $query = Invoice::where('agency_id', $agency->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $invoices = $query->with('client')->orderBy('created_at', 'desc')->paginate(15);

        $agencyInvoices = Invoice::where('agency_id', $agency->id);
        $stats = [
            'total' => (clone $agencyInvoices)->sum('total'),
            'pending' => (clone $agencyInvoices)->pending()->sum('total'),
            'overdue' => (clone $agencyInvoices)->overdue()->sum('total'),
        ];

        return view('invoices.index', compact('agency', 'invoices', 'stats'));
    }

    public function create(Request $request)
    {
        $agency = $request->user()->agency;

        return view('invoices.create', compact('agency'));
    }

    public function store(Request $request)
    {
        return $this->handleAction(function () use ($request) {
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

            $tax = $subtotal * 0.0;
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

            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'agency_id' => $agency->id,
                'total' => $total,
            ]);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice created successfully.');
        }, 'Failed to create invoice. Please try again.', [
            'route' => 'invoices.create',
            'message' => 'Failed to create invoice. Please try again.',
        ]);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        $invoice->load('items', 'client', 'agency');

        return view('invoices.show', compact('agency', 'invoice'));
    }

    public function edit(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        return view('invoices.edit', compact('agency', 'invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        return $this->handleAction(function () use ($request, $invoice) {
            $validated = $request->validate([
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
            ]);

            $invoice->update($validated);

            Log::info('Invoice updated', [
                'invoice_id' => $invoice->id,
                'agency_id' => $agency->id,
            ]);

            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice updated successfully.');
        }, 'Failed to update invoice.', [
            'route' => 'invoices.edit',
            'params' => ['invoice' => $invoice],
            'message' => 'Failed to update invoice.',
        ]);
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $agency = $request->user()->agency;

        if ($invoice->agency_id !== $agency->id) {
            abort(403);
        }

        return $this->handleAction(function () use ($request, $invoice) {
            $invoice->delete();

            Log::warning('Invoice deleted', [
                'invoice_id' => $invoice->id,
                'agency_id' => $request->user()->agency_id,
            ]);

            return redirect()->route('invoices.index')
                ->with('success', 'Invoice deleted.');
        }, 'Failed to delete invoice.', [
            'route' => 'invoices.index',
            'message' => 'Failed to delete invoice.',
        ]);
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $agencyId = $request->user()->agency_id;

        if ((int) $invoice->agency_id !== (int) $agencyId) {
            abort(403);
        }

        return $this->handleAction(function () use ($request, $invoice) {
            $invoice->markPaid(
                $request->input('payment_method', 'manual'),
                $request->input('transaction_id', '')
            );

            Log::info('Invoice marked as paid', [
                'invoice_id' => $invoice->id,
                'agency_id' => $agencyId,
                'payment_method' => $request->input('payment_method', 'manual'),
            ]);

            return back()->with('success', 'Invoice marked as paid.');
        }, 'Failed to mark invoice as paid.', [
            'route' => 'invoices.show',
            'params' => ['invoice' => $invoice],
            'message' => 'Failed to mark invoice as paid.',
        ]);
    }
}
