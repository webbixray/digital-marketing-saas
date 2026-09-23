<?php

namespace Tests\Feature\AI;

use App\Models\Agency;
use App\Models\AiModelVersion;
use App\Models\AiTrainingDataset;
use App\Models\AiTrainingJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiTrainingTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_ai_training_index_requires_auth(): void
    {
        $response = $this->get(route('ai-training.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_ai_training_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->user)->get(route('ai-training.index'));
        $response->assertOk();
        $response->assertViewIs('ai-training.index');
        $response->assertViewHas('modelVersions');
    }

    public function test_ai_training_create_shows_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('ai-training.create'));
        $response->assertOk();
        $response->assertViewIs('ai-training.create');
    }

    public function test_ai_training_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('ai-training.store'), []);
        $response->assertSessionHasErrors(['model_name', 'dataset_id']);
    }

    public function test_ai_training_store_creates_model_version_and_job(): void
    {
        Storage::fake('local');

        $dataset = AiTrainingDataset::create([
            'agency_id' => $this->agency->id,
            'name' => 'Test Dataset',
            'description' => 'Test data',
            'file_path' => 'ai-training/datasets/test.csv',
            'file_hash' => hash('sha256', 'test'),
            'row_count' => 100,
            'column_count' => 2,
            'status' => 'ready',
        ]);

        $response = $this->actingAs($this->user)->post(route('ai-training.store'), [
            'model_name' => 'Test Model',
            'dataset_id' => $dataset->id,
            'base_model' => 'llama-3',
            'epochs' => 3,
            'learning_rate' => 0.0001,
            'batch_size' => 16,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ai_model_versions', [
            'agency_id' => $this->agency->id,
            'name' => 'Test Model',
            'base_model' => 'llama-3',
        ]);

        $modelVersion = AiModelVersion::where('name', 'Test Model')->first();
        $this->assertContains($modelVersion->status, ['draft', 'training', 'ready']);

        $this->assertDatabaseHas('ai_training_jobs', [
            'agency_id' => $this->agency->id,
            'dataset_id' => $dataset->id,
        ]);

        $job = AiTrainingJob::where('dataset_id', $dataset->id)->first();
        $this->assertContains($job->status, ['queued', 'running', 'completed']);
    }

    public function test_ai_training_show_displays_model_details(): void
    {
        $modelVersion = AiModelVersion::create([
            'agency_id' => $this->agency->id,
            'name' => 'Show Test Model',
            'base_model' => 'mistral',
            'version' => '1.0.0',
            'status' => 'ready',
            'metrics' => ['accuracy' => 0.92, 'loss' => 0.05, 'f1_score' => 0.91],
            'is_active' => false,
            'trained_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('ai-training.show', $modelVersion->id));
        $response->assertOk();
        $response->assertViewIs('ai-training.show');
        $response->assertViewHas('modelVersion');
    }

    public function test_ai_training_activate_sets_model_active(): void
    {
        $modelVersion = AiModelVersion::create([
            'agency_id' => $this->agency->id,
            'name' => 'Activate Test',
            'base_model' => 'custom',
            'version' => '1.0.0',
            'status' => 'ready',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->post(route('ai-training.activate', $modelVersion->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $modelVersion->refresh();
        $this->assertTrue($modelVersion->is_active);
    }

    public function test_ai_training_datasets_list(): void
    {
        AiTrainingDataset::create([
            'agency_id' => $this->agency->id,
            'name' => 'Dataset 1',
            'file_path' => 'ai-training/datasets/test.csv',
            'file_hash' => hash('sha256', 'test'),
            'row_count' => 50,
            'column_count' => 2,
            'status' => 'ready',
        ]);

        $response = $this->actingAs($this->user)->get(route('ai-training.datasets'));
        $response->assertOk();
        $response->assertViewIs('ai-training.datasets');
        $response->assertViewHas('datasets');
    }

    public function test_ai_training_upload_dataset_stores_file_and_creates_record(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('training_data.csv', 100, 'text/csv');

        $response = $this->actingAs($this->user)->post(route('ai-training.datasets.upload'), [
            'name' => 'Uploaded Dataset',
            'description' => 'Test upload',
            'dataset_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ai_training_datasets', [
            'agency_id' => $this->agency->id,
            'name' => 'Uploaded Dataset',
        ]);
    }

    public function test_ai_training_jobs_list(): void
    {
        $modelVersion = AiModelVersion::create([
            'agency_id' => $this->agency->id,
            'name' => 'Job Test Model',
            'base_model' => 'custom',
            'version' => '1.0.0',
            'status' => 'training',
        ]);

        $dataset = AiTrainingDataset::create([
            'agency_id' => $this->agency->id,
            'name' => 'Job Dataset',
            'file_path' => 'ai-training/datasets/test.csv',
            'status' => 'ready',
        ]);

        AiTrainingJob::create([
            'agency_id' => $this->agency->id,
            'model_version_id' => $modelVersion->id,
            'dataset_id' => $dataset->id,
            'status' => 'running',
            'progress' => 50,
        ]);

        $response = $this->actingAs($this->user)->get(route('ai-training.jobs'));
        $response->assertOk();
        $response->assertViewIs('ai-training.jobs');
        $response->assertViewHas('jobs');
    }

    public function test_ai_training_cancel_job_changes_status(): void
    {
        $modelVersion = AiModelVersion::create([
            'agency_id' => $this->agency->id,
            'name' => 'Cancel Test',
            'base_model' => 'custom',
            'version' => '1.0.0',
            'status' => 'draft',
        ]);

        $dataset = AiTrainingDataset::create([
            'agency_id' => $this->agency->id,
            'name' => 'Cancel Dataset',
            'file_path' => 'ai-training/datasets/test.csv',
            'status' => 'ready',
        ]);

        $job = AiTrainingJob::create([
            'agency_id' => $this->agency->id,
            'model_version_id' => $modelVersion->id,
            'dataset_id' => $dataset->id,
            'status' => 'queued',
            'progress' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('ai-training.jobs.cancel', $job->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $job->refresh();
        $this->assertEquals('cancelled', $job->status);
    }

    public function test_ai_training_evaluate_shows_results(): void
    {
        $modelVersion = AiModelVersion::create([
            'agency_id' => $this->agency->id,
            'name' => 'Eval Test',
            'base_model' => 'custom',
            'version' => '1.0.0',
            'status' => 'ready',
            'metrics' => ['accuracy' => 0.88, 'f1_score' => 0.87],
        ]);

        $dataset = AiTrainingDataset::create([
            'agency_id' => $this->agency->id,
            'name' => 'Eval Dataset',
            'file_path' => 'ai-training/datasets/eval.csv',
            'file_hash' => hash('sha256', 'eval'),
            'row_count' => 200,
            'column_count' => 2,
            'status' => 'ready',
        ]);

        $response = $this->actingAs($this->user)->get(route('ai-training.evaluate', $modelVersion->id));
        $response->assertOk();
        $response->assertViewIs('ai-training.evaluate');
        $response->assertViewHas('evaluationResults');
    }

    public function test_ai_training_cross_agency_isolation(): void
    {
        $otherAgency = Agency::factory()->create();
        $otherUser = User::factory()->create(['agency_id' => $otherAgency->id]);

        $otherModel = AiModelVersion::create([
            'agency_id' => $otherAgency->id,
            'name' => 'Other Model',
            'base_model' => 'custom',
            'version' => '1.0.0',
            'status' => 'ready',
        ]);

        $response = $this->actingAs($this->user)->get(route('ai-training.show', $otherModel->id));
        $response->assertNotFound();
    }

    public function test_ai_training_job_status_endpoint(): void
    {
        $modelVersion = AiModelVersion::create([
            'agency_id' => $this->agency->id,
            'name' => 'Status Test',
            'base_model' => 'custom',
            'version' => '1.0.0',
            'status' => 'training',
        ]);

        $dataset = AiTrainingDataset::create([
            'agency_id' => $this->agency->id,
            'name' => 'Status Dataset',
            'file_path' => 'ai-training/datasets/status.csv',
            'status' => 'ready',
        ]);

        $job = AiTrainingJob::create([
            'agency_id' => $this->agency->id,
            'model_version_id' => $modelVersion->id,
            'dataset_id' => $dataset->id,
            'status' => 'running',
            'progress' => 75,
        ]);

        $response = $this->actingAs($this->user)->get(route('ai-training.jobs.status', $job->id));
        $response->assertOk();
        $response->assertJson([
            'job_id' => $job->id,
            'status' => 'running',
            'progress' => 75,
        ]);
    }

    public function test_ai_training_only_ready_model_can_be_activated(): void
    {
        $draftModel = AiModelVersion::create([
            'agency_id' => $this->agency->id,
            'name' => 'Draft Model',
            'base_model' => 'custom',
            'version' => '1.0.0',
            'status' => 'draft',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->post(route('ai-training.activate', $draftModel->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $draftModel->refresh();
        $this->assertFalse($draftModel->is_active);
    }
}
