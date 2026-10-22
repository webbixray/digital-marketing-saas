<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoices: add client_name (denormalized label for one-off invoices without
     * a client record) and soft deletes (financial records must never be hard-deleted).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'client_name')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('client_name')->nullable()->after('client_id');
            });
        }

        if (! Schema::hasColumn('invoices', 'deleted_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'client_name')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('client_name');
            });
        }

        if (Schema::hasColumn('invoices', 'deleted_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
