@extends("layouts.unified")
@section('title', 'Support Tickets')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">Support Tickets</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
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
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Your Tickets</h3>
                        <div class="card-tools">
                            <a href="{{ route('support.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm">
                                <i class="fas fa-plus mr-1"></i>New Ticket
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200">
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
