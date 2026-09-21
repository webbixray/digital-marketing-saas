<?php

namespace App\Services\AI\Agent;

use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;

class MultilingualEngine
{
    /**
     * Supported languages (ISO 639-1 codes)
     */
    public const LANGUAGES = [
        'en' => ['name' => 'English', 'native' => 'English'],
        'es' => ['name' => 'Spanish', 'native' => 'Español'],
        'fr' => ['name' => 'French', 'native' => 'Français'],
        'de' => ['name' => 'German', 'native' => 'Deutsch'],
        'it' => ['name' => 'Italian', 'native' => 'Italiano'],
        'pt' => ['name' => 'Portuguese', 'native' => 'Português'],
        'nl' => ['name' => 'Dutch', 'native' => 'Nederlands'],
        'pl' => ['name' => 'Polish', 'native' => 'Polski'],
        'ru' => ['name' => 'Russian', 'native' => 'Русский'],
        'ja' => ['name' => 'Japanese', 'native' => '日本語'],
        'ko' => ['name' => 'Korean', 'native' => '한국어'],
        'zh' => ['name' => 'Chinese (Simplified)', 'native' => '简体中文'],
        'zh-tw' => ['name' => 'Chinese (Traditional)', 'native' => '繁體中文'],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية'],
        'he' => ['name' => 'Hebrew', 'native' => 'עברית'],
        'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी'],
        'tr' => ['name' => 'Turkish', 'native' => 'Türkçe'],
        'th' => ['name' => 'Thai', 'native' => 'ไทย'],
        'vi' => ['name' => 'Vietnamese', 'native' => 'Tiếng Việt'],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia'],
        'ms' => ['name' => 'Malay', 'native' => 'Bahasa Melayu'],
        'sv' => ['name' => 'Swedish', 'native' => 'Svenska'],
        'da' => ['name' => 'Danish', 'native' => 'Dansk'],
        'no' => ['name' => 'Norwegian', 'native' => 'Norsk'],
        'fi' => ['name' => 'Finnish', 'native' => 'Suomi'],
        'el' => ['name' => 'Greek', 'native' => 'Ελληνικά'],
        'cs' => ['name' => 'Czech', 'native' => 'Čeština'],
        'ro' => ['name' => 'Romanian', 'native' => 'Română'],
        'hu' => ['name' => 'Hungarian', 'native' => 'Magyar'],
        'bg' => ['name' => 'Bulgarian', 'native' => 'Български'],
        'uk' => ['name' => 'Ukrainian', 'native' => 'Українська'],
        'hr' => ['name' => 'Croatian', 'native' => 'Hrvatski'],
        'sk' => ['name' => 'Slovak', 'native' => 'Slovenčina'],
        'sl' => ['name' => 'Slovenian', 'native' => 'Slovenščina'],
        'sr' => ['name' => 'Serbian', 'native' => 'Српски'],
        'ca' => ['name' => 'Catalan', 'native' => 'Català'],
        'gl' => ['name' => 'Galician', 'native' => 'Galego'],
        'eu' => ['name' => 'Basque', 'native' => 'Euskara'],
        'lv' => ['name' => 'Latvian', 'native' => 'Latviešu'],
        'lt' => ['name' => 'Lithuanian', 'native' => 'Lietuvių'],
        'et' => ['name' => 'Estonian', 'native' => 'Eesti'],
        'is' => ['name' => 'Icelandic', 'native' => 'Íslenska'],
        'ga' => ['name' => 'Irish', 'native' => 'Gaeilge'],
        'mt' => ['name' => 'Maltese', 'native' => 'Malti'],
        'cy' => ['name' => 'Welsh', 'native' => 'Cymraeg'],
        'mk' => ['name' => 'Macedonian', 'native' => 'Македонски'],
        'sq' => ['name' => 'Albanian', 'native' => 'Shqip'],
        'bs' => ['name' => 'Bosnian', 'native' => 'Bosanski'],
        'ka' => ['name' => 'Georgian', 'native' => 'ქართული'],
        'hy' => ['name' => 'Armenian', 'native' => 'Հայերեն'],
        'az' => ['name' => 'Azerbaijani', 'native' => 'Azərbaycan'],
        'kk' => ['name' => 'Kazakh', 'native' => 'Қазақ'],
        'uz' => ['name' => 'Uzbek', 'native' => 'Oʻzbek'],
        'ta' => ['name' => 'Tamil', 'native' => 'தமிழ்'],
        'te' => ['name' => 'Telugu', 'native' => 'తెలుగు'],
        'ml' => ['name' => 'Malayalam', 'native' => 'മലയാളം'],
        'kn' => ['name' => 'Kannada', 'native' => 'ಕನ್ನಡ'],
        'mr' => ['name' => 'Marathi', 'native' => 'मराठी'],
        'gu' => ['name' => 'Gujarati', 'native' => 'ગુજરાતી'],
        'pa' => ['name' => 'Punjabi', 'native' => 'ਪੰਜਾਬੀ'],
        'bn' => ['name' => 'Bengali', 'native' => 'বাংলা'],
        'ur' => ['name' => 'Urdu', 'native' => 'اردو'],
        'fa' => ['name' => 'Persian', 'native' => 'فارسی'],
        'ps' => ['name' => 'Pashto', 'native' => 'پښتو'],
        'ku' => ['name' => 'Kurdish', 'native' => 'Kurdî'],
        'ne' => ['name' => 'Nepali', 'native' => 'नेपाली'],
        'si' => ['name' => 'Sinhala', 'native' => 'සිංහල'],
        'km' => ['name' => 'Khmer', 'native' => 'ខ្មែរ'],
        'lo' => ['name' => 'Lao', 'native' => 'ລາວ'],
        'my' => ['name' => 'Burmese', 'native' => 'မြန်မာ'],
        'sw' => ['name' => 'Swahili', 'native' => 'Kiswahili'],
        'yo' => ['name' => 'Yoruba', 'native' => 'Yorùbá'],
        'ig' => ['name' => 'Igbo', 'native' => 'Igbo'],
        'ha' => ['name' => 'Hausa', 'native' => 'Hausa'],
        'zu' => ['name' => 'Zulu', 'native' => 'isiZulu'],
        'af' => ['name' => 'Afrikaans', 'native' => 'Afrikaans'],
        'mg' => ['name' => 'Malagasy', 'native' => 'Malagasy'],
        'so' => ['name' => 'Somali', 'native' => 'Soomaali'],
        'rw' => ['name' => 'Kinyarwanda', 'native' => 'Ikinyarwanda'],
        'ny' => ['name' => 'Chichewa', 'native' => 'Chichewa'],
        'sn' => ['name' => 'Shona', 'native' => 'chiShona'],
        'st' => ['name' => 'Sesotho', 'native' => 'Sesotho'],
        'tn' => ['name' => 'Tswana', 'native' => 'Setswana'],
        'ts' => ['name' => 'Tsonga', 'native' => 'Xitsonga'],
        've' => ['name' => 'Venda', 'native' => 'Tshivenḓa'],
        'xh' => ['name' => 'Xhosa', 'native' => 'isiXhosa'],
        'ti' => ['name' => 'Tigrinya', 'native' => 'ትግርኛ'],
        'am' => ['name' => 'Amharic', 'native' => 'አማርኛ'],
    ];

    public function __construct(
        private readonly AiGateway $aiGateway
    ) {}

    /**
     * Generate content in target language
     */
    public function generate(
        string $prompt,
        string $targetLanguage,
        array $options = []
    ): string {
        if (!isset(self::LANGUAGES[$targetLanguage])) {
            $targetLanguage = 'en';
        }

        $languageName = self::LANGUAGES[$targetLanguage]['name'];
        $nativeName = self::LANGUAGES[$targetLanguage]['native'];

        $instructions = [
            "IMPORTANT: Generate the ENTIRE response in {$languageName} ({$nativeName}).",
            "Use natural, native-quality {$languageName} that sounds like a native speaker.",
            "Adapt cultural references, idioms, and expressions appropriately for {$languageName}-speaking audiences.",
            "Maintain the same meaning and intent as the original prompt.",
        ];

        if (isset($options['tone'])) {
            $instructions[] = "Tone: {$options['tone']}.";
        }

        if (isset($options['formality'])) {
            $instructions[] = "Formality level: {$options['formality']}.";
        }

        $fullPrompt = implode("\n", $instructions) . "\n\n" . $prompt;

        $response = $this->aiGateway->send(new AiRequest(
            prompt: $fullPrompt,
            maxTokens: $options['max_tokens'] ?? 2000,
        ));

        return $response->content;
    }

    /**
     * Detect language of text
     */
    public function detectLanguage(string $text): string
    {
        // Quick detection for common patterns
        $patterns = [
            'zh' => '/[\x{4e00}-\x{9fff}]/u',
            'ja' => '/[\x{3040}-\x{309f}\x{30a0}-\x{30ff}]/u',
            'ko' => '/[\x{ac00}-\xd7af}]/u',
            'ar' => '/[\x{0600}-\x{06ff}]/u',
            'he' => '/[\x{0590}-\x{05ff}]/u',
            'hi' => '/[\x{0900}-\x{097f}]/u',
            'th' => '/[\x{0e00}-\x{0e7f}]/u',
            'ru' => '/[\x{0400}-\x{04ff}]/u',
        ];

        foreach ($patterns as $lang => $pattern) {
            if (preg_match($pattern, $text)) {
                return $lang;
            }
        }

        // Use AI for ambiguous cases
        $response = $this->aiGateway->send(new AiRequest(
            prompt: "Detect the language of this text. Respond with ONLY the ISO 639-1 code (en, es, fr, de, etc.):\n\n" . substr($text, 0, 200),
            maxTokens: 10,
        ));

        $detected = trim(strtolower($response->content));
        return isset(self::LANGUAGES[$detected]) ? $detected : 'en';
    }

    /**
     * Translate content between languages
     */
    public function translate(
        string $content,
        string $fromLanguage,
        string $toLanguage,
        array $options = []
    ): string {
        if ($fromLanguage === $toLanguage) {
            return $content;
        }

        $fromName = self::LANGUAGES[$fromLanguage]['name'] ?? 'English';
        $toName = self::LANGUAGES[$toLanguage]['name'] ?? 'English';

        $prompt = "Translate this content from {$fromName} to {$toName}.\n\n";

        if (isset($options['context'])) {
            $prompt .= "Context: {$options['context']}\n\n";
        }

        if (isset($options['preserve_formatting']) && $options['preserve_formatting']) {
            $prompt .= "Preserve all formatting (line breaks, markdown, hashtags, @mentions).\n\n";
        }

        $prompt .= "Content:\n{$content}";

        return $this->generate($prompt, $toLanguage, $options);
    }

    /**
     * Localize content (translate + cultural adaptation)
     */
    public function localize(
        string $content,
        string $targetLanguage,
        array $options = []
    ): string {
        $languageName = self::LANGUAGES[$targetLanguage]['name'];

        $prompt = "Localize this content for {$languageName}-speaking audiences.\n\n";
        $prompt .= "This means:\n";
        $prompt .= "1. Translate to natural {$languageName}\n";
        $prompt .= "2. Adapt cultural references, idioms, and humor\n";
        $prompt .= "3. Use appropriate date/time formats\n";
        $prompt .= "4. Adapt examples and metaphors\n";
        $prompt .= "5. Maintain the same intent and tone\n\n";

        if (isset($options['region'])) {
            $prompt .= "Target region: {$options['region']}\n\n";
        }

        $prompt .= "Content:\n{$content}";

        return $this->generate($prompt, $targetLanguage, $options);
    }

    /**
     * Get supported languages list
     */
    public static function getSupportedLanguages(): array
    {
        return self::LANGUAGES;
    }

    /**
     * Check if language is supported
     */
    public static function isSupported(string $language): bool
    {
        return isset(self::LANGUAGES[$language]);
    }
}
