<?php

namespace App\Services\Schedule;

use App\Enums\PostStatus;
use App\Models\BulkSchedule;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BulkScheduleService
{
    /**
     * Parse a CSV file and return structured data.
     */
    public function parseCSV(string $filePath, string $disk = 'local'): array
    {
        $content = Storage::disk($disk)->get($filePath);

        if ($content === null) {
            return [
                'success' => false,
                'error' => 'File not found.',
                'headers' => [],
                'rows' => [],
            ];
        }

        $lines = $this->parseMultilineCSV($content);

        if (empty($lines)) {
            return [
                'success' => false,
                'error' => 'CSV file is empty.',
                'headers' => [],
                'rows' => [],
            ];
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        $rows = [];
        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $fields = str_getcsv($line);
            $row = [];

            foreach ($headers as $headerIndex => $header) {
                $row[$header] = isset($fields[$headerIndex]) ? trim($fields[$headerIndex]) : '';
            }

            $row['_line_number'] = $index + 2; // +2 for header and 1-based index
            $rows[] = $row;
        }

        return [
            'success' => true,
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Handle multiline CSV fields properly.
     */
    private function parseMultilineCSV(string $content): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tempFile, $content);

        $lines = [];
        $handle = fopen($tempFile, 'r');
        $currentLine = '';

        if ($handle !== false) {
            while (($line = fgets($handle)) !== false) {
                $currentLine .= $line;

                // Check if the line has complete quotes (even number of quotes)
                if (substr_count($currentLine, '"') % 2 === 0) {
                    $lines[] = $currentLine;
                    $currentLine = '';
                }
            }

            if (!empty(trim($currentLine))) {
                $lines[] = $currentLine;
            }

            fclose($handle);
        }

        unlink($tempFile);

        return $lines;
    }

    /**
     * Validate a single CSV row.
     */
    public function validateRow(array $row, array $validationRules, int $agencyId): array
    {
        $errors = [];
        $data = [];

        // Content validation
        $content = $row['content'] ?? '';
        if (empty($content)) {
            $errors[] = 'Content is required.';
        } elseif (strlen($content) > 5000) {
            $errors[] = 'Content exceeds maximum length of 5000 characters.';
        } else {
            $data['content'] = $content;
        }

        // Platform validation
        $platform = strtolower($row['platform'] ?? '');
        if (empty($platform)) {
            $errors[] = 'Platform is required.';
        } else {
            $validPlatforms = array_keys(SocialAccount::SUPPORTED_PLATFORMS);
            if (!in_array($platform, $validPlatforms)) {
                $errors[] = 'Invalid platform. Must be one of: ' . implode(', ', $validPlatforms) . '.';
            } else {
                $data['platform'] = $platform;

                // Find social account for this platform
                $account = SocialAccount::where('agency_id', $agencyId)
                    ->where('platform', $platform)
                    ->where('is_active', true)
                    ->first();

                if (!$account) {
                    $errors[] = "No active social account found for platform: {$platform}.";
                } else {
                    $data['social_account_id'] = $account->id;
                }
            }
        }

        // Scheduled at validation
        $scheduledAt = $row['scheduled_at'] ?? '';
        if (empty($scheduledAt)) {
            $errors[] = 'Scheduled date is required.';
        } else {
            $parsedDate = $this->parseDate($scheduledAt);
            if ($parsedDate === null) {
                $errors[] = 'Invalid date format. Use YYYY-MM-DD HH:MM:SS.';
            } elseif ($parsedDate->isPast()) {
                $errors[] = 'Scheduled date must be in the future.';
            } else {
                $data['scheduled_at'] = $parsedDate->toDateTimeString();
            }
        }

        // Media URL (optional)
        $mediaUrl = $row['media_url'] ?? '';
        if (!empty($mediaUrl)) {
            if (!filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid media URL format.';
            } else {
                $data['media_url'] = $mediaUrl;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data,
        ];
    }

    /**
     * Create multiple posts from validated rows.
     */
    public function createPosts(int $agencyId, array $validatedRows): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($validatedRows as $index => $row) {
            try {
                $post = DB::transaction(function () use ($agencyId, $row) {
                    $postData = [
                        'social_account_id' => $row['social_account_id'],
                        'platform' => $row['platform'],
                        'content' => $row['content'],
                        'status' => PostStatus::SCHEDULED->value,
                        'scheduled_at' => $row['scheduled_at'],
                    ];

                    if (!empty($row['media_url'])) {
                        $postData['media'] = [$row['media_url']];
                    }

                    return SocialPost::create(array_merge(
                        ['agency_id' => $agencyId],
                        $postData
                    ));
                });

                $results['success'][] = [
                    'row' => $index + 1,
                    'post_id' => $post->id,
                    'platform' => $post->platform,
                    'scheduled_at' => $post->scheduled_at->toDateTimeString(),
                ];
            } catch (\Exception $e) {
                Log::error("Bulk schedule failed for row {$index}: " . $e->getMessage());

                $results['failed'][] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Generate a sample CSV template for download.
     */
    public function generateTemplate(): string
    {
        $headers = ['content', 'platform', 'scheduled_at', 'media_url'];
        $platforms = implode(', ', array_keys(SocialAccount::SUPPORTED_PLATFORMS));
        $exampleDate = now()->addDay()->format('Y-m-d H:i:s');

        $csv = implode(',', $headers) . "\n";
        $csv .= '"Check out our new product launch!",facebook,"' . $exampleDate . '",https://example.com/image.jpg' . "\n";
        $csv .= '"Join us for a live webinar.",twitter,"' . $exampleDate . '",' . "\n";
        $csv .= '"Exciting news coming soon...",linkedin,"' . $exampleDate . '",' . "\n";

        return $csv;
    }

    /**
     * Get validation rules documentation.
     */
    public function getValidationRules(): array
    {
        return [
            'content' => [
                'required' => true,
                'max_length' => 5000,
                'description' => 'Post content (required, max 5000 chars)',
            ],
            'platform' => [
                'required' => true,
                'values' => array_keys(SocialAccount::SUPPORTED_PLATFORMS),
                'description' => 'Social platform (required, must have active account)',
            ],
            'scheduled_at' => [
                'required' => true,
                'format' => 'YYYY-MM-DD HH:MM:SS',
                'description' => 'Schedule date/time (required, must be future)',
            ],
            'media_url' => [
                'required' => false,
                'format' => 'URL',
                'description' => 'Media URL (optional)',
            ],
        ];
    }

    /**
     * Parse a date string to Carbon instance.
     */
    private function parseDate(string $date): ?\Carbon\Carbon
    {
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd/m/Y H:i:s',
            'd/m/Y',
            'm/d/Y H:i:s',
            'm/d/Y',
        ];

        foreach ($formats as $format) {
            try {
                return \Carbon\Carbon::createFromFormat($format, $date);
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
