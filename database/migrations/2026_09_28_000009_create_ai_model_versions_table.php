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
        Schema::create('ai_model_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('base_model', 100)->default('custom');
            $table->string('version', 50)->default('1.0.0');
            $table->enum('status', ['draft', 'training', 'ready', 'failed'])->default('draft');
            $table->string('training_data_hash', 64)->nullable();
            $table->json('metrics')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('trained_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'is_active']);
            $table->index('base_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_model_versions');
    }
};
