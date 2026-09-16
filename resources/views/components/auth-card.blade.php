@props(['title' => '', 'description' => ''])

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">
    <div class="w-full max-w-md">
        @if($title)
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">{{ $title }}</h1>
            @if($description)
            <p class="text-white/80">{{ $description }}</p>
            @endif
        </div>
        @endif
        <div class="bg-white rounded-xl shadow-xl p-8">
            {{ $slot }}
        </div>
    </div>
</div>
