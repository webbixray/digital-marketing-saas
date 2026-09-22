<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PART 1: Fix existing foreign key delete behaviors
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->foreign('agency_id')
                ->references('id')->on('agencies')
                ->onDelete('cascade');
        });

        // content_insights.social_post_id: change ON DELETE SET NULL -> CASCADE
        Schema::table('content_insights', function (Blueprint $table) {
            $table->dropForeign(['social_post_id']);
            $table->dropUnique('content_insights_social_post_id_insight_date_unique');
        });

        Schema::table('content_insights', function (Blueprint $table) {
            $table->foreign('social_post_id')
                ->references('id')->on('social_posts')
                ->onDelete('cascade');
            $table->unique(['social_post_id', 'insight_date']);
        });

        // PART 2: Add agency_id to orphaned tables
        Schema::table('workflow_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('agency_id')->nullable()->after('workflow_execution_id');
            $table->foreign('agency_id')
                ->references('id')->on('agencies')
                ->onDelete('cascade');
        });

        Schema::table('workflow_webhook_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('agency_id')->nullable()->after('workflow_id');
            $table->foreign('agency_id')
                ->references('id')->on('agencies')
                ->onDelete('cascade');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('agency_id')->nullable()->after('notifiable_id');
            $table->foreign('agency_id')
                ->references('id')->on('agencies')
                ->onDelete('cascade');
        });

        // PART 3: Add composite indexes
        Schema::table('social_posts', function (Blueprint $table) {
            $table->index(['agency_id', 'status', 'created_at'], 'social_posts_agency_id_status_created_at_index');
        });

        $this->addIndexIfNotExists('custom_templates', ['agency_id', 'type'], 'custom_templates_agency_id_type_index');

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['agency_id', 'action'], 'activity_logs_agency_id_action_index');
        });

        $this->addIndexIfNotExists('ai_credit_purchases', ['agency_id'], 'ai_credit_purchases_agency_id_index');

        // PART 4: Add unique constraints
        $this->addIndexIfNotExists('custom_templates', ['agency_id', 'type', 'name'], 'custom_templates_agency_id_type_name_unique');

        // email_templates[agency_id, slug] — drop existing single-column slug unique first
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropUnique('email_templates_slug_unique');
            $table->unique(['agency_id', 'slug'], 'email_templates_agency_id_slug_unique');
        });
    }

    public function down(): void
    {
        // Restore email_templates
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropUnique('email_templates_agency_id_slug_unique');
            $table->unique('slug', 'email_templates_slug_unique');
        });

        // Restore custom_templates
        Schema::table('custom_templates', function (Blueprint $table) {
            $table->dropUnique('custom_templates_agency_id_type_name_unique');
        });

        // Drop composite indexes
        foreach ([
            ['ai_credit_purchases', 'ai_credit_purchases_agency_id_index'],
            ['activity_logs', 'activity_logs_agency_id_action_index'],
            ['social_posts', 'social_posts_agency_id_status_created_at_index'],
        ] as [$table, $index]) {
            $this->dropIndexIfExists($table, $index);
        }

        // Drop agency_id columns + FK
        foreach (['notifications', 'workflow_webhook_logs', 'workflow_logs'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropForeign(['agency_id']);
                $table->dropColumn('agency_id');
            });
        }

        // Restore content_insights FK + unique
        Schema::table('content_insights', function (Blueprint $table) {
            $table->dropForeign(['social_post_id']);
            $table->dropUnique('content_insights_social_post_id_insight_date_unique');
        });
        Schema::table('content_insights', function (Blueprint $table) {
            $table->foreign('social_post_id')
                ->references('id')->on('social_posts')
                ->onDelete('set null');
            $table->unique(['social_post_id', 'insight_date']);
        });

        // Restore users FK
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->foreign('agency_id')
                ->references('id')->on('agencies')
                ->onDelete('set null');
        });
    }

    /**
     * Add an index only if it doesn't already exist (cross-database).
     */
    private function addIndexIfNotExists(string $table, $columns, string $name): void
    {
        if ($this->indexExists($table, $name)) {
            return;
        }
        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    /**
     * Drop an index if it exists (cross-database).
     */
    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!$this->indexExists($table, $index)) {
            return;
        }
        try {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        } catch (\Exception $e) {
            // Index may not exist
        }
    }

    /**
     * Check if an index exists on a table (works on SQLite and MySQL).
     */
    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            $indexes = DB::select(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='{$table}'"
            );
            foreach ($indexes as $idx) {
                if ($idx->name === $index) {
                    return true;
                }
            }
            return false;
        }
        
        // MySQL
        $indexes = DB::select(DB::raw("SHOW INDEX FROM `{$table}`"));
        foreach ($indexes as $idx) {
            if ($idx->Key_name === $index) {
                return true;
            }
        }
        return false;
    }
};
