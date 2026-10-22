<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_workflow_executions', function (Blueprint $table) {
            $table->id();
            $table->string('execution_id')->unique();
            $table->string('workflow_name');
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, running, success, failed, cancelled
            $table->json('input_data')->nullable();
            $table->json('output_data')->nullable();
            $table->unsignedInteger('steps_total')->default(0);
            $table->unsignedInteger('steps_completed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['workflow_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_workflow_executions');
    }
};
