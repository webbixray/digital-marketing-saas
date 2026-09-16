@if ($paginator->hasPages())
<nav class="flex items-center justify-between mt-6" aria-label="Pagination">
    <div class="text-sm text-gray-500 dark:text-gray-400">
        Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
    </div>
    <div class="flex gap-1">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-2 rounded-lg border border-gray-200 text-gray-400 cursor-not-allowed dark:border-gray-700">←</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">←</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-3 py-2 rounded-lg border border-gray-200 text-gray-400 dark:border-gray-700">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-3 py-2 rounded-lg bg-indigo-600 text-white font-medium">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-2 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">→</a>
        @else
            <span class="px-3 py-2 rounded-lg border border-gray-200 text-gray-400 cursor-not-allowed dark:border-gray-700">→</span>
        @endif
    </div>
</nav>
@endif
