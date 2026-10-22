<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_approvals')) {
            Schema::create('client_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->foreignId('social_post_id')->nullable()->constrained('social_posts')->nullOnDelete();
                $table->string('status', 20)->default('pending');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['client_id', 'status'], 'client_approvals_client_status_index');
                $table->index(['status'], 'client_approvals_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('client_approvals');
    }
};
