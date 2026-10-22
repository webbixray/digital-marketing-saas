<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ContentAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ContentAssetFactory extends Factory
{
    protected $model = ContentAsset::class;

    public function definition(): array
    {
        $types = ['text', 'image', 'video', 'audio', 'document', 'link'];
        $type = fake()->randomElement($types);
        $name = fake()->words(3, true);

        return [
            'agency_id' => Agency::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'type' => $type,
            'content' => fake()->paragraphs(2, true),
            'media_url' => null,
            'tags' => fake()->words(3),
            'is_public' => false,
            'status' => 'active',
        ];
    }
}
