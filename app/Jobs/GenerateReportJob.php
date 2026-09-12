<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 300;

    public function __construct(
        public readonly Report $report,
    ) {}

    public function handle(): void
    {
        $this->report->update(['status' => 'processing']);

        try {
            $this->generate();

            $this->report->update([
                'status' => 'completed',
                'last_generated_at' => now(),
            ]);

            Log::info('Report generated successfully', [
                'report_id' => $this->report->id,
                'agency_id' => $this->report->agency_id,
            ]);
        } catch (Throwable $e) {
            $this->report->update(['status' => 'failed']);

            Log::error('Report generation failed', [
                'report_id' => $this->report->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->report->update(['status' => 'failed']);

        Log::error('GenerateReportJob failed permanently', [
            'report_id' => $this->report->id,
            'error' => $exception->getMessage(),
        ]);
    }

    private function generate(): void
    {
        $report = $this->report;

        Log::info('Generating report', [
            'report_id' => $report->id,
            'type' => $report->type,
            'format' => $report->format,
        ]);

        // Report generation logic placeholder
        // In production, this would query data, format it, and store the file
    }
}
