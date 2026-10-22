<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'file_path' => 'media/'.fake()->uuid().'.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'width' => 1920,
            'height' => 1080,
            'alt_text' => fake()->sentence(),
            'folder' => fake()->randomElement(['social-posts', 'banners', 'logos', 'uncategorized']),
            'tags' => [fake()->word(), fake()->word()],
            'is_public' => fake()->boolean(20),
            'usage_count' => fake()->numberBetween(0, 50),
        ];
    }
}
