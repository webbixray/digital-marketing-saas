<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events')->nullable();
            $table->string('content_type')->default('application/json');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_calls')->default(0);
            $table->unsignedInteger('failed_calls')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'is_active']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->cascadeOnDelete();
            $table->string('event');
            $table->integer('status_code')->nullable();
            $table->text('payload')->nullable();
            $table->text('response')->nullable();
            $table->string('error_message')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->boolean('is_success')->default(false);
            $table->timestamps();

            $table->index(['webhook_id', 'event']);
            $table->index(['webhook_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
    }
};
