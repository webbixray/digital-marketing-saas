<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workflow templates table
        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->string('icon')->default('fa-project-diagram');
            $table->json('nodes');
            $table->json('connections');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_public']);
        });

        // Workflow versions table
        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('name');
            $table->string('trigger_type');
            $table->json('trigger_config');
            $table->json('actions');
            $table->json('conditions');
            $table->json('nodes')->nullable();
            $table->json('connections')->nullable();
            $table->text('change_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workflow_id', 'version_number']);
        });

        // Add webhook support to workflows
        Schema::table('workflows', function (Blueprint $table) {
            $table->string('webhook_secret')->nullable()->after('is_system');
            $table->string('webhook_url')->nullable()->after('webhook_secret');
        });

        // Add webhook logs table
        Schema::create('workflow_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->string('ip_address')->nullable();
            $table->string('status')->default('received');
            $table->text('response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_webhook_logs');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflow_templates');

        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn(['webhook_secret', 'webhook_url']);
        });
    }
};
