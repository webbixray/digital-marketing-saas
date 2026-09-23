@extends('layouts.unified')

@section('title', 'AI Content Translation')

@section('content')
    <x-flash-messages />
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">AI Content Translation</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Translate your content to multiple languages while preserving formatting.</p>
        </div>
    </div>

    <div x-data="translationEditor()" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Source Panel -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Source Content</h3>
                <div class="flex items-center gap-2">
                    <select x-model="sourceLang" @change="detectLanguage()" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Auto-detect</option>
                        @foreach($languages as $code => $name)
                            <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                    <span x-show="detectedLang" x-text="'Detected: ' + detectedLang" class="text-xs text-green-600 dark:text-green-400"></span>
                </div>
            </div>
            <textarea x-model="sourceContent" @input.debounce.500ms="detectLanguage()" placeholder="Enter content to translate... (HTML and Markdown supported)" class="w-full h-80 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"></textarea>
            <div class="mt-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span x-text="sourceContent.length + ' characters'"></span>
                <button @click="sourceContent = ''" type="button" class="text-red-500 hover:text-red-700">Clear</button>
            </div>
        </div>

        <!-- Target Panel -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Translated Content</h3>
                <div class="flex items-center gap-2">
                    <select x-model="targetLang" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach($languages as $code => $name)
                            <option value="{{ $code }}">{{ $name }} ({{ $code }})</option>
                        @endforeach
                    </select>
                    <button @click="copyTranslation()" type="button" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400" x-show="translatedContent">Copy</button>
                </div>
            </div>
            <div class="w-full h-80 p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 overflow-y-auto">
                <div x-show="!translatedContent && !translating" class="flex items-center justify-center h-full text-gray-400 dark:text-gray-500">
                    <p>Translation will appear here</p>
                </div>
                <div x-show="translating" class="flex items-center justify-center h-full">
                    <svg class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-gray-500 dark:text-gray-400">Translating...</span>
                </div>
                <div x-show="translatedContent && !translating" x-text="translatedContent" class="text-gray-900 dark:text-white whitespace-pre-wrap font-mono text-sm"></div>
            </div>
            <!-- Quality Score -->
            <div x-show="qualityScore !== null" class="mt-2 flex items-center justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Quality Score:</span>
                <span class="flex items-center gap-2">
                    <div class="w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all" :class="qualityScore >= 80 ? 'bg-green-500' : (qualityScore >= 50 ? 'bg-yellow-500' : 'bg-red-500')" :style="'width: ' + qualityScore + '%'"></div>
                    </div>
                    <span class="font-medium" :class="qualityScore >= 80 ? 'text-green-600' : (qualityScore >= 50 ? 'text-yellow-600' : 'text-red-600')" x-text="qualityScore + '%'"></span>
                </span>
            </div>
        </div>

        <!-- Context & Translate Button -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Context</label>
                    <select x-model="context" class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="general">General</option>
                        <option value="social_post">Social Post</option>
                        <option value="email_subject">Email Subject</option>
                        <option value="email_body">Email Body</option>
                    </select>
                </div>
                <div class="flex-1"></div>
                <button @click="translate()" :disabled="!sourceContent || !targetLang || translating" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium transition-colors inline-flex items-center gap-2">
                    <span x-show="!translating">Translate</span>
                    <span x-show="translating">Translating...</span>
                    <svg x-show="translating" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
            <!-- Error message -->
            <div x-show="error" x-text="error" class="text-red-600 dark:text-red-400 text-sm p-3 bg-red-50 dark:bg-red-900/20 rounded-lg"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function translationEditor() {
    return {
        sourceContent: '',
        translatedContent: '',
        sourceLang: '',
        targetLang: 'es',
        context: 'general',
        detectedLang: '',
        qualityScore: null,
        translating: false,
        error: '',

        detectLanguage() {
            if (!this.sourceContent || this.sourceContent.length < 10) {
                this.detectedLang = '';
                return;
            }

            fetch('{{ route("translate.detect") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ text: this.sourceContent.substring(0, 500) }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.detectedLang = data.data.name;
                    if (!this.sourceLang) {
                        this.sourceLang = data.data.code;
                    }
                }
            })
            .catch(() => {});
        },

        translate() {
            if (!this.sourceContent || !this.targetLang) return;

            this.translating = true;
            this.error = '';
            this.qualityScore = null;

            fetch('{{ route("translate.translate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    content: this.sourceContent,
                    source_lang: this.sourceLang || 'en',
                    target_lang: this.targetLang,
                    context: this.context,
                }),
            })
            .then(r => r.json())
            .then(data => {
                this.translating = false;
                if (data.success) {
                    this.translatedContent = data.data.translated;
                    this.qualityScore = data.data.quality_score;
                } else {
                    this.error = data.message || 'Translation failed';
                }
            })
            .catch(err => {
                this.translating = false;
                this.error = 'Network error. Please try again.';
            });
        },

        copyTranslation() {
            if (this.translatedContent) {
                navigator.clipboard.writeText(this.translatedContent);
            }
        },
    };
}
</script>
@endpush
