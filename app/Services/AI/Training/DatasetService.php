<?php

namespace App\Services\AI\Training;

use App\Models\AiTrainingDataset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatasetService
{
    private const ALLOWED_EXTENSIONS = ['csv', 'json', 'jsonl', 'xlsx'];
    private const MAX_FILE_SIZE = 102400; // 100 MB in KB
    private const STORAGE_DISK = 'local';
    private const STORAGE_PATH = 'ai-training/datasets';

    /**
     * Upload a training dataset file and create a record.
     */
    public function uploadDataset(UploadedFile $file, int $agencyId, string $name, ?string $description = null): AiTrainingDataset
    {
        $extension = $file->getClientOriginalExtension();
        $this->validateFileExtension($extension);

        $fileHash = hash_file('sha256', $file->getRealPath());

        $fileName = sprintf(
            '%s_%s.%s',
            $agencyId,
            Str::uuid()->toString(),
            $extension
        );

        $filePath = $file->storeAs(self::STORAGE_PATH, $fileName, self::STORAGE_DISK);

        $dataset = AiTrainingDataset::create([
            'agency_id' => $agencyId,
            'name' => $name,
            'description' => $description,
            'file_path' => $filePath,
            'file_hash' => $fileHash,
            'row_count' => 0,
            'column_count' => 0,
            'status' => 'uploading',
            'metadata' => [
                'original_filename' => $file->getClientOriginalName(),
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ],
        ]);

        // Trigger processing
        $this->processDataset($dataset->id);

        return $dataset;
    }

    /**
     * Validate a dataset file for integrity and structure.
     */
    public function validateDataset(int $datasetId): array
    {
        $dataset = AiTrainingDataset::findOrFail($datasetId);

        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        if (! Storage::disk(self::STORAGE_DISK)->exists($dataset->file_path)) {
            $result['valid'] = false;
            $result['errors'][] = 'Dataset file not found.';
            return $result;
        }

        $filePath = Storage::disk(self::STORAGE_DISK)->path($dataset->file_path);
        $currentHash = hash_file('sha256', $filePath);

        if ($dataset->file_hash && $currentHash !== $dataset->file_hash) {
            $result['valid'] = false;
            $result['errors'][] = 'File integrity check failed (hash mismatch).';
        }

        $extension = pathinfo($dataset->file_path, PATHINFO_EXTENSION);

        if ($extension === 'csv') {
            $csvResult = $this->validateCsvStructure($filePath);
            $result['errors'] = array_merge($result['errors'], $csvResult['errors']);
            $result['warnings'] = array_merge($result['warnings'], $csvResult['warnings']);
        }

        if (! empty($result['errors'])) {
            $result['valid'] = false;
            $dataset->update(['status' => 'error']);
        }

        return $result;
    }

    /**
     * Process a dataset file to extract statistics.
     */
    public function processDataset(int $datasetId): AiTrainingDataset
    {
        $dataset = AiTrainingDataset::findOrFail($datasetId);
        $dataset->update(['status' => 'processing']);

        try {
            $filePath = Storage::disk(self::STORAGE_DISK)->path($dataset->file_path);

            if (! file_exists($filePath)) {
                throw new \RuntimeException('Dataset file not found for processing.');
            }

            $extension = pathinfo($dataset->file_path, PATHINFO_EXTENSION);
            $stats = [];

            if ($extension === 'csv') {
                $stats = $this->processCsvFile($filePath);
            } elseif ($extension === 'json' || $extension === 'jsonl') {
                $stats = $this->processJsonFile($filePath);
            } else {
                $stats = [
                    'row_count' => 0,
                    'column_count' => 0,
                ];
            }

            $dataset->update([
                'row_count' => $stats['row_count'],
                'column_count' => $stats['column_count'],
                'status' => 'ready',
                'metadata' => array_merge($dataset->metadata ?? [], $stats['metadata'] ?? []),
            ]);
        } catch (\Exception $e) {
            Log::error("Dataset processing failed for dataset #{$datasetId}: {$e->getMessage()}");
            $dataset->update(['status' => 'error']);
        }

        return $dataset->fresh();
    }

    /**
     * Get statistics for a dataset.
     */
    public function getDatasetStats(int $datasetId): array
    {
        $dataset = AiTrainingDataset::findOrFail($datasetId);

        return [
            'id' => $dataset->id,
            'name' => $dataset->name,
            'status' => $dataset->status,
            'row_count' => $dataset->row_count,
            'column_count' => $dataset->column_count,
            'file_hash' => $dataset->file_hash,
            'metadata' => $dataset->metadata,
            'created_at' => $dataset->created_at->toDateTimeString(),
        ];
    }

    /**
     * Delete a dataset and its associated file.
     */
    public function deleteDataset(int $datasetId): bool
    {
        $dataset = AiTrainingDataset::findOrFail($datasetId);

        if (Storage::disk(self::STORAGE_DISK)->exists($dataset->file_path)) {
            Storage::disk(self::STORAGE_DISK)->delete($dataset->file_path);
        }

        return $dataset->delete();
    }

    /**
     * Get all datasets for an agency.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AiTrainingDataset>
     */
    public function getDatasetsForAgency(int $agencyId)
    {
        return AiTrainingDataset::byAgency($agencyId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Validate the file extension against allowed types.
     */
    private function validateFileExtension(string $extension): void
    {
        if (! in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                'Invalid file type. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }
    }

    /**
     * Validate CSV structure and return errors/warnings.
     */
    private function validateCsvStructure(string $filePath): array
    {
        $result = ['errors' => [], 'warnings' => []];

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            $result['errors'][] = 'Cannot open CSV file.';
            return $result;
        }

        $header = fgetcsv($handle);
        fclose($handle);

        if (! $header || count($header) < 2) {
            $result['errors'][] = 'CSV must have at least 2 columns (input, output).';
        }

        $duplicateColumns = array_diff_assoc($header, array_unique($header));
        if (! empty($duplicateColumns)) {
            $result['warnings'][] = 'Duplicate column names detected: ' . implode(', ', $duplicateColumns);
        }

        return $result;
    }

    /**
     * Process a CSV file and return stats.
     */
    private function processCsvFile(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException('Cannot open CSV file for processing.');
        }

        $header = fgetcsv($handle);
        $columnCount = $header ? count($header) : 0;
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
        }

        fclose($handle);

        return [
            'row_count' => $rowCount,
            'column_count' => $columnCount,
            'metadata' => [
                'columns' => $header,
            ],
        ];
    }

    /**
     * Process a JSON/JSONL file and return stats.
     */
    private function processJsonFile(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $lines = array_filter(array_map('trim', explode("\n", $content)));

        if (empty($lines)) {
            // Try as single JSON array
            $data = json_decode($content, true);
            if (is_array($data)) {
                $rowCount = count($data);
                $columnCount = ! empty($data) ? count(array_keys($data[0])) : 0;

                return [
                    'row_count' => $rowCount,
                    'column_count' => $columnCount,
                    'metadata' => [
                        'format' => 'json_array',
                        'columns' => ! empty($data) ? array_keys($data[0]) : [],
                    ],
                ];
            }

            return [
                'row_count' => 0,
                'column_count' => 0,
                'metadata' => ['format' => 'json'],
            ];
        }

        // JSONL format
        $firstLine = json_decode($lines[0], true);
        $columnCount = is_array($firstLine) ? count(array_keys($firstLine)) : 0;

        return [
            'row_count' => count($lines),
            'column_count' => $columnCount,
            'metadata' => [
                'format' => 'jsonl',
                'columns' => is_array($firstLine) ? array_keys($firstLine) : [],
            ],
        ];
    }
}
