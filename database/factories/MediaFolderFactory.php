<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\MediaFolder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFolderFactory extends Factory
{
    protected $model = MediaFolder::class;

    public function definition(): array
    {
        $name = fake()->word();

        return [
            'agency_id' => Agency::factory(),
            'parent_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(4),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
