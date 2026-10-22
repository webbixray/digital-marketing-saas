<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prediction_results')) {
            Schema::create('prediction_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->foreignId('prediction_model_id')->constrained('prediction_models')->cascadeOnDelete();
                $table->string('target_type', 100);
                $table->unsignedBigInteger('target_id');
                $table->decimal('predicted_value', 10, 4)->default(0);
                $table->decimal('confidence', 5, 4)->default(0);
                $table->json('features_used')->nullable();
                $table->decimal('actual_value', 10, 4)->nullable();
                $table->decimal('error', 10, 4)->nullable();
                $table->timestamps();

                $table->index(['agency_id', 'created_at']);
                $table->index(['prediction_model_id', 'created_at']);
                $table->index(['target_type', 'target_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_results');
    }
};
