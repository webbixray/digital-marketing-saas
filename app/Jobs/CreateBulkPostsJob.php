<?php

namespace App\Jobs;

use App\Models\BulkSchedule;
use App\Services\Schedule\BulkScheduleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateBulkPostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BulkSchedule $bulkSchedule,
        public string $filePath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BulkScheduleService $service): void
    {
        $agencyId = $this->bulkSchedule->agency_id;

        // Mark as processing
        $this->bulkSchedule->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            // Parse CSV
            $parseResult = $service->parseCSV($this->filePath);

            if (!$parseResult['success']) {
                $this->bulkSchedule->update([
                    'status' => 'failed',
                    'errors' => [$parseResult['error']],
                    'completed_at' => now(),
                ]);
                return;
            }

            $rows = $parseResult['rows'];
            $totalRows = count($rows);

            $this->bulkSchedule->update([
                'total_rows' => $totalRows,
            ]);

            // Validate all rows
            $validationRules = $service->getValidationRules();
            $validatedRows = [];
            $rowResults = [];

            foreach ($rows as $row) {
                $lineNumber = $row['_line_number'] ?? 0;
                $validation = $service->validateRow($row, $validationRules, $agencyId);

                if ($validation['valid']) {
                    $validatedRows[] = $validation['data'];
                    $rowResults[] = [
                        'row' => $lineNumber,
                        'status' => 'valid',
                        'data' => $validation['data'],
                    ];
                } else {
                    $rowResults[] = [
                        'row' => $lineNumber,
                        'status' => 'invalid',
                        'errors' => $validation['errors'],
                    ];
                }
            }

            // Create posts for valid rows
            $createResults = $service->createPosts($agencyId, $validatedRows);

            // Update final status
            $this->bulkSchedule->update([
                'status' => 'completed',
                'successful_rows' => count($createResults['success']),
                'failed_rows' => count($createResults['failed']) + count($rowResults) - count($validatedRows),
                'processed_rows' => $totalRows,
                'row_results' => $rowResults,
                'errors' => array_merge(
                    array_column($createResults['failed'], 'error')
                ),
                'completed_at' => now(),
            ]);

            // Clean up uploaded file
            Storage::disk('local')->delete($this->filePath);

        } catch (\Exception $e) {
            Log::error("Bulk schedule job failed: " . $e->getMessage());

            $this->bulkSchedule->update([
                'status' => 'failed',
                'errors' => [$e->getMessage()],
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->bulkSchedule->update([
            'status' => 'failed',
            'errors' => [$exception->getMessage()],
            'completed_at' => now(),
        ]);
    }
}
