<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('search_history')) {
            Schema::create('search_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->string('query');
                $table->string('type', 50)->default('all');
                $table->unsignedInteger('results_count')->default(0);
                $table->json('clicked_result')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['user_id', 'created_at']);
                $table->index(['agency_id', 'created_at']);
                $table->index('type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('search_history');
    }
};
