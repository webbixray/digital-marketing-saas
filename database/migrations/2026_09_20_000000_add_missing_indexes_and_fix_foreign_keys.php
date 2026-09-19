<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing indexes for ai_credit_purchases
        Schema::table('ai_credit_purchases', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at']);
            $table->index('status');
        });

        // Add missing indexes for notifications (morphs already creates notifiable_type+notifiable_id index)
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('read_at');
        });

        // Add missing indexes for social_post_scheduled_logs
        Schema::table('social_post_scheduled_logs', function (Blueprint $table) {
            $table->index(['social_post_id', 'status']);
        });

        // Add missing indexes for custom_templates
        Schema::table('custom_templates', function (Blueprint $table) {
            $table->index('agency_id');
            $table->index(['agency_id', 'type']);
        });

        // Add missing indexes for support_tickets
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->index('assigned_to');
        });

        // Add missing indexes for ab_tests
        Schema::table('ab_tests', function (Blueprint $table) {
            $table->index(['agency_id', 'platform', 'status']);
        });

        // Fix foreign key constraints
        Schema::table('campaign_post', function (Blueprint $table) {
            $table->foreign('social_post_id')
                ->references('id')
                ->on('social_posts')
                ->nullOnDelete();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_credit_purchases', function (Blueprint $table) {
            $table->dropIndex(['agency_id', 'created_at']);
            $table->dropIndex(['status']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
            $table->dropIndex(['read_at']);
        });

        Schema::table('social_post_scheduled_logs', function (Blueprint $table) {
            $table->dropIndex(['social_post_id', 'status']);
        });

        Schema::table('custom_templates', function (Blueprint $table) {
            $table->dropIndex(['agency_id']);
            $table->dropIndex(['agency_id', 'type']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
        });

        Schema::table('ab_tests', function (Blueprint $table) {
            $table->dropIndex(['agency_id', 'platform', 'status']);
        });

        Schema::table('campaign_post', function (Blueprint $table) {
            $table->dropForeign(['social_post_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
    }
};
