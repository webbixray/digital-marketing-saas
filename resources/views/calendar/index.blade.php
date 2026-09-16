@extends('layouts.unified')
@section('title', 'Content Calendar')

@section('content')
<div class="space-y-6">

</div>


        <!-- Stats Cards -->
        <div class="grid grid-cols-12 gap-4>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total'] ?? 0 }}</h3>
                        <p>Total Posts</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['published'] ?? 0 }}</h3>
                        <p>Published</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['scheduled'] ?? 0 }}</h3>
                        <p>Scheduled</p>
                    </div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats['failed'] ?? 0 }}</h3>
                        <p>Failed</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>Best Times</h3>
                        <p>
                            @forelse($bestTimes ?? [] as $time)
                                <span class="badge bg-light">{{ $time['label'] }}</span>
                            @empty
                                No data yet
                            @endforelse
                        </p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="grid grid-cols-12 gap-4>
            <div class="col-span-12">
                <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            <i class="far fa-calendar-alt"></i>
                            {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                        </h3>
                        <div class="card-tools">
                            <a href="{{ route('calendar.index', ['year' => $year, 'month' => $month - 1]) }}" class="btn btn-tool">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <a href="{{ route('calendar.index', ['year' => now()->year, 'month' => now()->month]) }}" class="btn btn-tool">
                                Today
                            </a>
                            <a href="{{ route('calendar.index', ['year' => $year, 'month' => $month + 1]) }}" class="btn btn-tool">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Post Details</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Platform:</strong> <span id="modalPlatform"></span></p>
                <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                <p><strong>Scheduled:</strong> <span id="modalDate"></span></p>
                <p><strong>Content:</strong></p>
                <div id="modalContent" class="border p-2 bg-light"></div>
            </div>
            <div class="modal-footer">
                <a id="modalEdit" href="#" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">Edit Post</a>
                <button type="button" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors" data-dismiss="modal">Close</button>
            </div>
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
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
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
            if (modal) modal.style.display = 'block';
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
