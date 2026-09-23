<?php

namespace App\Services\AI\Training;

use App\Jobs\TrainAiModelJob;
use App\Models\Agency;
use App\Models\AiModelVersion;
use App\Models\AiTrainingDataset;
use App\Models\AiTrainingJob;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TrainingService
{
    /**
     * Start a new training job.
     */
    public function startTraining(int $agencyId, string $modelName, int $datasetId, array $hyperparameters = []): AiTrainingJob
    {
        $dataset = AiTrainingDataset::where('id', $datasetId)
            ->where('agency_id', $agencyId)
            ->where('status', 'ready')
            ->firstOrFail();

        // Create model version
        $version = $this->generateNextVersionNumber($agencyId);

        $modelVersion = AiModelVersion::create([
            'agency_id' => $agencyId,
            'name' => $modelName,
            'base_model' => $hyperparameters['base_model'] ?? 'custom',
            'version' => $version,
            'status' => 'draft',
            'training_data_hash' => $dataset->file_hash,
            'is_active' => false,
        ]);

        // Create training job
        $job = AiTrainingJob::create([
            'agency_id' => $agencyId,
            'model_version_id' => $modelVersion->id,
            'dataset_id' => $dataset->id,
            'status' => 'queued',
            'progress' => 0,
            'hyperparameters' => $this->buildHyperparameters($hyperparameters),
            'metrics' => null,
        ]);

        // Dispatch queue job
        TrainAiModelJob::dispatch($job->id);

        Log::info("Training job #{$job->id} queued for model version #{$modelVersion->id}, agency #{$agencyId}");

        return $job->fresh();
    }

    /**
     * Execute model training (called from queue job).
     */
    public function trainModel(int $jobId): AiTrainingJob
    {
        $job = AiTrainingJob::with(['modelVersion', 'dataset'])->findOrFail($jobId);

        if (! in_array($job->status, ['queued', 'running'], true)) {
            return $job;
        }

        $job->update([
            'status' => 'running',
            'progress' => 0,
            'started_at' => now(),
        ]);

        try {
            $modelVersion = $job->modelVersion;
            $modelVersion->update(['status' => 'training']);

            $dataset = $job->dataset;
            $hyperparameters = $job->hyperparameters ?? [];

            // Simulate training progression
            $this->simulateTrainingProgress($job);

            // Calculate final metrics based on dataset and hyperparameters
            $metrics = $this->calculateTrainingMetrics($dataset, $hyperparameters);

            $modelFilePath = $this->storeTrainedModel($modelVersion);

            $modelVersion->update([
                'status' => 'ready',
                'metrics' => $metrics,
                'file_path' => $modelFilePath,
                'trained_at' => now(),
            ]);

            $job->update([
                'status' => 'completed',
                'progress' => 100,
                'metrics' => $metrics,
                'completed_at' => now(),
            ]);

            Log::info("Training job #{$jobId} completed successfully for model version #{$modelVersion->id}");
        } catch (\Exception $e) {
            Log::error("Training job #{$jobId} failed: {$e->getMessage()}");

            $job->modelVersion->update(['status' => 'failed']);

            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $job->fresh();
    }

    /**
     * Get training status for a job.
     */
    public function getTrainingStatus(int $jobId): array
    {
        $job = AiTrainingJob::with(['modelVersion', 'dataset'])->findOrFail($jobId);

        return [
            'job_id' => $job->id,
            'status' => $job->status,
            'progress' => $job->progress,
            'model_name' => $job->modelVersion->name,
            'model_version' => $job->modelVersion->version,
            'model_status' => $job->modelVersion->status,
            'dataset_name' => $job->dataset->name,
            'metrics' => $job->metrics,
            'error_message' => $job->error_message,
            'started_at' => $job->started_at?->toDateTimeString(),
            'completed_at' => $job->completed_at?->toDateTimeString(),
            'created_at' => $job->created_at->toDateTimeString(),
            'hyperparameters' => $job->hyperparameters,
        ];
    }

    /**
     * Cancel a running or queued training job.
     */
    public function cancelTraining(int $jobId): AiTrainingJob
    {
        $job = AiTrainingJob::findOrFail($jobId);

        if (! in_array($job->status, ['queued', 'running'], true)) {
            throw new \RuntimeException("Cannot cancel job with status '{$job->status}'.");
        }

        $job->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        $job->modelVersion->update(['status' => 'failed']);

        Log::info("Training job #{$jobId} cancelled.");

        return $job->fresh();
    }

    /**
     * Get all model versions for an agency.
     *
     * @return Collection<int, AiModelVersion>
     */
    public function getModelVersions(int $agencyId): Collection
    {
        return AiModelVersion::byAgency($agencyId)
            ->latest()
            ->get();
    }

    /**
     * Activate a model version (deactivates others for the agency).
     */
    public function activateModel(int $versionId): AiModelVersion
    {
        $model = AiModelVersion::findOrFail($versionId);

        if ($model->status !== 'ready') {
            throw new \RuntimeException('Only ready models can be activated.');
        }

        // Deactivate all other models for this agency
        AiModelVersion::where('agency_id', $model->agency_id)
            ->where('id', '!=', $model->id)
            ->update(['is_active' => false]);

        $model->update(['is_active' => true]);

        Log::info("Model version #{$versionId} activated for agency #{$model->agency_id}");

        return $model->fresh();
    }

    /**
     * Compare multiple model versions.
     */
    public function compareModels(array $versionIds): array
    {
        $models = AiModelVersion::whereIn('id', $versionIds)
            ->where('status', 'ready')
            ->get();

        $comparison = [];

        foreach ($models as $model) {
            $comparison[] = [
                'id' => $model->id,
                'name' => $model->name,
                'version' => $model->version,
                'base_model' => $model->base_model,
                'metrics' => $model->metrics,
                'is_active' => $model->is_active,
                'trained_at' => $model->trained_at?->toDateTimeString(),
            ];
        }

        return $comparison;
    }

    /**
     * Generate the next version number for an agency's models.
     */
    private function generateNextVersionNumber(int $agencyId): string
    {
        $latestVersion = AiModelVersion::byAgency($agencyId)
            ->latest()
            ->first();

        if (! $latestVersion) {
            return '1.0.0';
        }

        $parts = explode('.', $latestVersion->version);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);
        $patch = (int) ($parts[2] ?? 0);

        $patch++;
        if ($patch >= 100) {
            $patch = 0;
            $minor++;
        }
        if ($minor >= 100) {
            $minor = 0;
            $major++;
        }

        return "{$major}.{$minor}.{$patch}";
    }

    /**
     * Build default hyperparameters merged with user-provided ones.
     */
    private function buildHyperparameters(array $userParams): array
    {
        $defaults = [
            'base_model' => 'custom',
            'epochs' => 3,
            'learning_rate' => 0.0001,
            'batch_size' => 16,
            'warmup_steps' => 100,
            'weight_decay' => 0.01,
            'max_seq_length' => 512,
            'optimizer' => 'adamw',
            'scheduler' => 'linear',
        ];

        return array_merge($defaults, $userParams);
    }

    /**
     * Simulate training progress updates.
     */
    private function simulateTrainingProgress(AiTrainingJob $job): void
    {
        $steps = [10, 25, 40, 55, 70, 85, 95];

        foreach ($steps as $progress) {
            $job->update(['progress' => $progress]);
            // In production, this would be actual training steps
            usleep(100000); // 100ms per step for simulation
        }
    }

    /**
     * Calculate training metrics based on dataset and hyperparameters.
     */
    private function calculateTrainingMetrics(AiTrainingDataset $dataset, array $hyperparameters): array
    {
        $rowCount = $dataset->row_count;
        $epochs = $hyperparameters['epochs'] ?? 3;
        $learningRate = $hyperparameters['learning_rate'] ?? 0.0001;

        // Simulated metrics based on dataset size and hyperparameters
        $baseAccuracy = min(0.95, 0.7 + ($rowCount / 10000) * 0.2);
        $lrBonus = $learningRate <= 0.0001 ? 0.02 : 0.01;
        $epochBonus = min(0.05, $epochs * 0.01);

        $finalAccuracy = min(0.98, $baseAccuracy + $lrBonus + $epochBonus);
        $finalLoss = max(0.02, 0.5 - ($finalAccuracy - 0.7) * 1.2);

        return [
            'accuracy' => round($finalAccuracy, 4),
            'loss' => round($finalLoss, 4),
            'epochs_completed' => $epochs,
            'training_samples' => $rowCount,
            'validation_split' => 0.2,
            'final_learning_rate' => $learningRate,
            'training_duration_seconds' => $epochs * max(10, (int) ($rowCount / 100)),
            'perplexity' => round(max(1.0, 10 * $finalLoss), 2),
            'f1_score' => round($finalAccuracy * 0.95, 4),
        ];
    }

    /**
     * Store the trained model file path.
     */
    private function storeTrainedModel(AiModelVersion $modelVersion): string
    {
        $path = sprintf(
            'ai-training/models/agency_%d/%s_v%s.model',
            $modelVersion->agency_id,
            \Illuminate\Support\Str::slug($modelVersion->name),
            $modelVersion->version
        );

        // In production, this would save the actual model weights
        return $path;
    }
}
