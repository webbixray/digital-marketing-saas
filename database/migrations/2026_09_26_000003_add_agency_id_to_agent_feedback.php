<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_feedback', function (Blueprint $table) {
            $table->foreignId('agency_id')->nullable()->after('id')->constrained('agencies')->cascadeOnDelete();
            $table->index(['agency_id', 'agent_name', 'rating'], 'af_agency_agent_rating_index');
        });
    }

    public function down(): void
    {
        Schema::table('agent_feedback', function (Blueprint $table) {
            $table->dropForeign(['agency_id']);
            $table->dropIndex('af_agency_agent_rating_index');
            $table->dropColumn('agency_id');
        });
    }
};
