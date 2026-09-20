<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;

        $query = Client::where('agency_id', $agency->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', $search)
                    ->orWhereLike('email', $search)
                    ->orWhereLike('company', $search);
            });
        }

        $clients = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('clients.index', compact('agency', 'clients'));
    }

    public function create(Request $request)
    {
        $agency = $request->user()->agency;

        return view('clients.create', compact('agency'));
    }

    public function store(Request $request)
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $client = Client::create([
            'agency_id' => $agency->id,
            ...$validated,
            'status' => 'active',
        ]);

        $agency->increment('clients_count');

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client created successfully.');
    }

    public function show(Request $request, Client $client)
    {
        $agency = $request->user()->agency;

        if ($client->agency_id !== $agency->id) {
            abort(403);
        }

        $campaigns = $client->campaigns()->with('client')->orderBy('created_at', 'desc')->paginate(10);

        return view('clients.show', compact('agency', 'client', 'campaigns'));
    }

    public function edit(Request $request, Client $client)
    {
        $agency = $request->user()->agency;

        if ($client->agency_id !== $agency->id) {
            abort(403);
        }

        return view('clients.edit', compact('agency', 'client'));
    }

    public function update(Request $request, Client $client)
    {
        $agency = $request->user()->agency;

        if ($client->agency_id !== $agency->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,'.$client->id,
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive,lead',
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Request $request, Client $client)
    {
        $agency = $request->user()->agency;

        if ($client->agency_id !== $agency->id) {
            abort(403);
        }

        $client->delete();
        $agency->decrement('clients_count');

        return redirect()->route('clients.index')
            ->with('success', 'Client deleted.');
    }
}
