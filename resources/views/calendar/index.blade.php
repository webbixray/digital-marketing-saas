@extends('layouts.unified')
@section('title', 'Content Calendar')

@section('content')
<x-flash-messages />
<div class="space-y-6" x-data="calendarApp()">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Content Calendar</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">View, schedule, and optimize your social media content.</p>
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

    <!-- Optimal Slot Suggestions -->
    @if(!empty($optimalSlots))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">
            <i class="fas fa-magic mr-2 text-indigo-500"></i>Suggested Optimal Posting Times
        </h3>
        <div class="flex flex-wrap gap-2">
            @foreach($optimalSlots as $slot)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium text-white"
                      style="background-color: {{ $slot['platform'] ? match($slot['platform']) { 'facebook' => '#1877f2', 'instagram' => '#e4405f', 'twitter' => '#1da1f2', 'linkedin' => '#0077b5', 'tiktok' => '#000000', 'pinterest' => '#bd081c', default => '#6c757d' } : '#6366f1' }}">
                    <i class="fas fa-clock"></i>
                    {{ $slot['day_name'] }} {{ $slot['time_slot'] }}
                    @if($slot['platform'])
                        <span class="ml-1 opacity-75">({{ ucfirst($slot['platform']) }})</span>
                    @endif
                </span>
            @endforeach
        </div>
    </div>
    @endif>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Platform:</label>
                <select x-model="selectedPlatform" @change="refetchEvents()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">All Platforms</option>
                    @foreach($platforms as $platform)
                        <option value="{{ $platform }}">{{ ucfirst($platform) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Account:</label>
                <select x-model="selectedAccount" @change="refetchEvents()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->platform_display_name ?? $account->platform_username }} ({{ ucfirst($account->platform) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="ml-auto flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <i class="fas fa-info-circle"></i>
                <span>Drag and drop events to reschedule</span>
            </div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="p-6">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div x-data="{ open: false, event: null }"
     x-show="open"
     x-cloak
     x-trap.inert.noscroll="open"
     @keydown.escape.window="open = false"
     @event-clicked.window="event = $event.detail; open = true"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
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
            <div class="p-6 space-y-3" x-show="event">
                <p class="text-sm"><strong class="text-gray-700 dark:text-gray-300">Platform:</strong> <span x-text="event?.platform" class="text-gray-900 dark:text-white"></span></p>
                <p class="text-sm"><strong class="text-gray-700 dark:text-gray-300">Status:</strong> <span x-text="event?.status" class="text-gray-900 dark:text-white"></span></p>
                <p class="text-sm"><strong class="text-gray-700 dark:text-gray-300">Account:</strong> <span x-text="event?.account_name || event?.account" class="text-gray-900 dark:text-white"></span></p>
                <p class="text-sm"><strong class="text-gray-700 dark:text-gray-300">Scheduled:</strong> <span x-text="event?.scheduled_at" class="text-gray-900 dark:text-white"></span></p>
                <div>
                    <strong class="text-sm text-gray-700 dark:text-gray-300">Content:</strong>
                    <div class="mt-1 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm text-gray-900 dark:text-white" x-text="event?.content"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                <a :href="event?.edit_url || '#'" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                    <i class="fas fa-edit"></i> Edit Post
                </a>
                <button type="button" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors" @click="open = false">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div x-data="{ show: false, message: '', type: 'success' }"
     x-show="show"
     x-transition
     x-cloak
     class="fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white"
     :class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
     style="display: none;">
    <span x-text="message"></span>
</div>
@endsection

@push('styles')
<style>
    .fc-event { cursor: pointer; border: none !important; }
    .fc-event-dragging { opacity: 0.8; }
    .fc .fc-button-primary { background-color: #6366f1; border-color: #6366f1; }
    .fc .fc-button-primary:hover { background-color: #4f46e5; border-color: #4f46e5; }
    .fc .fc-button-primary:not(:disabled).fc-button-active { background-color: #4338ca; border-color: #4338ca; }
    .fc .fc-toolbar-title { font-size: 1.25rem; font-weight: 600; }
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6/index.global.min.js"></script>
<script nonce="{{ $cspNonce ?? '' }}">
    function calendarApp() {
        return {
            calendar: null,
            selectedPlatform: '',
            selectedAccount: '',

            init() {
                const calendarEl = document.getElementById('calendar');

                this.calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    views: {
                        dayGridMonth: { buttonText: 'Month' },
                        timeGridWeek: { buttonText: 'Week' },
                        timeGridDay: { buttonText: 'Day' }
                    },
                    events: (fetchInfo, successCallback, failureCallback) => {
                        const params = new URLSearchParams({
                            start: fetchInfo.startStr,
                            end: fetchInfo.endStr,
                            platform: this.selectedPlatform,
                            account_id: this.selectedAccount,
                        });
                        fetch(`{{ route('calendar.events') }}?${params.toString()}`, {
                            headers: { 'Accept': 'application/json' }
                        })
                        .then(response => response.json())
                        .then(data => successCallback(data))
                        .catch(error => failureCallback(error));
                    },
                    eventClick: (info) => {
                        const props = info.event.extendedProps;
                        window.dispatchEvent(new CustomEvent('event-clicked', { detail: props }));
                    },
                    editable: true,
                    droppable: true,
                    eventDrop: (info) => {
                        const event = info.event;
                        fetch('{{ route('content-calendar.update-schedule') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                id: event.id,
                                scheduled_at: event.start.toISOString(),
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Post rescheduled successfully.', 'success');
                            } else {
                                showToast(data.message || 'Failed to reschedule.', 'error');
                                info.revert();
                            }
                        })
                        .catch(() => {
                            showToast('Network error. Changes reverted.', 'error');
                            info.revert();
                        });
                    },
                    eventResize: (info) => {
                        // Duration change not applicable for posts, revert
                        info.revert();
                    },
                    height: 'auto',
                    aspectRatio: 1.8,
                    nowIndicator: true,
                    businessHours: {
                        daysOfWeek: [1, 2, 3, 4, 5],
                        startTime: '08:00',
                        endTime: '18:00',
                    },
                    eventDidMount: (info) => {
                        // Add platform badge tooltip
                        const props = info.event.extendedProps;
                        if (props.platform) {
                            info.el.title = `${ucfirst(props.platform)} - ${props.status}\n${props.content || info.event.title}`;
                        }
                    }
                });

                this.calendar.render();
            },

            refetchEvents() {
                if (this.calendar) {
                    this.calendar.refetchEvents();
                }
            }
        };
    }

    function showToast(message, type) {
        const toast = document.querySelector('[x-data*="show: false"]');
        if (toast && toast._x_dataStack) {
            const data = toast._x_dataStack[0];
            data.message = message;
            data.type = type;
            data.show = true;
            setTimeout(() => { data.show = false; }, 3000);
        }
    }

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
</script>
@endpush
