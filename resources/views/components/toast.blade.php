@props([
    'type' => 'success',
    'message' => '',
])

@php
$typeClasses = [
    'success' => 'bg-green-500',
    'error' => 'bg-red-500',
    'warning' => 'bg-yellow-500',
    'info' => 'bg-blue-500',
];
$iconClasses = [
    'success' => 'fas fa-check-circle',
    'error' => 'fas fa-exclamation-circle',
    'warning' => 'fas fa-exclamation-triangle',
    'info' => 'fas fa-info-circle',
];
$bgClass = $typeClasses[$type] ?? 'bg-green-500';
$iconClass = $iconClasses[$type] ?? 'fas fa-check-circle';
@endphp

<div x-data="{
    show: false,
    message: @js($message),
    type: @js($type),
    init() {
        this.$watch('show', (value) => {
            if (value) {
                setTimeout(() => this.show = false, 3000);
            }
        });
        if (this.message) {
            this.show = true;
        }
    }
}"
x-cloak
x-show="show"
x-transition:enter="transition ease-out duration-300"
x-transition:enter-start="opacity-0 translate-y-2"
x-transition:enter-end="opacity-100 translate-y-0"
x-transition:leave="transition ease-in duration-200"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 translate-y-2"
class="fixed top-4 right-4 z-[100] px-6 py-3 rounded-lg shadow-lg text-white flex items-center gap-2 {{ $bgClass }}"
style="display: none;"
role="alert"
aria-live="polite">
    <i class="{{ $iconClass }}"></i>
    <span x-text="message"></span>
</div>
