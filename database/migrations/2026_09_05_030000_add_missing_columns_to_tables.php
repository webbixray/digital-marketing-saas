<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // clients: website, address
        if (! Schema::hasColumn('clients', 'website')) {
            Schema::table('clients', fn ($t) => $t->string('website')->nullable()->after('phone'));
        }
        if (! Schema::hasColumn('clients', 'address')) {
            Schema::table('clients', fn ($t) => $t->text('address')->nullable()->after('notes'));
        }

        // invoices: client_id, tax_rate, tax_amount, paid_at
        if (! Schema::hasColumn('invoices', 'client_id')) {
            Schema::table('invoices', fn ($t) => $t->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete());
        }
        if (! Schema::hasColumn('invoices', 'tax_rate')) {
            Schema::table('invoices', fn ($t) => $t->decimal('tax_rate', 5, 2)->default(0));
        }
        if (! Schema::hasColumn('invoices', 'tax_amount')) {
            Schema::table('invoices', fn ($t) => $t->decimal('tax_amount', 10, 2)->default(0));
        }
        if (! Schema::hasColumn('invoices', 'paid_at')) {
            Schema::table('invoices', fn ($t) => $t->timestamp('paid_at')->nullable());
        }

        // content_assets: slug + more columns
        if (! Schema::hasColumn('content_assets', 'slug')) {
            Schema::table('content_assets', fn ($t) => $t->string('slug')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'description')) {
            Schema::table('content_assets', fn ($t) => $t->text('description')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'file_path')) {
            Schema::table('content_assets', fn ($t) => $t->string('file_path')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'file_url')) {
            Schema::table('content_assets', fn ($t) => $t->string('file_url')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'file_size')) {
            Schema::table('content_assets', fn ($t) => $t->unsignedInteger('file_size')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'file_mime')) {
            Schema::table('content_assets', fn ($t) => $t->string('file_mime')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'thumbnail_url')) {
            Schema::table('content_assets', fn ($t) => $t->string('thumbnail_url')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'tags')) {
            Schema::table('content_assets', fn ($t) => $t->string('tags')->nullable());
        }
        if (! Schema::hasColumn('content_assets', 'is_active')) {
            Schema::table('content_assets', fn ($t) => $t->boolean('is_active')->default(true));
        }

        // forms: description, is_active
        if (! Schema::hasColumn('forms', 'description')) {
            Schema::table('forms', fn ($t) => $t->text('description')->nullable());
        }
        if (! Schema::hasColumn('forms', 'is_active')) {
            Schema::table('forms', fn ($t) => $t->boolean('is_active')->default(true));
        }

        // webhooks: last_status_code
        if (! Schema::hasColumn('webhooks', 'last_status_code')) {
            Schema::table('webhooks', fn ($t) => $t->unsignedInteger('last_status_code')->nullable());
        }

        // workflows: slug, description, is_active, last_run_at, run_count
        if (! Schema::hasColumn('workflows', 'slug')) {
            Schema::table('workflows', fn ($t) => $t->string('slug')->nullable());
        }
        if (! Schema::hasColumn('workflows', 'description')) {
            Schema::table('workflows', fn ($t) => $t->text('description')->nullable());
        }
        if (! Schema::hasColumn('workflows', 'is_active')) {
            Schema::table('workflows', fn ($t) => $t->boolean('is_active')->default(true));
        }
        if (! Schema::hasColumn('workflows', 'last_run_at')) {
            Schema::table('workflows', fn ($t) => $t->timestamp('last_run_at')->nullable());
        }
        if (! Schema::hasColumn('workflows', 'run_count')) {
            Schema::table('workflows', fn ($t) => $t->unsignedInteger('run_count')->default(0));
        }
    }

    public function down(): void {}
};
