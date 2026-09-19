@extends('layouts.unified')
@section('title', 'Content Calendar')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Content Calendar</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">View and manage your scheduled content.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-700 dark:text-blue-400">Total Posts</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $stats['total'] ?? 0 }}</p>
                </div>
                <i class="fas fa-calendar-alt text-blue-500 text-xl"></i>
            </div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-700 dark:text-green-400">Published</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $stats['published'] ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-700 dark:text-yellow-400">Scheduled</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">{{ $stats['scheduled'] ?? 0 }}</p>
                </div>
                <i class="fas fa-clock text-yellow-500 text-xl"></i>
            </div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-700 dark:text-red-400">Failed</p>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-400">{{ $stats['failed'] ?? 0 }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
            </div>
        </div>
        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-indigo-700 dark:text-indigo-400">Best Times</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @forelse($bestTimes ?? [] as $time)
                            <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-xs rounded-full">{{ $time['label'] }}</span>
                        @empty
                            <span class="text-sm text-gray-500 dark:text-gray-400">No data yet</span>
                        @endforelse
                    </div>
                </div>
                <i class="fas fa-chart-line text-indigo-500 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                <i class="far fa-calendar-alt mr-2"></i>
                {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
            </h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('calendar.index', ['year' => $year, 'month' => $month - 1]) }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="{{ route('calendar.index', ['year' => now()->year, 'month' => now()->month]) }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">
                    Today
                </a>
                <a href="{{ route('calendar.index', ['year' => $year, 'month' => $month + 1]) }}" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="p-6">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Event Modal -->
<div x-data="{ open: false }"
     x-show="open"
     x-cloak
     x-trap.inert.noscroll="open"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     id="eventModal"
     tabindex="-1"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modal-title">
    <div class="max-w-lg w-full mx-4" @click.outside="open = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">Post Details</h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" @click="open = false" aria-label="Close modal">&times;</button>
            </div>
            <div class="p-6 space-y-3">
                <p class="text-sm"><strong class="text-gray-700 dark:text-gray-300">Platform:</strong> <span id="modalPlatform" class="text-gray-900 dark:text-white"></span></p>
                <p class="text-sm"><strong class="text-gray-700 dark:text-gray-300">Status:</strong> <span id="modalStatus" class="text-gray-900 dark:text-white"></span></p>
                <p class="text-sm"><strong class="text-gray-700 dark:text-gray-300">Scheduled:</strong> <span id="modalDate" class="text-gray-900 dark:text-white"></span></p>
                <div>
                    <strong class="text-sm text-gray-700 dark:text-gray-300">Content:</strong>
                    <div id="modalContent" class="mt-1 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm text-gray-900 dark:text-white"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                <a id="modalEdit" href="#" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Edit Post</a>
                <button type="button" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors" @click="open = false">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    .fc-event { cursor: pointer; }
    .platform-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; color: white; }
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const events = @json($events);
        
        const modal = document.getElementById('eventModal');
        const renderEventModal = function(info) {
            const props = info.event.extendedProps;
            document.getElementById('modalPlatform').textContent = props.platform;
            document.getElementById('modalStatus').textContent = props.status;
            document.getElementById('modalDate').textContent = info.event.start.toLocaleString();
            document.getElementById('modalContent').textContent = info.event.title;
            document.getElementById('modalEdit').href = '/social/posts/' + info.event.id + '/edit';
            if (modal && modal._x_dataStack) {
                modal._x_dataStack[0].open = true;
            }
        };
        
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: false,
            events: events,
            eventClick: renderEventModal,
            height: 'auto',
        });
        calendar.render();
    });
</script>
@endpush
