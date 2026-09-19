@extends("layouts.unified")
@section('title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Ticket #{{ $ticket->ticket_number }}</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">View and reply to support ticket.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $ticket->subject }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $ticket->status === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($ticket->status === 'resolved' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">{{ ucfirst($ticket->status) }}</span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <!-- Original message -->
                        <div class="border-l-4 border-indigo-500 pl-4 py-2">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $ticket->created_at ? $ticket->created_at->diffForHumans() : 'N/A' }}</div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $ticket->user->name ?? 'Unknown' }}</h4>
                            <p class="text-gray-700 dark:text-gray-300 mt-1">{{ $ticket->description }}</p>
                        </div>

                        <!-- Replies -->
                        @foreach($ticket->replies as $reply)
                        <div class="border-l-4 border-yellow-500 pl-4 py-2">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $reply->created_at ? $reply->created_at->diffForHumans() : 'N/A' }}</div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $reply->user->name ?? 'Unknown' }}</h4>
                            <p class="text-gray-700 dark:text-gray-300 mt-1">{{ $reply->message }}</p>
                        </div>
                        @endforeach

                        @if($ticket->isClosed())
                        <div class="border-l-4 border-green-500 pl-4 py-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white">Ticket Closed</h4>
                        </div>
                        @endif
                    </div>
                </div>
                @if(!$ticket->isClosed())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <form action="/support/{{ $ticket->id }}/reply" method="POST">
                            @csrf
                            <div class="flex gap-2">
                                <input type="text" name="message" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Type your reply..." required>
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Reply</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <div>
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Details</h3>
                </div>
                <div class="p-6 space-y-3">
                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Category:</strong> {{ ucfirst($ticket->category ?? 'Other') }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Priority:</strong> <span class="px-2 py-1 text-xs font-medium rounded-full {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : ($ticket->priority === 'high' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400') }}">{{ ucfirst($ticket->priority) }}</span></p>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Status:</strong> {{ ucfirst($ticket->status) }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Created:</strong> {{ $ticket->created_at ? $ticket->created_at->toDateString() : 'N/A' }}</p>
                    @if($ticket->resolved_at)
                        <p class="text-sm text-gray-700 dark:text-gray-300"><strong>Resolved:</strong> {{ $ticket->resolved_at ? $ticket->resolved_at->toDateString() : 'N/A' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
 @endsection
