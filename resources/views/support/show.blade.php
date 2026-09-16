@extends("layouts.unified")
@section('title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="grid grid-cols-12 gap-4 mb-2>
            <div class="col-span-12 sm:col-span-6">
                <h1 class="m-0">Ticket #{{ $ticket->ticket_number }}</h1>
            </div>
            <div class="col-span-12 sm:col-span-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('support.index') }}">Support</a></li>
                    <li class="breadcrumb-item active">{{ $ticket->ticket_number }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12 md:col-span-8">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $ticket->subject }}</h3>
                        <div class="card-tools">
                            <span class="badge badge-{{ $ticket->status === 'open' ? 'success' : ($ticket->status === 'resolved' ? 'primary' : 'secondary') }}">{{ ucfirst($ticket->status) }}</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="timeline">
                            <!-- Original message -->
                            <div class="time-label">
                                <span class="bg-primary">{{ $ticket->created_at ? $ticket->created_at->toDateString() : 'N/A' }}</span>
                            </div>
                            <div>
                                <i class="fas fa-envelope bg-blue"></i>
                                <div class="timeline-item">
                                    <span class="time">{{ $ticket->created_at ? $ticket->created_at->diffForHumans() : 'N/A' }}</span>
                                    <h3 class="timeline-header">{{ $ticket->user->name ?? 'Unknown' }}</h3>
                                    <div class="timeline-body">
                                        {{ $ticket->description }}
                                    </div>
                                </div>
                            </div>

                            <!-- Replies -->
                            @foreach($ticket->replies as $reply)
                                <div>
                                    <i class="fas fa-comments bg-yellow"></i>
                                    <div class="timeline-item">
                                        <span class="time">{{ $reply->created_at ? $reply->created_at->diffForHumans() : 'N/A' }}</span>
                                        <h3 class="timeline-header">{{ $reply->user->name ?? 'Unknown' }}</h3>
                                        <div class="timeline-body">
                                            {{ $reply->message }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if($ticket->isClosed())
                                <div>
                                    <i class="fas fa-check bg-green"></i>
                                    <div class="timeline-item">
                                        <h3 class="timeline-header">Ticket Closed</h3>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @if(!$ticket->isClosed())
                        <div class="card-footer">
                            <form action="/support/{{ $ticket->id }}/reply" method="POST">
                                @csrf
                                <div class="input-group">
                                    <input type="text" name="message" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Type your reply..." required>
                                    <div class="input-group-append">
                                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Reply</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Details</h3>
                    </div>
                    <div class="p-6">
                        <p><strong>Category:</strong> {{ ucfirst($ticket->category ?? 'Other') }}</p>
                        <p><strong>Priority:</strong> <span class="badge badge-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : 'info') }}">{{ ucfirst($ticket->priority) }}</span></p>
                        <p><strong>Status:</strong> {{ ucfirst($ticket->status) }}</p>
                        <p><strong>Created:</strong> {{ $ticket->created_at ? $ticket->created_at->toDateString() : 'N/A' }}</p>
                        @if($ticket->resolved_at)
                            <p><strong>Resolved:</strong> {{ $ticket->resolved_at ? $ticket->resolved_at->toDateString() : 'N/A' }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
