<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\ProductTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductTagFactory extends Factory
{
    protected $model = ProductTag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'agency_id' => Agency::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
        ];
    }
}
