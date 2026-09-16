<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agencies
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('active');
            $table->string('subscription_plan')->default('free');
            $table->timestamp('subscription_start')->nullable();
            $table->timestamp('subscription_end')->nullable();
            $table->string('subscription_status')->default('active');
            $table->string('subscription_payment_method')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('subscription_id')->nullable();
            $table->unsignedInteger('posts_count')->default(0);
            $table->unsignedInteger('ai_requests_count')->default(0);
            $table->unsignedInteger('ai_generations_count')->default(0);
            $table->unsignedInteger('campaigns_count')->default(0);
            $table->unsignedInteger('clients_count')->default(0);
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('social_accounts_count')->default(0);
            $table->unsignedInteger('landing_pages_count')->default(0);
            $table->unsignedInteger('forms_count')->default(0);
            $table->json('custom_settings')->nullable();
            $table->json('branding')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'subscription_plan']);
            $table->index(['subscription_status']);
        });

        // Users
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'agency_id')) {
                $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('member');
            }
            if (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
            if (! Schema::hasColumn('users', 'title')) {
                $table->string('title')->nullable();
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('users', 'last_active_at')) {
                $table->timestamp('last_active_at')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (! Schema::hasColumn('users', 'is_approved')) {
                $table->boolean('is_approved')->default(false);
            }
            if (! Schema::hasColumn('users', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        // Plans
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->decimal('price', 8, 2)->default(0);
            $table->string('interval')->default('month');
            $table->unsignedInteger('users')->nullable()->default(1);
            $table->unsignedInteger('social_accounts')->default(1);
            $table->unsignedInteger('posts_per_month')->default(30);
            $table->unsignedInteger('campaigns')->default(1);
            $table->unsignedInteger('clients')->default(0);
            $table->unsignedInteger('ai_requests_per_month')->default(50);
            $table->unsignedInteger('ai_generations_per_month')->default(20);
            $table->unsignedInteger('landing_pages')->default(0);
            $table->unsignedInteger('forms')->default(0);
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Plan features pivot
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature_code')->index();
            $table->string('feature_name');
            $table->timestamps();
        });

        // Features
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Agency Features
        Schema::create('agency_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['agency_id', 'feature_id']);
        });

        // Clients
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('industry')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_contact_at')->nullable();
            $table->unsignedInteger('posts_count')->default(0);
            $table->unsignedInteger('campaigns_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['email']);
        });

        // Campaigns
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('general');
            $table->string('status')->default('draft');
            $table->text('description')->nullable();
            $table->string('objective')->nullable();
            $table->string('target_audience')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('tags')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('posts_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->decimal('estimated_roi', 8, 2)->nullable();
            $table->integer('engagement_rate')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index(['client_id']);
            $table->index(['start_date', 'end_date']);
        });

        // Campaign-Post pivot
        Schema::create('campaign_post', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('social_post_id')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'social_post_id']);
        });

        // Content Assets (Library)
        Schema::create('content_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('text');
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_public')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'type']);
            $table->index(['agency_id', 'status']);
        });

        // Content Templates
        Schema::create('content_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('platform')->nullable();
            $table->string('type')->default('post');
            $table->text('template_content')->nullable();
            $table->json('variables')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'platform']);
            $table->index(['agency_id', 'status']);
        });

        // Content Insights
        Schema::create('content_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->nullable();
            $table->date('insight_date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('reach')->default(0);
            $table->unsignedInteger('engagement')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('saves')->default(0);
            $table->unsignedInteger('shares')->default(0);
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->json('demographics')->nullable();
            $table->json('top_locations')->nullable();
            $table->json('top_devices')->nullable();
            $table->timestamps();

            $table->unique(['social_post_id', 'insight_date']);
            $table->index(['insight_date']);
            $table->index(['social_post_id']);
        });

        // AI Content Logs
        Schema::create('ai_content_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('action')->nullable();
            $table->string('content_type')->nullable();
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->unsignedInteger('total_tokens')->default(0);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->decimal('cost_usd', 8, 4)->default(0);
            $table->string('status')->default('success');
            $table->string('error_message')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['provider']);
            $table->index(['created_at']);
        });

        // Agency Settings
        Schema::create('agency_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('description');
            $table->string('subject_type')->nullable();
            $table->unsignedInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['agency_id', 'created_at']);
            $table->index(['user_id']);
            $table->index(['action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('agency_settings');
        Schema::dropIfExists('ai_content_logs');
        Schema::dropIfExists('content_insights');
        Schema::dropIfExists('content_templates');
        Schema::dropIfExists('content_assets');
        Schema::dropIfExists('campaign_post');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('agency_features');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('features');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('agencies');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropColumn(['agency_id', 'role', 'avatar', 'title', 'phone', 'last_active_at', 'is_active', 'is_approved', 'notes']);
        });
    }
};
