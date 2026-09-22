<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\User;
use App\Models\BulkUpload;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkUploadFactory extends Factory
{
    protected $model = BulkUpload::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'user_id' => User::factory(),
            'original_filename' => fake()->word() . '.csv',
            'stored_path' => 'uploads/' . fake()->uuid() . '.csv',
            'file_type' => 'csv',
            'total_rows' => fake()->numberBetween(10, 1000),
            'processed_rows' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'errors' => [],
            'status' => 'pending',
            'processed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function processing(): static
    {
        return $this->state(['status' => 'processing']);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'processed_rows' => 100,
            'success_count' => 100,
            'processed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}
