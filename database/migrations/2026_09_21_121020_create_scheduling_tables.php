<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('optimal_posting_times')) {
            Schema::create('optimal_posting_times', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained()->onDelete('cascade');
                $table->string('platform', 50);
                $table->unsignedTinyInteger('day_of_week');
                $table->unsignedTinyInteger('hour');
                $table->decimal('engagement_score', 5, 2);
                $table->unsignedInteger('sample_size')->default(0);
                $table->timestamps();

                $table->unique(['agency_id', 'platform', 'day_of_week', 'hour']);
            });
        }

        if (! Schema::hasTable('bulk_uploads')) {
            Schema::create('bulk_uploads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('original_filename');
                $table->string('stored_path');
                $table->string('file_type', 10);
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('processed_rows')->default(0);
                $table->unsignedInteger('success_count')->default(0);
                $table->unsignedInteger('error_count')->default(0);
                $table->json('errors')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        // Add scheduling columns to social_posts
        if (! Schema::hasColumn('social_posts', 'is_bulk_upload')) {
            Schema::table('social_posts', function (Blueprint $table) {
                $table->boolean('is_bulk_upload')->default(false);
            });
        }
        if (! Schema::hasColumn('social_posts', 'bulk_upload_id')) {
            Schema::table('social_posts', function (Blueprint $table) {
                $table->foreignId('bulk_upload_id')->nullable()->constrained()->onDelete('set null');
            });
        }
        if (! Schema::hasColumn('social_posts', 'optimal_scheduled_at')) {
            Schema::table('social_posts', function (Blueprint $table) {
                $table->timestamp('optimal_scheduled_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('social_posts', 'is_bulk_upload') || Schema::hasColumn('social_posts', 'bulk_upload_id') || Schema::hasColumn('social_posts', 'optimal_scheduled_at')) {
            Schema::table('social_posts', function (Blueprint $table) {
                if (Schema::hasColumn('social_posts', 'is_bulk_upload')) $table->dropColumn('is_bulk_upload');
                if (Schema::hasColumn('social_posts', 'bulk_upload_id')) $table->dropColumn('bulk_upload_id');
                if (Schema::hasColumn('social_posts', 'optimal_scheduled_at')) $table->dropColumn('optimal_scheduled_at');
            });
        }
        Schema::dropIfExists('bulk_uploads');
        Schema::dropIfExists('optimal_posting_times');
    }
};
