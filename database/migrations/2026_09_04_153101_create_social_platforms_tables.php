<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // facebook, instagram, twitter, linkedin, tiktok, pinterest
            $table->string('display_name');
            $table->string('base_url')->nullable();
            $table->json('scopes')->nullable();
            $table->string('auth_url')->nullable();
            $table->string('token_url')->nullable();
            $table->string('api_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('platform')->index(); // facebook, instagram, twitter, linkedin, tiktok, pinterest
            $table->string('platform_account_id')->nullable(); // ID from platform
            $table->string('platform_username')->nullable();
            $table->string('platform_display_name')->nullable();
            $table->string('platform_account_type')->nullable(); // page, user, channel
            $table->string('access_token')->nullable();
            $table->string('refresh_token')->nullable();
            $tokenExpiresAt = $table->timestamp('token_expires_at')->nullable();
            $table->string('token_type')->nullable();
            $table->integer('scope')->nullable();
            $table->string('scope_str')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
            $table->unique(['agency_id', 'platform', 'platform_account_id'], 'social_account_unique');

            $table->index(['platform', 'is_active']);
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('platform');
            $table->string('content')->nullable();
            $table->json('media')->nullable(); // array of media URLs / IDs
            $table->json('links')->nullable(); // array of link objects
            $table->json('hashtags')->nullable(); // array of hashtags
            $table->json('mentions')->nullable(); // array of @mentions
            $table->json('tags')->nullable(); // for linkedin / facebook page tags
            $table->string('status')->default('draft'); // draft, scheduled, in_queue, publishing, published, failed, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->json('platform_response')->nullable(); // raw response from platform
            $table->string('external_post_id')->nullable(); // ID from platform
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->integer('clicks_count')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0)->nullable();
            $table->json('metrics')->nullable(); // aggregated performance metrics
            $table->integer('quality_score')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['agency_id', 'platform', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['published_at']);
        });

        Schema::create('social_post_scheduled_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('actual_published_at')->nullable();
            $table->string('status')->default('pending'); // pending, processing, published, failed, cancelled
            $table->string('error_message')->nullable();
            $table->integer('attempt_number')->default(0);
            $table->timestamps();
        });

        Schema::create('social_platform_cache', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_platform_cache');
        Schema::dropIfExists('social_post_scheduled_logs');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('platforms');
    }
};
