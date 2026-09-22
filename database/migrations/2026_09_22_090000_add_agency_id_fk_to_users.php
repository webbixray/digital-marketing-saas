<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add cascade on delete to users.agency_id foreign key.
     *
     * The original migration (2026_09_04_153100) created the FK with
     * nullOnDelete(). For tenant integrity, when an agency is deleted
     * its users should be cascaded rather than orphaned.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing FK (may be nullOnDelete or already cascade from v6 repair)
            $table->dropForeign(['agency_id']);

            // Re-add with cascade on delete for tenant isolation integrity
            $table->foreign('agency_id')
                ->references('id')->on('agencies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migration: restore nullOnDelete behavior.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);

            $table->foreign('agency_id')
                ->references('id')->on('agencies')
                ->onDelete('set null');
        });
    }
};
