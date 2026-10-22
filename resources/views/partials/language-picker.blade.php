@props([
    'currentLocale',
    'currentLanguage',
    'supportedLanguages',
    'isRTL',
])

<div class="relative" x-data="{
    open: false,
    search: '',
    get filteredLanguages() {
        if (!this.search) return {{ json_encode($supportedLanguages) }};
        const q = this.search.toLowerCase();
        return Object.fromEntries(
            Object.entries({{ json_encode($supportedLanguages) }}).filter(
                ([code, lang]) => lang.native.toLowerCase().includes(q) || lang.name.toLowerCase().includes(q) || code.toLowerCase().includes(q)
            )
        );
    },
    switchLocale(locale) {
        fetch('{{ route('languages.switch') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ locale })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(error => console.error('Locale switch failed:', error));
    }
}" @click.away="open = false">
    <button
        @click="open = !open"
        class="flex items-center gap-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors min-h-[44px] rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="{{ __('language.switch_to', ['language' => $currentLanguage['native'] ?? 'Language']) }}"
        title="{{ $currentLanguage['native'] ?? 'Language' }} ({{ strtoupper($currentLocale) }})">
        <span class="text-lg leading-none">{{ $currentLanguage['flag'] ?? '🌐' }}</span>
        <span class="hidden md:inline">{{ $currentLanguage['native'] ?? 'Language' }}</span>
        @if($isRTL)
            <span class="hidden md:inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded">
                {{ __('language.rtl') }}
            </span>
        @endif
        <i class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
        @class([
            'right-0' => !$isRTL,
            'left-0' => $isRTL,
        ])
        role="menu"
        aria-label="Language selection">
        <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <i class="fas fa-search absolute top-1/2 -translate-y-1/2 @if($isRTL) right-3 @else left-3 @endif text-gray-400 text-xs"></i>
                <input
                    type="text"
                    x-model="search"
                    placeholder="{{ __('language.search') }}"
                    class="w-full pl-8 pr-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 border-0 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-indigo-500"
                    @click.stop
                    aria-label="{{ __('language.search') }}">
            </div>
        </div>

        <div class="max-h-64 overflow-y-auto">
            <template x-for="(lang, code) in filteredLanguages" :key="code">
                <button
                    @click="switchLocale(code)"
                    type="button"
                    class="language-picker-item flex items-center justify-between w-full px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors min-h-[44px] gap-2"
                    :class="{ 'bg-indigo-50 dark:bg-indigo-900/20 font-medium': code === '{{ $currentLocale }}' }"
                    role="menuitem">
                    <div class="flex items-center gap-2">
                        <span class="text-lg" x-text="lang.flag"></span>
                        <div class="text-start">
                            <span x-text="lang.native" class="block"></span>
                            <span x-text="lang.name" class="block text-xs text-gray-400 dark:text-gray-500"></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <span
                            x-show="lang.rtl"
                            class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 rounded rtl-badge">
                            {{ __('language.rtl') }}
                        </span>
                        <i
                            x-show="code === '{{ $currentLocale }}'"
                            class="fas fa-check text-indigo-600 dark:text-indigo-400 text-xs"></i>
                    </div>
                </button>
            </template>
            <div x-show="Object.keys(filteredLanguages).length === 0" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                No languages found
            </div>
        </div>
    </div>
</div>
