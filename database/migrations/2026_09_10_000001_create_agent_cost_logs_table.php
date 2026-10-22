<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_cost_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('agent_name');
            $table->string('task_type');
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->timestamp('executed_at')->useCurrent();
            $table->timestamps();

            $table->index(['agency_id', 'executed_at']);
            $table->index(['agency_id', 'agent_name']);
            $table->index(['agency_id', 'task_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_cost_logs');
    }
};
