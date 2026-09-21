<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name', 100);
            $table->string('task_id', 100);
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('feedback')->nullable();
            $table->json('expected_output')->nullable();
            $table->json('actual_output')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['agent_name', 'rating']);
            $table->index(['agent_name', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_feedback');
    }
};
