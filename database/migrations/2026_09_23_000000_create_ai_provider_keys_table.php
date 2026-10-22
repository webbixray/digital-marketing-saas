<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->string('provider_name');
            $table->text('api_key');
            $table->string('api_base_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('priority')->default(5);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['agency_id', 'provider_name']);
            $table->index(['agency_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_keys');
    }
};
