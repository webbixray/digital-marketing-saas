<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $status = $request->query('status', 'all');

        $query = SupportTicket::where('agency_id', $agencyId)
            ->with(['user', 'assignee'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $tickets = $query->paginate(15);

        return view('support.index', compact('tickets', 'status'));
    }

    public function create()
    {
        return view('support.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'required|in:billing,technical,feature_request,other',
        ]);

        $ticket = SupportTicket::create([
            'agency_id' => $request->user()->agency_id,
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'ticket_number' => 'TKT-'.strtoupper(Str::random(8)),
            'status' => 'open',
        ]);

        Log::info('Support ticket created', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
        ]);

        return redirect('/support')
            ->with('success', 'Ticket created successfully. We\'ll get back to you soon!');
    }

    public function show(Request $request, SupportTicket $ticket)
    {
        if ((int) $ticket->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }
        $ticket->load(['replies.user', 'assignee']);

        return view('support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        if ($ticket->status === 'waiting') {
            $ticket->update(['status' => 'in_progress']);
        }

        Log::info('Support ticket reply added', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Reply added successfully.');
    }

    public function destroy(Request $request, SupportTicket $support)
    {
        if ((int) $support->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }

        $support->delete();

        Log::warning('Support ticket deleted', [
            'ticket_id' => $support->id,
            'user_id' => $request->user()->id,
        ]);

        return redirect('/support')->with('success', 'Ticket deleted.');
    }
}
