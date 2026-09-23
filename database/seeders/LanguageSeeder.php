<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Languages to seed.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $languages = [
        [
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'is_rtl' => false,
            'is_active' => true,
            'sort_order' => 1,
            'flag_emoji' => '🇬🇧',
        ],
        [
            'code' => 'es',
            'name' => 'Spanish',
            'native_name' => 'Español',
            'is_rtl' => false,
            'is_active' => true,
            'sort_order' => 2,
            'flag_emoji' => '🇪🇸',
        ],
        [
            'code' => 'fr',
            'name' => 'French',
            'native_name' => 'Français',
            'is_rtl' => false,
            'is_active' => true,
            'sort_order' => 3,
            'flag_emoji' => '🇫🇷',
        ],
        [
            'code' => 'de',
            'name' => 'German',
            'native_name' => 'Deutsch',
            'is_rtl' => false,
            'is_active' => true,
            'sort_order' => 4,
            'flag_emoji' => '🇩🇪',
        ],
        [
            'code' => 'zh',
            'name' => 'Chinese',
            'native_name' => '中文',
            'is_rtl' => false,
            'is_active' => true,
            'sort_order' => 5,
            'flag_emoji' => '🇨🇳',
        ],
        [
            'code' => 'ja',
            'name' => 'Japanese',
            'native_name' => '日本語',
            'is_rtl' => false,
            'is_active' => true,
            'sort_order' => 6,
            'flag_emoji' => '🇯🇵',
        ],
        [
            'code' => 'ar',
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'is_rtl' => true,
            'is_active' => true,
            'sort_order' => 7,
            'flag_emoji' => '🇸🇦',
        ],
        [
            'code' => 'my',
            'name' => 'Myanmar',
            'native_name' => 'မြန်မာ',
            'is_rtl' => false,
            'is_active' => true,
            'sort_order' => 8,
            'flag_emoji' => '🇲🇲',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }
    }
}
