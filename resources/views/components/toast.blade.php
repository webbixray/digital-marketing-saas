@props([
    'position' => 'top-4 right-4',
    'maxWidth' => 'max-w-sm',
    'duration' => 5000,
])

@php
// Collect all session flashes into a normalized array
$toasts = [];
foreach (['success', 'error', 'warning', 'info'] as $type) {
    $message = session($type);
    if ($message) {
        $toasts[] = [
            'type' => $type,
            'message' => is_array($message) ? implode(' ', $message) : $message,
        ];
    }
}
$hasSessionToasts = !empty($toasts);
@endphp

@if($hasSessionToasts)
<!-- Toast Container: fixed top-4 right-4 z-50 max-w-sm -->
<div x-data="toastSystem()" 
     x-init="init(@js($toasts), {{ $duration }})"
     class="fixed {{ $position }} {{ $maxWidth }} z-50 space-y-3"
     role="region"
     aria-label="Notifications"
     aria-live="polite">
    
    <template x-for="(toast, index) in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-x-8 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-x-0 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-8"
             class="relative overflow-hidden rounded-lg shadow-lg border backdrop-blur-sm"
             :class="{
                'bg-green-50 dark:bg-green-900/40 border-green-200 dark:border-green-800 ring-1 ring-green-500/20': toast.type === 'success',
                'bg-red-50 dark:bg-red-900/40 border-red-200 dark:border-red-800 ring-1 ring-red-500/20': toast.type === 'error',
                'bg-yellow-50 dark:bg-yellow-900/40 border-yellow-200 dark:border-yellow-800 ring-1 ring-yellow-500/20': toast.type === 'warning',
                'bg-blue-50 dark:bg-blue-900/40 border-blue-200 dark:border-blue-800 ring-1 ring-blue-500/20': toast.type === 'info'
             }"
             role="alert"
             @mouseenter="pauseTimer(toast.id)"
             @mouseleave="resumeTimer(toast.id)">
            
            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200/30 dark:bg-gray-700/30">
                <div class="h-full transition-all duration-100 ease-linear"
                     :class="{
                        'bg-green-500': toast.type === 'success',
                        'bg-red-500': toast.type === 'error',
                        'bg-yellow-500': toast.type === 'warning',
                        'bg-blue-500': toast.type === 'info'
                     }"
                     :style="`width: ${toast.progress}%`">
                </div>
            </div>
            
            <!-- Content -->
            <div class="flex items-start gap-3 px-4 py-3 pr-3">
                <!-- Icon -->
                <div class="flex-shrink-0 mt-0.5">
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>
                
                <!-- Message -->
                <p class="flex-1 text-sm font-medium text-gray-800 dark:text-gray-100 leading-snug" x-text="toast.message"></p>
                
                <!-- Close button -->
                <button @click="dismiss(toast.id)" 
                        class="flex-shrink-0 ml-2 p-1 rounded-md text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 hover:bg-gray-200/50 dark:hover:bg-gray-700/50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 dark:focus:ring-gray-500"
                        :aria-label="`Dismiss ${toast.type} notification`">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
function toastSystem() {
    return {
        toasts: [],
        toastId: 0,
        intervals: {},
        
        init(sessionToasts, duration) {
            // Load session-based toasts first
            if (sessionToasts && sessionToasts.length > 0) {
                sessionToasts.forEach(t => {
                    this.add(t.message, t.type, duration);
                });
            }
            
            // Store globally for JS-triggered toasts
            window.toast = (message, type = 'info', customDuration = null) => {
                this.add(message, type, customDuration || duration);
            };
            // Backward compatibility with old showToast API
            window.showToast = (message, type = 'info') => {
                this.add(message, type, duration);
            };
        },
        
        add(message, type = 'info', duration = 5000) {
            const id = ++this.toastId;
            const toast = {
                id,
                message,
                type,
                visible: true,
                progress: 100,
                paused: false,
                duration,
            };
            this.toasts.push(toast);
            this.startTimer(id, duration);
        },
        
        startTimer(id, duration) {
            const toast = this.toasts.find(t => t.id === id);
            if (!toast) return;
            
            const interval = 50; // Update every 50ms for smooth progress
            const step = (interval / duration) * 100;
            
            this.intervals[id] = setInterval(() => {
                const t = this.toasts.find(t => t.id === id);
                if (!t || t.paused) return;
                
                t.progress -= step;
                if (t.progress <= 0) {
                    this.dismiss(id);
                }
            }, interval);
        },
        
        pauseTimer(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) toast.paused = true;
        },
        
        resumeTimer(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) toast.paused = false;
        },
        
        dismiss(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (!toast) return;
            
            toast.visible = false;
            
            // Clear interval
            if (this.intervals[id]) {
                clearInterval(this.intervals[id]);
                delete this.intervals[id];
            }
            
            // Remove from array after animation
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 250);
        }
    };
}
</script>
@endif
