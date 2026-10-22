<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name');
            $table->string('agent_category');
            $table->string('metric_type');
            $table->decimal('metric_value', 10, 4);
            $table->json('parameters_used')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['agent_name', 'metric_type']);
            $table->index(['agent_name', 'recorded_at']);
            $table->index(['agent_category']);
        });

        Schema::create('agent_learning_reports', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name');
            $table->string('improvement_type');
            $table->text('description');
            $table->json('changes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['agent_name']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_learning_reports');
        Schema::dropIfExists('agent_performance_logs');
    }
};
