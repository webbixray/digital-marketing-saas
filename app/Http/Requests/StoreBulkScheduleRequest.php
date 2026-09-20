<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StoreBulkScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->agency_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxSize = $this->routeIs('social.bulk.sync') ? 2048 : 10240;

        return [
            'csv_file' => [
                'required',
                'file',
                'max:' . $maxSize,
                'mimes:csv,txt',
                'mimetypes:text/csv,text/plain,text/x-comma-separated-values,text/x-csv,application/csv,application/vnd.ms-excel',
            ],
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    public function messages(): array
    {
        return [
            'csv_file.mimes' => 'Invalid file extension. Only CSV and TXT files are allowed.',
            'csv_file.mimetypes' => 'Invalid MIME type. Only CSV and TXT files are allowed.',
        ];
    }

    /**
     * Handle additional content sniffing validation.
     */
    protected function passedValidation(): void
    {
        $file = $this->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv_file' => 'Unable to read the uploaded file.',
            ]);
        }

        $content = fread($handle, 4096);
        fclose($handle);

        if ($content === false || strlen($content) === 0) {
            throw ValidationException::withMessages([
                'csv_file' => 'The uploaded file is empty.',
            ]);
        }

        // Check for PHP tags
        if (stripos($content, '<?php') !== false || stripos($content, '<?=') !== false) {
            Log::warning('BulkSchedule upload blocked: PHP tags detected', [
                'user_id' => $this->user()->id,
                'filename' => $file->getClientOriginalName(),
            ]);
            throw ValidationException::withMessages([
                'csv_file' => 'Invalid content detected in the file.',
            ]);
        }

        // Check for HTML/script content
        if (preg_match('/<\s*script/i', $content) || preg_match('/<\s*html/i', $content) || preg_match('/<\s*!doctype/i', $content)) {
            Log::warning('BulkSchedule upload blocked: HTML/script content detected', [
                'user_id' => $this->user()->id,
                'filename' => $file->getClientOriginalName(),
            ]);
            throw ValidationException::withMessages([
                'csv_file' => 'Invalid content detected in the file.',
            ]);
        }

        // Check for binary content
        if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $content)) {
            Log::warning('BulkSchedule upload blocked: binary content detected', [
                'user_id' => $this->user()->id,
                'filename' => $file->getClientOriginalName(),
            ]);
            throw ValidationException::withMessages([
                'csv_file' => 'Invalid content detected in the file.',
            ]);
        }

        // Verify CSV structure
        $lines = str_getcsv($content, "\n");
        if (count($lines) < 1) {
            throw ValidationException::withMessages([
                'csv_file' => 'The file does not appear to be a valid CSV.',
            ]);
        }
    }
}
