<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->string('format')->default('pdf');
            $table->string('schedule')->default('once');
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['agency_id', 'type']);
            $table->index(['agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
