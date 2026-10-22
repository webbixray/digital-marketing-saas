<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'social_posts' => [
                ['agency_id', 'status', 'social_posts_agency_status_index'],
                ['agency_id', 'scheduled_at', 'social_posts_agency_scheduled_index'],
                ['agency_id', 'platform', 'social_posts_agency_platform_index'],
            ],
            'email_campaigns' => [
                ['agency_id', 'status', 'email_campaigns_agency_status_index'],
            ],
            'invoices' => [
                ['agency_id', 'status', 'invoices_agency_status_index'],
                ['agency_id', 'paid_at', 'invoices_agency_paid_at_index'],
            ],
            'ai_content_logs' => [
                ['agency_id', 'status', 'ai_logs_agency_status_index'],
                ['agency_id', 'action', 'ai_logs_agency_action_index'],
                ['agency_id', 'created_at', 'ai_logs_agency_created_index'],
            ],
            'activity_logs' => [
                ['agency_id', 'created_at', 'activity_agency_created_index'],
            ],
            'clients' => [
                ['agency_id', 'status', 'clients_agency_status_index'],
            ],
            'campaigns' => [
                ['agency_id', 'status', 'campaigns_agency_status_index'],
            ],
            'users' => [
                ['agency_id', 'users_agency_id_index'],
            ],
            'social_accounts' => [
                ['agency_id', 'social_accounts_agency_id_index'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $index) {
                $name = array_pop($index);
                $columns = $index;
                $this->tryAddIndex($table, $columns, $name);
            }
        }
    }

    public function down(): void
    {
        // Indexes dropped automatically with table
    }

    /**
     * Try to add an index, silently fail if it already exists.
     */
    private function tryAddIndex(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        } catch (Exception $e) {
            // Index may already exist, ignore
        }
    }
};
