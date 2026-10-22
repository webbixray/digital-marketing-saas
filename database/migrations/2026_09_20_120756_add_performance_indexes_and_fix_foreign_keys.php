<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // workflow_webhook_logs: status index (TRULY MISSING)
        Schema::table('workflow_webhook_logs', function (Blueprint $table) {
            $table->index('status');
        });

        // social_posts: approved_by FK (TRULY MISSING)
        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // social_posts: client_id FK (TRULY MISSING)
        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();
        });

        // content_insights: social_post_id FK (TRULY MISSING)
        Schema::table('content_insights', function (Blueprint $table) {
            $table->foreign('social_post_id')
                ->references('id')
                ->on('social_posts')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_insights', function (Blueprint $table) {
            $table->dropForeign(['social_post_id']);
        });

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['approved_by']);
        });

        Schema::table('workflow_webhook_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
