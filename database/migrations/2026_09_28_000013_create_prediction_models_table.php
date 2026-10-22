<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prediction_models')) {
            Schema::create('prediction_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->string('name', 255);
                $table->enum('type', ['churn', 'revenue', 'engagement', 'optimal_time']);
                $table->string('model_version', 50)->default('1.0');
                $table->decimal('accuracy', 5, 4)->default(0);
                $table->json('features')->nullable();
                $table->json('hyperparameters')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('trained_at')->nullable();
                $table->timestamp('last_prediction_at')->nullable();
                $table->timestamps();

                $table->index(['agency_id', 'type']);
                $table->index(['agency_id', 'is_active']);
                $table->index('trained_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_models');
    }
};
