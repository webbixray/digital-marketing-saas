<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_marketplace_categories')) {
            Schema::create('agent_marketplace_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 120)->unique();
                $table->text('description')->nullable();
                $table->string('icon', 100)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('agent_count')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (! Schema::hasTable('agent_marketplace_items')) {
            Schema::create('agent_marketplace_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
                $table->string('name', 200);
                $table->string('slug', 220)->unique();
                $table->text('description')->nullable();
                $table->foreignId('category_id')->nullable()->constrained('agent_marketplace_categories')->nullOnDelete();
                $table->json('tags')->nullable();
                $table->string('icon', 100)->nullable();
                $table->json('screenshots')->nullable();
                $table->string('demo_url', 500)->nullable();
                $table->enum('pricing_type', ['free', 'paid', 'pricing_tiers'])->default('free');
                $table->json('pricing_config')->nullable();
                $table->json('features')->nullable();
                $table->json('requirements')->nullable();
                $table->unsignedInteger('install_count')->default(0);
                $table->decimal('rating_avg', 3, 2)->default(0);
                $table->unsignedInteger('rating_count')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_approved')->default(false);
                $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'is_approved']);
                $table->index(['category_id', 'status']);
                $table->index(['is_featured', 'status']);
                $table->index(['pricing_type', 'status']);
                $table->index(['agency_id']);
                $table->index(['published_at']);
            });
        }

        if (! Schema::hasTable('agent_marketplace_reviews')) {
            Schema::create('agent_marketplace_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('item_id')->constrained('agent_marketplace_items')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('agency_id')->nullable()->constrained('agencies')->nullOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->string('title', 200);
                $table->text('body');
                $table->boolean('is_verified_purchase')->default(false);
                $table->unsignedInteger('helpful_count')->default(0);
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->timestamps();

                $table->index(['item_id', 'status']);
                $table->index(['item_id', 'created_at']);
                $table->index(['user_id']);
                $table->index(['status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_marketplace_reviews');
        Schema::dropIfExists('agent_marketplace_items');
        Schema::dropIfExists('agent_marketplace_categories');
    }
};
