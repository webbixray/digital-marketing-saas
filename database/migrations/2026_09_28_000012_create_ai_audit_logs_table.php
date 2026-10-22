<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_audit_logs')) {
            Schema::create('ai_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('action', 100)->comment('generate, translate, analyze, etc');
                $table->string('model_used', 255)->nullable();
                $table->string('input_hash', 64)->nullable();
                $table->string('output_hash', 64)->nullable();
                $table->decimal('bias_score', 5, 2)->default(0);
                $table->decimal('toxicity_score', 5, 2)->default(0);
                $table->enum('compliance_status', ['pass', 'fail', 'warn'])->default('pass');
                $table->text('flagged_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['agency_id', 'created_at']);
                $table->index(['agency_id', 'compliance_status']);
                $table->index(['agency_id', 'action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_audit_logs');
    }
};
