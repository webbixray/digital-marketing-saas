<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBulkScheduleRequest;
use App\Jobs\CreateBulkPostsJob;
use App\Models\BulkSchedule;
use App\Models\SocialAccount;
use App\Services\Schedule\BulkScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulkScheduleController extends Controller
{
    public function __construct(
        private readonly BulkScheduleService $bulkService
    ) {
        $this->middleware(['auth', 'agency']);
        $this->middleware('can:view,bulkSchedule')->only('show');
    }

    /**
     * Display the upload form.
     */
    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $platforms = SocialAccount::SUPPORTED_PLATFORMS;
        $validationRules = $this->bulkService->getValidationRules();
        $recentImports = BulkSchedule::forAgency($agencyId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('social.bulk.index', compact('agencyId', 'platforms', 'validationRules', 'recentImports'));
    }

    /**
     * Handle CSV upload and preview.
     */
    public function upload(StoreBulkScheduleRequest $request)
    {
        $file = $request->file('csv_file');
        $agencyId = $request->user()->agency_id;

        // Store file with random name to prevent path traversal
        $storedName = Str::random(40) . '.csv';
        $path = $file->storeAs('bulk-uploads', $storedName, 'local');

        // Parse CSV
        $parseResult = $this->bulkService->parseCSV($path);

        if (!$parseResult['success']) {
            Storage::disk('local')->delete($path);
            return back()->with('error', $parseResult['error']);
        }

        // Validate rows
        $validationRules = $this->bulkService->getValidationRules();
        $validatedRows = [];
        $rowResults = [];

        foreach ($parseResult['rows'] as $row) {
            $lineNumber = $row['_line_number'] ?? 0;
            $validation = $this->bulkService->validateRow($row, $validationRules, $agencyId);

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

        // Store preview data in session for confirmation
        session([
            'bulk_upload' => [
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'total_rows' => count($parseResult['rows']),
                'valid_rows' => count($validatedRows),
                'invalid_rows' => count($parseResult['rows']) - count($validatedRows),
                'row_results' => $rowResults,
            ]
        ]);

        return view('social.bulk.preview', [
            'agencyId' => $agencyId,
            'filename' => $file->getClientOriginalName(),
            'totalRows' => count($parseResult['rows']),
            'validRows' => count($validatedRows),
            'invalidRows' => count($parseResult['rows']) - count($validatedRows),
            'rowResults' => $rowResults,
            'platforms' => SocialAccount::SUPPORTED_PLATFORMS,
        ]);
    }

    /**
     * Store bulk posts (create from validated CSV).
     */
    public function store(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $userId = $request->user()->id;

        $bulkData = session('bulk_upload');

        if (!$bulkData) {
            return redirect()->route('social.bulk.index')
                ->with('error', 'No upload data found. Please upload a CSV file first.');
        }

        $filePath = $bulkData['path'];

        if (!Storage::disk('local')->exists($filePath)) {
            session()->forget('bulk_upload');
            return redirect()->route('social.bulk.index')
                ->with('error', 'Uploaded file not found. Please try again.');
        }

        // Create import history record
        $bulkSchedule = BulkSchedule::create([
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'filename' => $bulkData['filename'],
            'total_rows' => $bulkData['total_rows'],
            'successful_rows' => 0,
            'failed_rows' => 0,
            'processed_rows' => 0,
            'row_results' => $bulkData['row_results'],
            'status' => 'pending',
        ]);

        // Dispatch job for processing
        Log::info('Bulk upload queued', ['bulk_id' => $bulkSchedule->id, 'agency_id' => $agencyId]);
        CreateBulkPostsJob::dispatch($bulkSchedule, $filePath);

        // Clear session
        session()->forget('bulk_upload');

        return redirect()->route('social.bulk.show', $bulkSchedule)
            ->with('success', 'Bulk upload has been queued for processing. You will see results shortly.');
    }

    /**
     * Show import results.
     */
    public function show(Request $request, BulkSchedule $bulkSchedule)
    {
        // Policy handles authorization: agency membership + (own record OR editor)
        $this->authorize('view', $bulkSchedule);

        $agencyId = $request->user()->agency_id;

        return view('social.bulk.show', [
            'agencyId' => $agencyId,
            'bulkSchedule' => $bulkSchedule,
        ]);
    }

    /**
     * Download sample CSV template.
     */
    public function template()
    {
        $csv = $this->bulkService->generateTemplate();

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="bulk_schedule_template.csv"');
    }

    /**
     * Process bulk upload synchronously (for small files).
     */
    public function processSync(StoreBulkScheduleRequest $request)
    {
        $file = $request->file('csv_file');
        $agencyId = $request->user()->agency_id;
        $userId = $request->user()->id;

        // Store file with random name to prevent path traversal
        $storedName = Str::random(40) . '.csv';
        $path = $file->storeAs('bulk-uploads', $storedName, 'local');

        // Parse CSV
        $parseResult = $this->bulkService->parseCSV($path);

        if (!$parseResult['success']) {
            Storage::disk('local')->delete($path);
            return back()->with('error', $parseResult['error']);
        }

        // Validate rows
        $validationRules = $this->bulkService->getValidationRules();
        $validatedRows = [];
        $rowResults = [];

        foreach ($parseResult['rows'] as $row) {
            $lineNumber = $row['_line_number'] ?? 0;
            $validation = $this->bulkService->validateRow($row, $validationRules, $agencyId);

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

        // Create posts
        $createResults = $this->bulkService->createPosts($agencyId, $validatedRows);

        // Create import history
        $bulkSchedule = BulkSchedule::create([
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'filename' => $file->getClientOriginalName(),
            'total_rows' => count($parseResult['rows']),
            'successful_rows' => count($createResults['success']),
            'failed_rows' => count($createResults['failed']) + (count($parseResult['rows']) - count($validatedRows)),
            'processed_rows' => count($parseResult['rows']),
            'row_results' => $rowResults,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // Clean up
        Log::info('Bulk upload processed', ['bulk_id' => $bulkSchedule->id, 'total' => count($parseResult['rows']), 'success' => count($createResults['success'])]);
        Storage::disk('local')->delete($path);

        return redirect()->route('social.bulk.show', $bulkSchedule)
            ->with('success', 'Bulk upload completed successfully!');
    }
}
