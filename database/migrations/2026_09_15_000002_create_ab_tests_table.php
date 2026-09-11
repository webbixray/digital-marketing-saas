<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained('agencies')->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('draft'); // draft, running, paused, completed
            $table->string('type')->default('content'); // content, timing, hashtag, media
            $table->string('platform');
            $table->text('hypothesis')->nullable();
            $table->string('variant_a_content');
            $table->string('variant_b_content');
            $table->json('variant_a_media')->nullable();
            $table->json('variant_b_media')->nullable();
            $table->integer('variant_a_impressions')->default(0);
            $table->integer('variant_b_impressions')->default(0);
            $table->integer('variant_a_engagement')->default(0);
            $table->integer('variant_b_engagement')->default(0);
            $table->integer('variant_a_clicks')->default(0);
            $table->integer('variant_b_clicks')->default(0);
            $table->string('winner')->nullable(); // a, b, inconclusive
            $table->decimal('confidence', 5, 2)->default(0); // statistical confidence %
            $table->integer('sample_size')->default(100); // target sample size per variant
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['agency_id', 'status']);
            $table->index(['status', 'platform']);
        });

        Schema::create('ab_test_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ab_test_id')->constrained('ab_tests')->cascadeOnDelete();
            $table->string('variant'); // a or b
            $table->string('event'); // impression, engagement, click
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['ab_test_id', 'variant', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_test_logs');
        Schema::dropIfExists('ab_tests');
    }
};
