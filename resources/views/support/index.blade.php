@extends("layouts.unified")
@section('title', 'Support Tickets')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Support Tickets</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Support</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Your Tickets</h3>
                        <div class="card-tools">
                            <a href="{{ route('support.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus mr-1"></i>New Ticket
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr>
                                        <td><a href="{{ route('support.show', $ticket) }}">{{ $ticket->ticket_number }}</a></td>
                                        <td>{{ $ticket->subject }}</td>
                                        <td><span class="badge badge-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : 'info') }}">{{ ucfirst($ticket->priority) }}</span></td>
                                        <td><span class="badge badge-{{ $ticket->status === 'open' ? 'success' : ($ticket->status === 'resolved' ? 'primary' : 'secondary') }}">{{ ucfirst($ticket->status) }}</span></td>
                                        <td>{{ $ticket->created_at->toDateString() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No tickets found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $tickets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
