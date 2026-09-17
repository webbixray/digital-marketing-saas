@props([
    'icon' => 'fa-inbox',
    'message' => 'No items found.',
    'actionUrl' => null,
    'actionLabel' => null,
])

<div class="text-center py-12">
    <i class="fas {{ $icon }} fa-3x text-gray-300 dark:text-gray-600 mb-4"></i>
    <p class="text-gray-500 dark:text-gray-400">{{ $message }}</p>
    @if($actionUrl)
        <a href="{{ $actionUrl }}" class="mt-4 inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 font-medium">
            <i class="fas fa-plus"></i> {{ $actionLabel }}
        </a>
    @endif
</div>
