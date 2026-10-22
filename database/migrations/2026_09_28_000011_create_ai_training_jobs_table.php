<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_training_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('model_version_id')->constrained('ai_model_versions')->cascadeOnDelete();
            $table->foreignId('dataset_id')->constrained('ai_training_datasets')->cascadeOnDelete();
            $table->enum('status', ['queued', 'running', 'completed', 'failed', 'cancelled'])->default('queued');
            $table->tinyInteger('progress')->unsigned()->default(0);
            $table->json('hyperparameters')->nullable();
            $table->json('metrics')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['model_version_id', 'status']);
            $table->index('dataset_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_training_jobs');
    }
};
