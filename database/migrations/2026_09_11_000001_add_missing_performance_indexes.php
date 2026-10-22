<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add composite indexes for common query patterns

        // social_accounts - agency_id + is_active for filtering active accounts
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->index(['agency_id', 'is_active'], 'social_accounts_agency_active_index');
        });

        // media_assets - agency_id + created_at for listing/sorting
        Schema::table('media_assets', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], 'media_assets_agency_created_index');
        });

        // content_assets - agency_id + status for filtering by status
        Schema::table('content_assets', function (Blueprint $table) {
            $table->index(['agency_id', 'status'], 'content_assets_agency_status_index');
        });

        // content_templates - agency_id + status for filtering by status
        Schema::table('content_templates', function (Blueprint $table) {
            $table->index(['agency_id', 'status'], 'content_templates_agency_status_index');
        });

        // landing_pages - agency_id + is_published for filtering
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->index(['agency_id', 'is_published'], 'landing_pages_agency_published_index');
        });

        // forms - agency_id + is_published for filtering
        Schema::table('forms', function (Blueprint $table) {
            $table->index(['agency_id', 'is_published'], 'forms_agency_published_index');
        });

        // email_campaigns - agency_id + created_at for listing/sorting
        Schema::table('email_campaigns', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], 'email_campaigns_agency_created_index');
        });

        // inbox_messages - agency_id + status for filtering inbox by status
        Schema::table('inbox_messages', function (Blueprint $table) {
            $table->index(['agency_id', 'status'], 'inbox_messages_agency_status_index');
        });

        // workflows - agency_id + created_at for listing/sorting
        Schema::table('workflows', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], 'workflows_agency_created_index');
        });

        // invoices - agency_id + created_at for listing/sorting
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], 'invoices_agency_created_index');
        });

        // clients - agency_id + created_at for listing/sorting
        Schema::table('clients', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], 'clients_agency_created_index');
        });

        // campaigns - agency_id + created_at for listing/sorting
        Schema::table('campaigns', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], 'campaigns_agency_created_index');
        });

        // social_posts - agency_id + created_at for listing/sorting
        Schema::table('social_posts', function (Blueprint $table) {
            $table->index(['agency_id', 'created_at'], 'social_posts_agency_created_index');
        });
    }

    public function down(): void
    {
        $tables = [
            'social_accounts' => 'social_accounts_agency_active_index',
            'media_assets' => 'media_assets_agency_created_index',
            'content_assets' => 'content_assets_agency_status_index',
            'content_templates' => 'content_templates_agency_status_index',
            'landing_pages' => 'landing_pages_agency_published_index',
            'forms' => 'forms_agency_published_index',
            'email_campaigns' => 'email_campaigns_agency_created_index',
            'inbox_messages' => 'inbox_messages_agency_status_index',
            'workflows' => 'workflows_agency_created_index',
            'invoices' => 'invoices_agency_created_index',
            'clients' => 'clients_agency_created_index',
            'campaigns' => 'campaigns_agency_created_index',
            'social_posts' => 'social_posts_agency_created_index',
        ];

        foreach ($tables as $table => $index) {
            try {
                Schema::table($table, function (Blueprint $blueprint) use ($index) {
                    $blueprint->dropIndex($index);
                });
            } catch (Exception $e) {
                // Index may not exist
            }
        }
    }
};
