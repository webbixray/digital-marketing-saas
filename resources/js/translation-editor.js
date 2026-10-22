/**
 * Translation Editor - Alpine.js Component
 *
 * Provides a live translation editor with:
 * - Source/target language selectors
 * - Content editor with live preview
 * - Language detection
 * - Copy/paste functionality
 * - Quality score display
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('translationEditor', () => ({
        sourceContent: '',
        translatedContent: '',
        sourceLang: '',
        targetLang: 'es',
        context: 'general',
        detectedLang: '',
        qualityScore: null,
        translating: false,
        error: '',
        copySuccess: false,
        detectTimeout: null,

        /**
         * Available languages
         */
        languages: {
            en: 'English',
            es: 'Spanish',
            fr: 'French',
            de: 'German',
            it: 'Italian',
            pt: 'Portuguese',
            nl: 'Dutch',
            ru: 'Russian',
            zh: 'Chinese',
            ja: 'Japanese',
            ko: 'Korean',
            ar: 'Arabic',
            hi: 'Hindi',
            tr: 'Turkish',
            pl: 'Polish',
            sv: 'Swedish',
            da: 'Danish',
            fi: 'Finnish',
            no: 'Norwegian',
            th: 'Thai',
            vi: 'Vietnamese',
            id: 'Indonesian',
            ms: 'Malay',
        },

        /**
         * Initialize component
         */
        init() {
            // Set CSRF token for fetch requests
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        },

        /**
         * Detect language of source content (debounced)
         */
        detectLanguage() {
            if (this.detectTimeout) {
                clearTimeout(this.detectTimeout);
            }

            this.detectTimeout = setTimeout(() => {
                if (!this.sourceContent || this.sourceContent.trim().length < 10) {
                    this.detectedLang = '';
                    return;
                }

                this.callDetectEndpoint(this.sourceContent.substring(0, 500));
            }, 500);
        },

        /**
         * Call language detection endpoint
         */
        async callDetectEndpoint(text) {
            try {
                const response = await fetch('/translate/detect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ text }),
                });

                const data = await response.json();

                if (data.success) {
                    this.detectedLang = data.data.name;
                    if (!this.sourceLang) {
                        this.sourceLang = data.data.code;
                    }
                }
            } catch (err) {
                console.error('Language detection failed:', err);
            }
        },

        /**
         * Translate content
         */
        async translate() {
            if (!this.sourceContent || !this.targetLang || this.translating) {
                return;
            }

            if (this.sourceLang === this.targetLang) {
                this.error = 'Source and target languages must be different.';
                return;
            }

            this.translating = true;
            this.error = '';
            this.qualityScore = null;
            this.translatedContent = '';

            try {
                const response = await fetch('/translate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        content: this.sourceContent,
                        source_lang: this.sourceLang || 'en',
                        target_lang: this.targetLang,
                        context: this.context,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.translatedContent = data.data.translated;
                    this.qualityScore = data.data.quality_score;
                } else {
                    this.error = data.message || 'Translation failed. Please try again.';
                }
            } catch (err) {
                this.error = 'Network error. Please check your connection and try again.';
            } finally {
                this.translating = false;
            }
        },

        /**
         * Copy translated content to clipboard
         */
        async copyTranslation() {
            if (!this.translatedContent) {
                return;
            }

            try {
                await navigator.clipboard.writeText(this.translatedContent);
                this.copySuccess = true;
                setTimeout(() => {
                    this.copySuccess = false;
                }, 2000);
            } catch (err) {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = this.translatedContent;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                this.copySuccess = true;
                setTimeout(() => {
                    this.copySuccess = false;
                }, 2000);
            }
        },

        /**
         * Paste from clipboard into source
         */
        async pasteFromClipboard() {
            try {
                const text = await navigator.clipboard.readText();
                this.sourceContent = text;
                this.detectLanguage();
            } catch (err) {
                console.error('Failed to read clipboard:', err);
            }
        },

        /**
         * Clear source content
         */
        clearSource() {
            this.sourceContent = '';
            this.detectedLang = '';
        },

        /**
         * Swap source and target languages
         */
        swapLanguages() {
            const temp = this.sourceLang;
            this.sourceLang = this.targetLang;
            this.targetLang = temp;

            if (this.translatedContent) {
                this.sourceContent = this.translatedContent;
                this.translatedContent = '';
                this.qualityScore = null;
            }
        },

        /**
         * Get quality score color class
         */
        get qualityColorClass() {
            if (this.qualityScore === null) return '';
            if (this.qualityScore >= 80) return 'text-green-600 dark:text-green-400';
            if (this.qualityScore >= 50) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-red-600 dark:text-red-400';
        },

        /**
         * Get quality bar color class
         */
        get qualityBarClass() {
            if (this.qualityScore === null) return '';
            if (this.qualityScore >= 80) return 'bg-green-500';
            if (this.qualityScore >= 50) return 'bg-yellow-500';
            return 'bg-red-500';
        },

        /**
         * Get character count for source
         */
        get sourceCharCount() {
            return this.sourceContent.length;
        },

        /**
         * Get character count for translated
         */
        get translatedCharCount() {
            return this.translatedContent.length;
        },
    }));
});
