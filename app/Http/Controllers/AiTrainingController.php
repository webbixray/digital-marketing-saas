<?php

namespace App\Http\Controllers;

use App\Models\AiModelVersion;
use App\Models\AiTrainingDataset;
use App\Models\AiTrainingJob;
use App\Services\AI\Training\DatasetService;
use App\Services\AI\Training\ModelEvaluationService;
use App\Services\AI\Training\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiTrainingController extends Controller
{
    public function __construct(
        private readonly TrainingService $trainingService,
        private readonly DatasetService $datasetService,
        private readonly ModelEvaluationService $evaluationService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * List model versions for the agency.
     */
    public function index(Request $request): View
    {
        $agency = $request->user()->agency;
        $modelVersions = $this->trainingService->getModelVersions($agency->id);

        $activeModel = $modelVersions->firstWhere('is_active', true);
        $latestJobs = AiTrainingJob::byAgency($agency->id)
            ->running()
            ->get();

        return view('ai-training.index', [
            'modelVersions' => $modelVersions,
            'activeModel' => $activeModel,
            'latestJobs' => $latestJobs,
        ]);
    }

    /**
     * Show form to create a new training job.
     */
    public function create(Request $request): View
    {
        $agency = $request->user()->agency;
        $datasets = $this->datasetService->getDatasetsForAgency($agency->id)
            ->where('status', 'ready');

        return view('ai-training.create', [
            'datasets' => $datasets,
        ]);
    }

    /**
     * Store a new training job.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'model_name' => 'required|string|max:255',
            'dataset_id' => 'required|integer|exists:ai_training_datasets,id',
            'base_model' => 'nullable|string|max:100',
            'epochs' => 'nullable|integer|min:1|max:100',
            'learning_rate' => 'nullable|numeric|min:0|max:1',
            'batch_size' => 'nullable|integer|min:1|max:256',
        ]);

        try {
            $hyperparameters = array_filter([
                'base_model' => $validated['base_model'] ?? null,
                'epochs' => $validated['epochs'] ?? null,
                'learning_rate' => isset($validated['learning_rate']) ? (float) $validated['learning_rate'] : null,
                'batch_size' => $validated['batch_size'] ?? null,
            ]);

            $job = $this->trainingService->startTraining(
                agencyId: $agency->id,
                modelName: $validated['model_name'],
                datasetId: (int) $validated['dataset_id'],
                hyperparameters: $hyperparameters,
            );

            return redirect()
                ->route('ai-training.show', ['version' => $job->model_version_id])
                ->with('success', 'Training job queued successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to start training: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show model version details.
     */
    public function show(Request $request, string $version): View
    {
        $agency = $request->user()->agency;

        $modelVersion = AiModelVersion::where('id', $version)
            ->where('agency_id', $agency->id)
            ->with(['trainingJobs.dataset'])
            ->firstOrFail();

        $evaluationResult = null;
        if ($modelVersion->status === 'ready' && $modelVersion->trainingJobs->isNotEmpty()) {
            $latestJob = $modelVersion->trainingJobs->first();
            $testDatasets = $this->datasetService->getDatasetsForAgency($agency->id)
                ->where('status', 'ready')
                ->where('id', '!=', $latestJob->dataset_id)
                ->first();

            if ($testDatasets) {
                try {
                    $evaluationResult = $this->evaluationService->evaluateModel(
                        $modelVersion->id,
                        $testDatasets->id
                    );
                } catch (\Exception $e) {
                    // Evaluation failed silently
                }
            }
        }

        return view('ai-training.show', [
            'modelVersion' => $modelVersion,
            'evaluationResult' => $evaluationResult,
        ]);
    }

    /**
     * List datasets for the agency.
     */
    public function datasets(Request $request): View
    {
        $agency = $request->user()->agency;
        $datasets = $this->datasetService->getDatasetsForAgency($agency->id);

        return view('ai-training.datasets', [
            'datasets' => $datasets,
        ]);
    }

    /**
     * Upload a training dataset.
     */
    public function uploadDataset(Request $request): \Illuminate\Http\RedirectResponse
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'dataset_file' => 'required|file|mimes:csv,json,jsonl,xlsx|max:102400',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $this->datasetService->uploadDataset(
                file: $request->file('dataset_file'),
                agencyId: $agency->id,
                name: $validated['name'],
                description: $validated['description'] ?? null,
            );

            return redirect()
                ->route('ai-training.datasets')
                ->with('success', 'Dataset uploaded successfully.');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Upload failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * List training jobs for the agency.
     */
    public function jobs(Request $request): View
    {
        $agency = $request->user()->agency;

        $jobs = AiTrainingJob::byAgency($agency->id)
            ->with(['modelVersion', 'dataset'])
            ->latest()
            ->paginate(20);

        return view('ai-training.jobs', [
            'jobs' => $jobs,
        ]);
    }

    /**
     * Show evaluation results for a model version.
     */
    public function evaluate(Request $request, int $version): View|JsonResponse
    {
        $agency = $request->user()->agency;

        $modelVersion = AiModelVersion::where('id', $version)
            ->where('agency_id', $agency->id)
            ->where('status', 'ready')
            ->firstOrFail();

        $datasets = $this->datasetService->getDatasetsForAgency($agency->id)
            ->where('status', 'ready');

        $evaluationResults = [];

        foreach ($datasets as $dataset) {
            try {
                $result = $this->evaluationService->evaluateModel($modelVersion->id, $dataset->id);
                $evaluationResults[] = $result;
            } catch (\Exception $e) {
                Log::warning("Evaluation failed for model #{$version} vs dataset #{$dataset->id}: {$e->getMessage()}");
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'model' => $modelVersion,
                'evaluations' => $evaluationResults,
            ]);
        }

        return view('ai-training.evaluate', [
            'modelVersion' => $modelVersion,
            'evaluationResults' => $evaluationResults,
        ]);
    }

    /**
     * Activate a model version.
     */
    public function activate(Request $request, int $version): \Illuminate\Http\RedirectResponse
    {
        $agency = $request->user()->agency;

        $modelVersion = AiModelVersion::where('id', $version)
            ->where('agency_id', $agency->id)
            ->firstOrFail();

        try {
            $this->trainingService->activateModel($modelVersion->id);

            return redirect()
                ->route('ai-training.index')
                ->with('success', 'Model activated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Activation failed: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a training job.
     */
    public function cancelJob(Request $request, int $job): \Illuminate\Http\RedirectResponse
    {
        $agency = $request->user()->agency;

        $trainingJob = AiTrainingJob::where('id', $job)
            ->where('agency_id', $agency->id)
            ->firstOrFail();

        try {
            $this->trainingService->cancelTraining($trainingJob->id);

            return redirect()
                ->route('ai-training.jobs')
                ->with('success', 'Training job cancelled.');
        } catch (\Exception $e) {
            return back()->with('error', 'Cancel failed: ' . $e->getMessage());
        }
    }

    /**
     * Get training status (AJAX).
     */
    public function jobStatus(Request $request, int $job): JsonResponse
    {
        $agency = $request->user()->agency;

        $trainingJob = AiTrainingJob::where('id', $job)
            ->where('agency_id', $agency->id)
            ->firstOrFail();

        $status = $this->trainingService->getTrainingStatus($trainingJob->id);

        return response()->json($status);
    }
}
