<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds composite indexes for frequently queried columns to improve query performance.
     */
    public function up(): void
    {
        // analytics_events: add composite index on (created_at, agency_id) for time-range queries
        $this->tryAddIndex('analytics_events', ['created_at', 'agency_id'], 'analytics_events_created_at_agency_id_index');
        $this->tryAddIndex('analytics_events', ['agency_id', 'created_at'], 'analytics_events_agency_id_created_at_index');

        // activity_logs: add composite index on (agency_id, action, created_at)
        $this->tryAddIndex('activity_logs', ['agency_id', 'action', 'created_at'], 'activity_logs_agency_action_created_index');
        $this->tryAddIndex('activity_logs', ['agency_id', 'created_at'], 'activity_logs_agency_created_index');

        // social_posts: add composite index on (platform, status, scheduled_at)
        $this->tryAddIndex('social_posts', ['platform', 'status', 'scheduled_at'], 'social_posts_platform_status_scheduled_index');
        $this->tryAddIndex('social_posts', ['agency_id', 'platform', 'status'], 'social_posts_agency_platform_status_index');

        // campaigns: add composite indexes
        $this->tryAddIndex('campaigns', ['agency_id', 'status', 'created_at'], 'campaigns_agency_status_created_index');

        // invoices: add composite indexes for date-range queries
        $this->tryAddIndex('invoices', ['agency_id', 'paid_at'], 'invoices_agency_paid_at_index');

        // email_campaigns: add composite indexes
        $this->tryAddIndex('email_campaigns', ['agency_id', 'status', 'created_at'], 'email_campaigns_agency_status_created_index');

        // agent_cost_logs: add composite index for cost reporting
        $this->tryAddIndex('agent_cost_logs', ['agency_id', 'executed_at'], 'agent_cost_logs_agency_executed_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->tryDropIndex('analytics_events', 'analytics_events_created_at_agency_id_index');
        $this->tryDropIndex('analytics_events', 'analytics_events_agency_id_created_at_index');
        $this->tryDropIndex('activity_logs', 'activity_logs_agency_action_created_index');
        $this->tryDropIndex('activity_logs', 'activity_logs_agency_created_index');
        $this->tryDropIndex('social_posts', 'social_posts_platform_status_scheduled_index');
        $this->tryDropIndex('social_posts', 'social_posts_agency_platform_status_index');
        $this->tryDropIndex('campaigns', 'campaigns_agency_status_created_index');
        $this->tryDropIndex('invoices', 'invoices_agency_paid_at_index');
        $this->tryDropIndex('email_campaigns', 'email_campaigns_agency_status_created_index');
        $this->tryDropIndex('agent_cost_logs', 'agent_cost_logs_agency_executed_index');
    }

    /**
     * Try to add an index, ignoring if it already exists.
     */
    private function tryAddIndex(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        } catch (\Exception $e) {
            // Index may already exist - skip
        }
    }

    /**
     * Try to drop an index, ignoring if it doesn't exist.
     */
    private function tryDropIndex(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        } catch (\Exception $e) {
            // Index may not exist - skip
        }
    }
};
