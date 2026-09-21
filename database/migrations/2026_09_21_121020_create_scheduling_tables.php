<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('optimal_posting_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->string('platform', 50);
            $table->unsignedTinyInteger('day_of_week'); // 0-6
            $table->unsignedTinyInteger('hour'); // 0-23
            $table->decimal('engagement_score', 5, 2);
            $table->unsignedInteger('sample_size')->default(0);
            $table->timestamps();

            $table->unique(['agency_id', 'platform', 'day_of_week', 'hour']);
        });

        Schema::create('bulk_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('file_type', 10); // csv, xlsx
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Add scheduling columns to social_posts
        Schema::table('social_posts', function (Blueprint $table) {
            $table->boolean('is_bulk_upload')->default(false)->after('is_recurring');
            $table->foreignId('bulk_upload_id')->nullable()->after('is_bulk_upload')->constrained()->onDelete('set null');
            $table->timestamp('optimal_scheduled_at')->nullable()->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn(['is_bulk_upload', 'bulk_upload_id', 'optimal_scheduled_at']);
        });
        Schema::dropIfExists('bulk_uploads');
        Schema::dropIfExists('optimal_posting_times');
    }
};
