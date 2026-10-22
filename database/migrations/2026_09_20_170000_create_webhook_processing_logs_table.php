<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 50)->index();
            $table->string('event_type', 100)->index();
            $table->string('webhook_id', 64)->unique()->comment('Unique webhook identifier for deduplication');
            $table->string('signature', 255)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->string('status', 20)->default('pending')->comment('pending, processing, completed, failed, dead_letter');
            $table->unsignedTinyInteger('attempt')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['platform', 'status']);
            $table->index(['status', 'attempt']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_processing_logs');
    }
};
