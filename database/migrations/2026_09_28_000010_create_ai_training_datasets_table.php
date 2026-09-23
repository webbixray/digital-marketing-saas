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
        Schema::create('ai_training_datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('file_path', 500);
            $table->string('file_hash', 64)->nullable();
            $table->integer('row_count')->default(0);
            $table->integer('column_count')->default(0);
            $table->enum('status', ['uploading', 'processing', 'ready', 'error'])->default('uploading');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_training_datasets');
    }
};
