<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\BulkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkScheduleFactory extends Factory
{
    protected $model = BulkSchedule::class;

    public function definition(): array
    {
        $totalRows = fake()->numberBetween(10, 500);
        $failedRows = fake()->numberBetween(0, (int) ($totalRows * 0.2));
        $processedRows = fake()->numberBetween(0, $totalRows - $failedRows);

        return [
            'agency_id' => Agency::factory(),
            'user_id' => null,
            'filename' => fake()->word() . '.csv',
            'total_rows' => $totalRows,
            'successful_rows' => $processedRows,
            'failed_rows' => $failedRows,
            'processed_rows' => $processedRows + $failedRows,
            'errors' => [],
            'row_results' => [],
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed']),
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
