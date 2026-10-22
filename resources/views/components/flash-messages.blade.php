@if(session('success'))
    <div class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800 rounded-lg p-3 mb-4" role="alert">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-4" role="alert">
        {{ session('error') }}
    </div>
@endif
