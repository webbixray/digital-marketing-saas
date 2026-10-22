<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite indexes for frequently queried columns
        $indexes = [
            'social_posts' => [
                ['agency_id', 'platform', 'status', 'social_posts_agency_platform_status_index'],
                ['agency_id', 'platform', 'created_at', 'social_posts_agency_platform_created_index'],
            ],
            'social_accounts' => [
                ['agency_id', 'is_active', 'social_accounts_agency_active_index'],
                ['agency_id', 'platform', 'social_accounts_agency_platform_index'],
            ],
            'users' => [
                ['agency_id', 'is_active', 'users_agency_active_index'],
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
